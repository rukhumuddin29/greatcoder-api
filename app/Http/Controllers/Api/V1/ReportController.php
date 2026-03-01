<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\PayrollRun;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get 12-month P&L breakdown and summary KPIs
     */
    public function financialSummary(Request $request)
    {
        $year = $request->input('year', now()->year);

        $data = $this->getFinancialData($year);

        return $this->success($data);
    }

    /**
     * Get revenue grouped by course
     */
    public function revenueByCourse(Request $request)
    {
        $year = $request->input('year', now()->year);

        $data = Payment::join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->whereYear('payments.payment_date', $year)
            ->whereNull('payments.deleted_at')
            ->selectRaw('courses.name as course_name, courses.code as course_code, 
                          SUM(payments.amount) as revenue, 
                          COUNT(DISTINCT enrollments.id) as enrollments')
            ->groupBy('courses.id', 'courses.name', 'courses.code')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $data->sum('revenue');
        $data->transform(function ($item) use ($totalRevenue) {
            $item->percentage = $totalRevenue > 0 ? round(($item->revenue / $totalRevenue) * 100, 1) : 0;
            return $item;
        });

        return $this->success($data);
    }

    /**
     * Get revenue grouped by BDE
     */
    public function revenueByBde(Request $request)
    {
        $year = $request->input('year', now()->year);

        $data = Payment::join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('leads', 'enrollments.lead_id', '=', 'leads.id')
            ->join('users', 'leads.assigned_to', '=', 'users.id')
            ->whereYear('payments.payment_date', $year)
            ->whereNull('payments.deleted_at')
            ->selectRaw('users.name as bde_name, users.id as bde_id, 
                          SUM(payments.amount) as revenue, 
                          COUNT(DISTINCT enrollments.id) as conversions')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get();

        $data->transform(function ($item) {
            $item->avg_deal_size = $item->conversions > 0 
                ? round($item->revenue / $item->conversions, 2) : 0;
            return $item;
        });

        return $this->success($data);
    }

    /**
     * Export P&L report as CSV
     */
    public function export(Request $request)
    {
        $year = $request->input('year', now()->year);
        $data = $this->getFinancialData($year);

        $headers = ['Month', 'Revenue', 'Expenses', 'Payroll', 'Net Profit'];
        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($data['monthly_breakdown'] as $row) {
                fputcsv($file, [
                    $row['month_label'],
                    $row['revenue'],
                    $row['expenses'],
                    $row['payroll'],
                    $row['net_profit']
                ]);
            }

            // Totals row
            fputcsv($file, [
                'TOTAL',
                $data['summary']['total_revenue'],
                $data['summary']['total_expenses'],
                $data['summary']['total_payroll'],
                $data['summary']['net_profit']
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=pnl_report_{$year}.csv",
        ]);
    }

    /**
     * Internal helper to aggregate financial data
     */
    protected function getFinancialData($year)
    {
        // Revenue
        $revenueByMonth = Payment::whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as m, SUM(amount) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        // Expenses (Approved only, excluding payroll-related)
        $expensesByMonth = Expense::whereYear('expense_date', $year)
            ->where('status', 'approved')
            ->whereNull('payroll_user_id')
            ->selectRaw('MONTH(expense_date) as m, SUM(amount) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        // Payroll
        $payrollByMonth = PayrollRun::whereYear('month', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw('MONTH(month) as m, SUM(net_salary) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        $breakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $rev = $revenueByMonth->get($m, 0);
            $exp = $expensesByMonth->get($m, 0);
            $pay = $payrollByMonth->get($m, 0);
            
            $breakdown[] = [
                'month' => sprintf('%d-%02d', $year, $m),
                'month_label' => date('F', mktime(0, 0, 0, $m, 1)),
                'revenue' => (float)$rev,
                'expenses' => (float)$exp,
                'payroll' => (float)$pay,
                'net_profit' => (float)($rev - $exp - $pay),
            ];
        }

        $totalRev = array_sum(array_column($breakdown, 'revenue'));
        $totalExp = array_sum(array_column($breakdown, 'expenses'));
        $totalPay = array_sum(array_column($breakdown, 'payroll'));
        $netProfit = $totalRev - $totalExp - $totalPay;

        $totalEnrollments = Enrollment::whereYear('created_at', $year)->count();

        return [
            'summary' => [
                'total_revenue' => round($totalRev, 2),
                'total_expenses' => round($totalExp, 2),
                'total_payroll' => round($totalPay, 2),
                'net_profit' => round($netProfit, 2),
                'profit_margin' => $totalRev > 0 ? round(($netProfit / $totalRev) * 100, 1) : 0,
                'total_enrollments' => $totalEnrollments,
                'avg_revenue_per_enrollment' => $totalEnrollments > 0 
                    ? round($totalRev / $totalEnrollments, 2) : 0,
            ],
            'monthly_breakdown' => $breakdown,
        ];
    }
}
