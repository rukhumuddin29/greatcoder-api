<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCallLog;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BdeScorecardService extends BaseService
{
    /**
     * Get full scorecard for a specific BDE.
     */
    public function getScorecard(int $userId, string $period = 'this_month', bool $includeRank = true): array
    {
        $range = $this->getDateRange($period);
        $user = User::findOrFail($userId);

        // 1. Lead Metrics
        $leadStats = Lead::where('assigned_to', $userId)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = "converted" THEN 1 ELSE 0 END) as converted,
                SUM(CASE WHEN status IN ("lost", "not_interested") THEN 1 ELSE 0 END) as lost,
                AVG(CASE WHEN status = "converted" AND converted_at IS NOT NULL 
                    THEN DATEDIFF(converted_at, created_at) ELSE NULL END) as avg_days_to_convert
            ')
            ->first();

        // 2. Call Metrics
        $callStats = LeadCallLog::where('called_by', $userId)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('
                COUNT(*) as total_calls,
                AVG(call_duration_seconds) as avg_duration,
                COUNT(DISTINCT DATE(created_at)) as active_days
            ')
            ->first();

        // 3. Revenue Metrics
        $revenueStats = Payment::join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('leads', 'enrollments.lead_id', '=', 'leads.id')
            ->where('leads.assigned_to', $userId)
            ->whereBetween('payments.payment_date', [$range['start'], $range['end']])
            ->selectRaw('SUM(payments.amount) as total_revenue')
            ->first();

        // 4. Follow-up Compliance (Approximate: Calls vs Follow-up dates)
        // This logic checks if leads due for follow-up in this period actually got a call log
        $compliance = $this->getComplianceRate($userId, $range);

        $metrics = [
            'total_leads' => (int)($leadStats->total_assigned ?? 0),
            'converted' => (int)($leadStats->converted ?? 0),
            'lost' => (int)($leadStats->lost ?? 0),
            'conversion_rate' => $leadStats->total_assigned > 0 
                ? round(($leadStats->converted / $leadStats->total_assigned) * 100, 1) : 0,
            'avg_days_to_convert' => round($leadStats->avg_days_to_convert ?? 0, 1),
            'total_calls' => (int)($callStats->total_calls ?? 0),
            'avg_call_duration' => round(($callStats->avg_duration ?? 0) / 60, 1), // in minutes
            'calls_per_day' => $callStats->active_days > 0 
                ? round($callStats->total_calls / $callStats->active_days, 1) : 0,
            'revenue' => (float)($revenueStats->total_revenue ?? 0),
            'compliance' => $compliance,
        ];

        $metrics['avg_deal_size'] = $metrics['converted'] > 0 
            ? round($metrics['revenue'] / $metrics['converted'], 2) : 0;

        $compositeScore = $this->calculateCompositeScore($metrics);

        // Pipeline
        $pipeline = Lead::where('assigned_to', $userId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Rank
        $rank = 0;
        $totalBdes = 0;
        if ($includeRank) {
            $rankData = $this->getRank($userId, $period);
            $rank = $rankData['rank'];
            $totalBdes = $rankData['total'];
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation,
                'avatar' => $user->avatar
            ],
            'period' => $period,
            'metrics' => $metrics,
            'pipeline' => $pipeline,
            'composite_score' => $compositeScore,
            'rank' => $rank,
            'total_bdes' => $totalBdes
        ];
    }

    /**
     * Get ranked list of all BDEs.
     */
    public function getLeaderboard(string $period = 'this_month'): array
    {
        $bdes = User::whereHas('roles', fn($q) => $q->where('name', 'bde'))->get();
        $leaderboard = [];

        foreach ($bdes as $bde) {
            $scorecard = $this->getScorecard($bde->id, $period, false);
            $leaderboard[] = [
                'id' => $bde->id,
                'name' => $bde->name,
                'avatar' => $bde->avatar,
                'composite_score' => $scorecard['composite_score'],
                'conversion_rate' => $scorecard['metrics']['conversion_rate'],
                'revenue' => $scorecard['metrics']['revenue'],
                'total_calls' => $scorecard['metrics']['total_calls']
            ];
        }

        usort($leaderboard, fn($a, $b) => $b['composite_score'] <=> $a['composite_score']);

        return array_map(function($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        }, $leaderboard, array_keys($leaderboard));
    }

    /**
     * Get 6-month performance trend.
     */
    public function getTrend(int $userId, int $months = 6): array
    {
        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $period = $date->format('Y-m');
            
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $conv = Lead::where('assigned_to', $userId)
                ->where('status', 'converted')
                ->whereBetween('converted_at', [$start, $end])
                ->count();

            $rev = Payment::join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
                ->join('leads', 'enrollments.lead_id', '=', 'leads.id')
                ->where('leads.assigned_to', $userId)
                ->whereBetween('payments.payment_date', [$start, $end])
                ->sum('payments.amount');

            $trend[] = [
                'label' => $date->format('M'),
                'conversions' => $conv,
                'revenue' => (float)$rev
            ];
        }
        return $trend;
    }

    private function calculateCompositeScore(array $m): int
    {
        $score = 0;

        // 1. Conversion Rate (35%) - Goal: 20%
        $score += min(35, ($m['conversion_rate'] / 20) * 35);

        // 2. Revenue (25%) - Benchmarked (simplified for now)
        $score += min(25, ($m['revenue'] / 100000) * 25);

        // 3. Call Activity (20%) - Goal: 20 calls/day
        $score += min(20, ($m['calls_per_day'] / 20) * 20);

        // 4. Speed (10%) - Goal: < 7 days
        if ($m['avg_days_to_convert'] > 0) {
            $score += max(0, min(10, (1 - ($m['avg_days_to_convert'] / 30)) * 10));
        }

        // 5. Compliance (10%)
        $score += ($m['compliance'] / 100) * 10;

        return (int)round($score);
    }

    private function getDateRange(string $period): array
    {
        $now = Carbon::now();
        switch ($period) {
            case 'last_month':
                return ['start' => $now->copy()->subMonth()->startOfMonth(), 'end' => $now->copy()->subMonth()->endOfMonth()];
            case 'this_quarter':
                return ['start' => $now->copy()->startOfQuarter(), 'end' => $now->copy()->endOfQuarter()];
            case 'this_year':
                return ['start' => $now->copy()->startOfYear(), 'end' => $now->copy()->endOfYear()];
            case 'this_month':
            default:
                return ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()];
        }
    }

    private function getComplianceRate(int $userId, array $range): float
    {
        // Simple heuristic: Number of leads due for follow up this month that have at least one call log this month
        $dueLeads = Lead::where('assigned_to', $userId)
            ->whereBetween('follow_up_date', [$range['start'], $range['end']])
            ->pluck('id');

        if ($dueLeads->isEmpty()) return 100;

        $followedUpCount = LeadCallLog::whereIn('lead_id', $dueLeads)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->distinct('lead_id')
            ->count();

        return round(($followedUpCount / $dueLeads->count()) * 100, 1);
    }

    private function getRank(int $userId, string $period): array
    {
        $leaderboard = $this->getLeaderboard($period);
        $rank = 0;
        foreach ($leaderboard as $item) {
            if ($item['id'] === $userId) {
                $rank = $item['rank'];
                break;
            }
        }
        return ['rank' => $rank, 'total' => count($leaderboard)];
    }
}
