<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $payments = Payment::with(['enrollment.lead', 'receivedBy'])->latest()->paginate(20);
        return $this->success($payments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:cash,bank_transfer,upi,cheque,card',
            'payment_type' => 'required|in:down_payment,installment,full_payment,other',
        ]);

        $payment = $this->paymentService->record($request->all(), $request->user()->id);
        return $this->success($payment, 'Payment recorded successfully', 201);
    }

    public function show(Payment $payment)
    {
        return $this->success($payment->load(['enrollment.lead', 'receivedBy']));
    }
}
