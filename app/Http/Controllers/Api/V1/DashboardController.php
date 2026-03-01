<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isBde = $user->hasRole('bde');
        $isAdmin = $user->hasAnyRole(['admin', 'super_admin']);

        $today = Carbon::today();
        $monthStr = Carbon::now()->format('Y-m');
        $monthStart = Carbon::now()->startOfMonth();

        // Base query for leads
        $leadQuery = Lead::query();
        if ($isBde) {
            $leadQuery->where('assigned_to', $user->id);
        }

        $stats = [
            'total_leads' => (clone $leadQuery)->count(),
            'new_leads_today' => (clone $leadQuery)->whereDate('created_at', $today)->count(),
        ];

        if ($isAdmin) {
            $stats['total_enrollments'] = Enrollment::count();
            $stats['revenue_this_month'] = Payment::whereDate('payment_date', '>=', $monthStart)->sum('amount');
            $stats['expenses_this_month'] = Expense::where('status', 'approved')
                ->whereDate('expense_date', '>=', $monthStart)
                ->sum('amount');

            // Payroll Stats
            $stats['active_employees'] = User::where('status', 'active')->count();
            $stats['payroll_liability_this_month'] = PayrollRun::where('month', $monthStr)
                ->whereIn('status', ['draft', 'approved'])
                ->sum('net_salary');
            $stats['payroll_paid_this_month'] = PayrollRun::where('month', $monthStr)
                ->where('status', 'paid')
                ->sum('net_salary');
        }

        if ($isBde) {
            $stats['interested_leads'] = (clone $leadQuery)->where('status', 'interested')->count();
            $stats['demo_scheduled'] = (clone $leadQuery)->where('status', 'demo_scheduled')->count();
            $stats['leads_by_type'] = [
                'student' => (clone $leadQuery)->where('lead_type', 'student')->count(),
                'professional' => (clone $leadQuery)->where('lead_type', 'professional')->count(),
            ];
        }

        $recentLeads = (clone $leadQuery)->with('interestedCourse')
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = [];
        if ($isAdmin) {
            $recentPayments = Payment::with(['enrollment.lead', 'enrollment.course'])
                ->latest()
                ->take(5)
                ->get();
        }

        return $this->success([
            'stats' => $stats,
            'recent_leads' => $recentLeads,
            'recent_payments' => $recentPayments,
            'user_role' => $isAdmin ? 'admin' : ($isBde ? 'bde' : 'other')
        ]);
    }

    public function getLeadStats(Request $request)
    {
        $user = $request->user();
        $isBde = $user->hasRole('bde');

        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));
        $targetUserId = $request->query('user_id');

        $query = Lead::query();
        if ($isBde) {
            $query->where('assigned_to', $user->id);
        }
        elseif ($targetUserId) {
            $query->where('assigned_to', $targetUserId);
        }

        $query->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $counts = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are present even with 0 count
        $allStatuses = [
            'new', 'contacted', 'interested', 'thinking', 'demo_scheduled',
            'no_response', 'callback', 'converted', 'lost', 'not_interested'
        ];

        $result = [];
        foreach ($allStatuses as $status) {
            $result[] = [
                'status' => $status,
                'count' => $counts[$status] ?? 0
            ];
        }

        return $this->success($result);
    }

    public function getFollowUps(Request $request)
    {
        $user = $request->user();
        $isBde = $user->hasRole('bde');
        $isAdmin = $user->hasAnyRole(['admin', 'super_admin']);
        $today = Carbon::today();

        $query = Lead::query()
            ->whereNotNull('follow_up_date')
            ->whereNotIn('status', ['converted', 'lost', 'not_interested']);

        // Restrictions
        if ($isBde && !$isAdmin) {
            $query->where('assigned_to', $user->id);
        }
        elseif ($request->has('user_id') && $isAdmin) {
            $query->where('assigned_to', $request->user_id);
        }

        // Overdue
        $overdue = (clone $query)->whereDate('follow_up_date', '<', $today)
            ->with(['assignedTo', 'interestedCourse'])
            ->orderBy('follow_up_date', 'asc')
            ->take(20)->get();

        // Today
        $todayLeads = (clone $query)->whereDate('follow_up_date', $today)
            ->with(['assignedTo', 'interestedCourse'])
            ->orderBy('follow_up_date', 'asc')
            ->get();

        // Upcoming (next 7 days)
        $upcoming = (clone $query)->whereDate('follow_up_date', '>', $today)
            ->whereDate('follow_up_date', '<=', $today->copy()->addDays(7))
            ->with(['assignedTo', 'interestedCourse'])
            ->orderBy('follow_up_date', 'asc')
            ->take(20)->get();

        return $this->success([
            'overdue' => $overdue,
            'today' => $todayLeads,
            'upcoming' => $upcoming,
            'counts' => [
                'overdue' => $overdue->count(),
                'today' => $todayLeads->count(),
                'upcoming' => $upcoming->count()
            ]
        ]);
    }

    public function getInterestedLeads(Request $request)
    {
        $user = $request->user();
        $isBde = $user->hasRole('bde');
        $isAdmin = $user->hasAnyRole(['admin', 'super_admin']);

        $query = Lead::query()
            ->whereIn('status', ['interested', 'thinking', 'demo_scheduled'])
            ->with(['assignedTo', 'interestedCourse']);

        if ($isBde && !$isAdmin) {
            $query->where('assigned_to', $user->id);
        }
        elseif ($isAdmin && $request->filled('user_id')) {
            $query->where('assigned_to', $request->user_id);
        }

        $leads = $query->orderBy('updated_at', 'desc')
            ->take(15)
            ->get();

        return $this->success($leads);
    }
}
