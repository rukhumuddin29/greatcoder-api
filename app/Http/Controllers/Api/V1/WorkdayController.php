<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\WorkdaySetting;
use Illuminate\Http\Request;

class WorkdayController extends Controller
{
    public function index()
    {
        $settings = WorkdaySetting::all()->pluck('value', 'key');
        $holidays = Holiday::orderBy('holiday_date')->get();

        return $this->success([
            'settings' => $settings,
            'holidays' => $holidays
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'weekends' => 'nullable|array',
            'weekends.*' => 'integer|min:0|max:6',
            'office_start_time' => 'nullable|string',
            'office_end_time' => 'nullable|string',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'half_day_threshold_hours' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->only(['weekends', 'office_start_time', 'office_end_time', 'grace_period_minutes', 'half_day_threshold_hours']) as $key => $value) {
            if ($value !== null) {
                WorkdaySetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return $this->success(null, 'Workday settings updated successfully');
    }

    public function updateLeavePolicy(Request $request)
    {
        $request->validate([
            'sick_leave.annual_quota' => 'required|integer|min:0|max:30',
            'sick_leave.allow_half_day' => 'required|boolean',
            'casual_leave.annual_quota' => 'required|integer|min:0|max:30',
            'casual_leave.allow_half_day' => 'required|boolean',
            'earned_leave.annual_quota' => 'required|integer|min:0|max:60',
            'earned_leave.carry_forward' => 'required|boolean',
            'earned_leave.carry_forward_max' => 'required|integer|min:0|max:60',
            'earned_leave.allow_half_day' => 'required|boolean',
        ]);

        $setting = WorkdaySetting::updateOrCreate(
        ['key' => 'leave_policy'],
        ['value' => $request->all()]
        );

        return $this->success($setting, 'Leave policy updated successfully');
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'is_recurring' => 'boolean'
        ]);

        $holiday = Holiday::create($validated);

        return $this->success($holiday, 'Holiday added successfully');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();
        return $this->success(null, 'Holiday deleted successfully');
    }
}
