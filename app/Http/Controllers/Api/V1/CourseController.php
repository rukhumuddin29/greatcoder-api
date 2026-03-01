<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index(Request $request)
    {
        $activeOnly = $request->has('active');
        $courses = $this->courseService->getAll($activeOnly);
        return $this->success($courses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'required|string|max:50|unique:courses',
            'original_price' => 'required|numeric',
            'offer_price' => 'required|numeric',
        ]);

        $course = $this->courseService->create($request->all(), $request->user()->id);
        return $this->success($course, 'Course created successfully', 201);
    }

    public function show(Course $course)
    {
        return $this->success($course);
    }

    public function update(Request $request, Course $course)
    {
        $course = $this->courseService->update($course, $request->all());
        return $this->success($course, 'Course updated successfully');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return $this->success(null, 'Course deleted successfully');
    }
}
