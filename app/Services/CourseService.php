<?php

namespace App\Services;

use App\Models\Course;

class CourseService extends BaseService
{
    public function getAll(bool $activeOnly = false)
    {
        $query = Course::query();
        if ($activeOnly) {
            $query->where('status', 'active');
        }
        return $query->with('createdBy')->latest()->paginate(20);
    }

    public function create(array $data, int $userId)
    {
        return $this->transactional(function () use ($data, $userId) {
            $data['created_by'] = $userId;
            return Course::create($data);
        });
    }

    public function update(Course $course, array $data)
    {
        return $this->transactional(function () use ($course, $data) {
            $course->update($data);
            return $course;
        });
    }
}
