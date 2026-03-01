<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;
    protected NotificationService $notificationService;

    public function __construct(PayrollService $payrollService, NotificationService $notificationService)
    {
        $this->payrollService = $payrollService;
        $this->notificationService = $notificationService;
    }

    /**
     * List payroll runs with filters.
     */
    public function index(Request $request)
    {
        $query = PayrollRun::with(['user', 'generatedByUser', 'approvedByUser']);

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $payrolls = $query->latest()->paginate($request->query('per_page', 20));
        return $this->success($payrolls);
    }

    /**
     * Generate payroll for a specific month.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $results = $this->payrollService->generateForMonth(
            $validated['month'],
            Auth::id()
        );

        $msg = count($results['generated']) . ' payrolls generated';
        if (count($results['skipped']) > 0) {
            $msg .= ', ' . count($results['skipped']) . ' skipped';
        }
        if (count($results['errors']) > 0) {
            $msg .= ', ' . count($results['errors']) . ' errors';
        }

        return $this->success($results, $msg);
    }

    /**
     * Preview payroll calculation for a single employee (without saving).
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $employee = User::findOrFail($validated['user_id']);

        try {
            $data = $this->payrollService->calculateForEmployee(
                $employee,
                $validated['month'],
                Auth::id()
            );
            return $this->success($data);
        }
        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get a single payroll run (payslip view).
     */
    public function show(PayrollRun $payroll)
    {
        $payroll->load(['user', 'generatedByUser', 'approvedByUser', 'expense']);
        return $this->success($payroll);
    }

    /**
     * Approve a draft payroll.
     */
    public function approve(PayrollRun $payroll)
    {
        try {
            $payroll = $this->payrollService->approve($payroll, Auth::id());

            // ✅ Dispatch notification
            $this->notificationService->notify(
                $payroll->user_id,
                'payroll_approved',
                'Payroll Approved 💸',
                "Your payroll for {$payroll->month} has been approved. Net: ₹" . round($payroll->net_salary),
                ['link' => '/payroll/my-payslips']
            );

            return $this->success($payroll->load('user'), 'Payroll approved');
        }
        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Bulk approve multiple payrolls.
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:payroll_runs,id',
        ]);

        $approved = 0;
        $errors = [];

        foreach ($validated['ids'] as $id) {
            $payroll = PayrollRun::find($id);
            try {
                $this->payrollService->approve($payroll, Auth::id());
                
                // ✅ Dispatch notification
                $this->notificationService->notify(
                    $payroll->user_id,
                    'payroll_approved',
                    'Payroll Approved 💸',
                    "Your payroll for {$payroll->month} has been approved. Net: ₹" . round($payroll->net_salary),
                    ['link' => '/payroll/my-payslips']
                );
                
                $approved++;
            }
            catch (\Exception $e) {
                $errors[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        return $this->success(
        ['approved' => $approved, 'errors' => $errors],
            "{$approved} payrolls approved"
        );
    }

    /**
     * Mark payroll as paid (creates expense).
     */
    public function markPaid(PayrollRun $payroll)
    {
        try {
            $payroll = $this->payrollService->markPaid($payroll, Auth::id());

            // ✅ Dispatch notification
            $this->notificationService->notify(
                $payroll->user_id,
                'payroll_paid',
                'Salary Credited! 🏦',
                "Your salary for {$payroll->month} has been marked as paid.",
                [
                    'link' => '/payroll/my-payslips',
                    'color' => 'success'
                ]
            );

            return $this->success($payroll->load(['user', 'expense']), 'Payroll marked as paid');
        }
        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Bulk mark as paid.
     */
    public function bulkPay(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:payroll_runs,id',
        ]);

        $paid = 0;
        $errors = [];

        foreach ($validated['ids'] as $id) {
            $payroll = PayrollRun::find($id);
            try {
                $this->payrollService->markPaid($payroll, Auth::id());

                // ✅ Dispatch notification
                $this->notificationService->notify(
                    $payroll->user_id,
                    'payroll_paid',
                    'Salary Credited! 🏦',
                    "Your salary for {$payroll->month} has been marked as paid.",
                    [
                        'link' => '/payroll/my-payslips',
                        'color' => 'success'
                    ]
                );

                $paid++;
            }
            catch (\Exception $e) {
                $errors[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        return $this->success(
        ['paid' => $paid, 'errors' => $errors],
            "{$paid} payrolls marked as paid"
        );
    }

    /**
     * Get payroll stats/summary for a month.
     */
    public function monthSummary(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $payrolls = PayrollRun::where('month', $month)->get();

        return $this->success([
            'month' => $month,
            'total_employees' => $payrolls->count(),
            'total_gross' => round($payrolls->sum('total_earnings'), 2),
            'total_deductions' => round($payrolls->sum('total_deductions'), 2),
            'total_net' => round($payrolls->sum('net_salary'), 2),
            'draft' => $payrolls->where('status', 'draft')->count(),
            'approved' => $payrolls->where('status', 'approved')->count(),
            'paid' => $payrolls->where('status', 'paid')->count(),
        ]);
    }
}
