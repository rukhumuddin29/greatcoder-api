<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Log an activity to the audit trail.
     *
     * @param string      $action     The action verb (e.g. 'lead.created', 'payment.recorded')
     * @param mixed|null  $model      The Eloquent model being acted upon
     * @param array|null  $oldValues  Previous values (for updates)
     * @param array|null  $newValues  New values (for creates/updates)
     */
    protected function logActivity(string $action, $model = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => $model ? $this->getModelLabel($model) : null,
            'model_id'   => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Convert a model class to a human-readable label.
     */
    private function getModelLabel($model): string
    {
        $map = [
            'App\Models\Lead'       => 'Lead',
            'App\Models\Enrollment' => 'Enrollment',
            'App\Models\Payment'    => 'Payment',
            'App\Models\Expense'    => 'Expense',
            'App\Models\PayrollRun' => 'PayrollRun',
            'App\Models\User'       => 'User',
            'App\Models\Course'     => 'Course',
        ];

        return $map[get_class($model)] ?? class_basename($model);
    }
}
