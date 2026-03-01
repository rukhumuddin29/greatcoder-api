<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\MockInterview;
use App\Models\StudentInterview;
use App\Models\Placement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlacementController extends Controller
{
    // Mock Interviews
    public function storeMock(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'technical_score' => 'nullable|integer|min:0|max:10',
            'behavioral_score' => 'nullable|integer|min:0|max:10',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:scheduled,completed,cancelled'
        ]);

        $mock = $enrollment->mockInterviews()->create(array_merge($validated, [
            'interviewer_id' => Auth::id()
        ]));

        // Auto-update status to mock_ready if scores are good
        if (($validated['technical_score'] ?? 0) >= 7 && ($validated['behavioral_score'] ?? 0) >= 7) {
            $enrollment->update(['status' => 'mock_ready']);
        }

        return $this->success($mock->load('interviewer'), 'Mock interview recorded', 201);
    }

    // Corporate Interviews
    public function storeInterview(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:hiring_companies,id',
            'job_title' => 'required|string|max:255',
            'interview_date' => 'nullable|date',
            'status' => 'required|string|in:shortlisted,round_1,round_2,selected,rejected',
            'notes' => 'nullable|string'
        ]);

        $interview = $enrollment->studentInterviews()->create($validated);
        return $this->success($interview->load('company'), 'Interview record added', 201);
    }

    // Final Placement
    public function storePlacement(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:hiring_companies,id',
            'ctc_annual' => 'required|numeric|min:0',
            'join_date' => 'required|date',
            'designation' => 'required|string|max:255',
            'offer_letter' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('offer_letter')) {
            $path = $request->file('offer_letter')->store("offers/{$enrollment->id}", 'local');
            $validated['offer_letter_path'] = $path;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('placements', 'public');
            $validated['student_photo'] = $path;
        }

        $placement = $enrollment->placement()->create($validated);
        
        // Update Enrollment Status
        $enrollment->update(['status' => 'placed']);

        return $this->success($placement->load('company'), 'Placement recorded successfully!', 201);
    }

    public function report()
    {
        $stats = [
            'total_placed' => Placement::count(),
            'avg_ctc' => Placement::avg('ctc_annual') ?: 0,
            'top_companies' => \App\Models\HiringCompany::withCount('placements')
                ->orderBy('placements_count', 'desc')
                ->limit(5)
                ->get(),
            'recent_placements' => Placement::with(['enrollment.lead', 'company'])
                ->orderBy('join_date', 'desc')
                ->limit(10)
                ->get()
        ];

        return $this->success($stats);
    }

    public function updateStatus(Request $request, Enrollment $enrollment)
    {
        $request->validate(['status' => 'required|string|in:enrolled,completed,mock_ready,placed,alumni']);
        $enrollment->update(['status' => $request->status]);
        return $this->success($enrollment, 'Student status updated to ' . $request->status);
    }
}
