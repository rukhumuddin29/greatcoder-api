<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected $expenseService;
    protected $notificationService;

    public function __construct(ExpenseService $expenseService, NotificationService $notificationService)
    {
        $this->expenseService = $expenseService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $query = Expense::with(['category', 'recordedBy', 'approvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $expenses = $query->latest()->paginate(20);
        return $this->success($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_mode' => 'required|in:cash,bank_transfer,upi,cheque',
        ]);

        $expense = $this->expenseService->record($request->all(), $request->user()->id);
        return $this->success($expense, 'Expense recorded successfully', 201);
    }

    public function show(Expense $expense)
    {
        return $this->success($expense->load(['category', 'recordedBy', 'approvedBy']));
    }

    public function approve(Request $request, Expense $expense)
    {
        $expense = $this->expenseService->approve($expense, $request->user()->id);

        // ✅ Dispatch notification to the recorder
        $this->notificationService->notify(
            $expense->created_by,
            'expense_approved',
            'Expense Approved ✅',
            "Your expense \"{$expense->title}\" for ₹" . round($expense->amount) . " has been approved.",
            [
                'link' => '/expenses',
                'color' => 'success'
            ]
        );

        return $this->success($expense, 'Expense approved');
    }

    public function categories()
    {
        $categories = ExpenseCategory::where('status', 'active')->get();
        return $this->success($categories);
    }
}
