<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function index()
    {
        $enrollments = Enrollment::with(['lead', 'course', 'enrolledBy'])->latest()->paginate(20);
        return $this->success($enrollments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'course_id' => 'required|exists:courses,id',
            'agreed_price' => 'required|numeric',
        ]);

        $enrollment = $this->enrollmentService->create($request->all(), $request->user()->id);
        return $this->success($enrollment, 'Student enrolled successfully', 201);
    }

    public function show(Enrollment $enrollment)
    {
        return $this->success($enrollment->load(['lead', 'course', 'enrolledBy', 'payments']));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $enrollment = $this->enrollmentService->update($enrollment, $request->all());
        return $this->success($enrollment, 'Enrollment updated successfully');
    }
}
