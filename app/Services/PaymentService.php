<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Enrollment;
use Carbon\Carbon;

class PaymentService extends BaseService
{
    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = Payment::whereYear('created_at', $year)->count() + 1;
        return "RCP-{$year}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function record(array $data, int $userId)
    {
        return $this->transactional(function () use ($data, $userId) {
            $enrollment = Enrollment::findOrFail($data['enrollment_id']);

            $payment = Payment::create(array_merge($data, [
                'received_by' => $userId,
                'payment_date' => $data['payment_date'] ?? now(),
            ]));

            // Check if full payment completed to update enrollment status if needed
            if ($enrollment->getBalanceDue() <= 0) {
            // Enrollment stays 'active' usually until course ends, 
            // but we could mark as 'paid' in a custom field
            }

            return $payment;
        });
    }
}
