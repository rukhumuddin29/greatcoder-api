<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\NotificationService;

class LeaveController extends Controller
{
    protected $leaveService;
    protected $balanceService;
    protected $notificationService;

    public function __construct(LeaveService $leaveService, LeaveBalanceService $balanceService, NotificationService $notificationService)
    {
        $this->leaveService = $leaveService;
        $this->balanceService = $balanceService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $query = LeaveRequest::with(['user', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return $this->success($query->paginate($request->input('per_page', 15)));
    }

    public function myLeaves(Request $request)
    {
        $query = LeaveRequest::with(['approver'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $this->success($query->paginate($request->input('per_page', 15)));
    }

    public function myBalance(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $balances = $this->balanceService->getBalances($request->user()->id, $year);
        
        return $this->success($balances);
    }

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => 'required|in:sick_leave,casual_leave,earned_leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'is_half_day' => 'boolean',
            'half_day_type' => 'nullable|required_if:is_half_day,true|in:first_half,second_half',
        ]);

        try {
            $leave = $this->leaveService->apply($request->user()->id, $validated);

            // ✅ Dispatch notification to Approvers
            $this->notificationService->notifyByPermission(
                'leaves.approve',
                'leave_requested',
                'New Leave Request 📅',
                "{$request->user()->name} has applied for " . str_replace('_', ' ', $leave->leave_type) . ". Total days: " . $leave->total_days,
                ['link' => '/leaves/approvals']
            );

            return $this->success($leave, 'Leave application submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        try {
            $this->leaveService->cancel($leaveRequest, $request->user()->id);
            return $this->success(null, 'Leave request cancelled');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        try {
            $this->leaveService->approve($leaveRequest, $request->user()->id, $request->remarks);

            // ✅ Dispatch notification to Applicant
            $this->notificationService->notify(
                $leaveRequest->user_id,
                'leave_approved',
                'Leave Approved ✅',
                "Your leave request for " . Carbon::parse($leaveRequest->start_date)->format('d M') . " has been approved.",
                [
                    'link' => '/leaves/my-leaves',
                    'color' => 'success'
                ]
            );

            return $this->success(null, 'Leave request approved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        try {
            $this->leaveService->reject($leaveRequest, $request->user()->id, $request->remarks);

            // ✅ Dispatch notification to Applicant
            $this->notificationService->notify(
                $leaveRequest->user_id,
                'leave_rejected',
                'Leave Rejected ❌',
                "Your leave request for " . Carbon::parse($leaveRequest->start_date)->format('d M') . " has been rejected.",
                [
                    'link' => '/leaves/my-leaves',
                    'color' => 'error'
                ]
            );

            return $this->success(null, 'Leave request rejected');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function pending(Request $request)
    {
        $pending = LeaveRequest::with(['user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
            
        return $this->success($pending);
    }
}
