<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Course;
use Carbon\Carbon;

class EnrollmentService extends BaseService
{
    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = Enrollment::whereYear('created_at', $year)->count() + 1;
        return "ENR-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, int $userId)
    {
        return $this->transactional(function () use ($data, $userId) {
            $course = Course::findOrFail($data['course_id']);
            $lead = Lead::findOrFail($data['lead_id']);

            $enrollment = Enrollment::create(array_merge($data, [
                'course_price' => $course->offer_price,
                'created_by' => $userId,
                'enrolled_by' => $data['enrolled_by'] ?? $userId,
                'status' => 'active',
                'start_date' => $data['start_date'] ?? now(),
            ]));

            // Convert lead
            $lead->status = 'converted';
            $lead->converted_at = now();
            $lead->save();

            $this->logActivity('enrollment.created', $enrollment, null, $enrollment->toArray());

            return $enrollment;
        });
    }

    public function update(Enrollment $enrollment, array $data)
    {
        return $this->transactional(function () use ($enrollment, $data) {
            $oldValues = $enrollment->only(array_keys($data));
            $enrollment->update($data);
            $this->logActivity('enrollment.updated', $enrollment, $oldValues, $enrollment->only(array_keys($data)));
            return $enrollment;
        });
    }
}
