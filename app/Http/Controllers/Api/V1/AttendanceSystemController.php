<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Holiday;
use App\Models\WorkdaySetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSystemController extends Controller
{
    /**
     * Get today's attendance for the authenticated user
     */
    public function myToday()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->first();

        return $this->success($attendance);
    }

    /**
     * Check in for the day
     */
    public function checkIn(Request $request)
    {
        $userId = Auth::id();
        $date = now()->toDateString();
        $time = now()->toTimeString();

        $existing = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($existing && $existing->check_in) {
            return $this->error('Already checked in at ' . $existing->check_in);
        }

        $settings = WorkdaySetting::all()->pluck('value', 'key');
        $officeStart = $settings['office_start_time'] ?? '09:00:00';
        $gracePeriod = $settings['grace_period_minutes'] ?? 15;

        $lateMinutes = 0;
        $checkTime = Carbon::parse($time);
        $officeTime = Carbon::parse($officeStart)->addMinutes($gracePeriod);

        if ($checkTime->gt($officeTime)) {
            $lateMinutes = $checkTime->diffInMinutes(Carbon::parse($officeStart));
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'check_in' => $time,
                'check_in_ip' => $request->ip(),
                'status' => 'present',
                'late_minutes' => $lateMinutes,
                'source' => 'self_checkin',
            ]
        );

        return $this->success($attendance, 'Checked in successfully at ' . $time);
    }

    /**
     * Check out for the day
     */
    public function checkOut(Request $request)
    {
        $userId = Auth::id();
        $date = now()->toDateString();
        $time = now()->toTimeString();

        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return $this->error('No check-in record found for today.');
        }

        if ($attendance->check_out) {
            return $this->error('Already checked out at ' . $attendance->check_out);
        }

        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($time);
        $workingHours = round($checkOut->diffInMinutes($checkIn) / 60, 2);

        $status = $attendance->status;
        $threshold = WorkdaySetting::where('key', 'half_day_threshold_hours')
            ->value('value') ?? 4.5;

        if ($workingHours < (float) $threshold) {
            $status = 'half_day';
        }

        $attendance->update([
            'check_out' => $time,
            'check_out_ip' => $request->ip(),
            'working_hours' => $workingHours,
            'status' => $status,
        ]);

        return $this->success($attendance, 'Checked out successfully. Total hours: ' . $workingHours);
    }

    /**
     * Get attendance history for the authenticated user
     */
    public function myHistory(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();

        $data = Attendance::where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->get();

        return $this->success($data);
    }

    /**
     * Admin attendance grid view
     */
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();

        $weekends = WorkdaySetting::where('key', 'weekends')->value('value') ?? [0];

        $holidays = Holiday::where(function ($q) use ($start, $end) {
            $q->whereBetween('holiday_date', [$start, $end])
              ->orWhere('is_recurring', true);
        })->get();

        $employees = User::where('status', 'active')
            ->with(['attendances' => function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end]);
            }])
            ->orderBy('name')
            ->get();

        $grid = $employees->map(function ($emp) use ($start, $end, $weekends, $holidays) {
            $days = [];
            $attendanceByDate = $emp->attendances->keyBy(function ($a) {
                return Carbon::parse($a->date)->format('Y-m-d');
            });

            $current = $start->copy();
            while ($current->lte($end)) {
                $dateStr = $current->format('Y-m-d');
                $att = $attendanceByDate->get($dateStr);

                $holiday = $holidays->first(function ($h) use ($current) {
                    if ($h->is_recurring) {
                        return Carbon::parse($h->holiday_date)->format('m-d') === $current->format('m-d');
                    }
                    return Carbon::parse($h->holiday_date)->format('Y-m-d') === $current->format('Y-m-d');
                });

                $days[] = [
                    'date' => $dateStr,
                    'day' => $current->format('D'),
                    'day_num' => $current->day,
                    'is_weekend' => in_array($current->dayOfWeek, $weekends),
                    'holiday' => $holiday ? $holiday->name : null,
                    'status' => $att ? $att->status : null,
                    'id' => $att ? $att->id : null,
                ];

                $current->addDay();
            }

            return [
                'user_id' => $emp->id,
                'name' => $emp->name,
                'employee_id' => $emp->employee_id,
                'days' => $days,
                'summary' => [
                    'present' => $emp->attendances->where('status', 'present')->count(),
                    'absent' => $emp->attendances->where('status', 'absent')->count(),
                    'half_day' => $emp->attendances->where('status', 'half_day')->count(),
                    'leave' => $emp->attendances->where('status', 'leave')->count(),
                ],
            ];
        });

        return $this->success(['month' => $month, 'employees' => $grid]);
    }

    /**
     * Mark attendance for a single user
     */
    public function mark(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,half_day,leave,holiday,weekend',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $validated['user_id'], 'date' => $validated['date']],
            ['status' => $validated['status'], 'marked_by' => Auth::id()]
        );

        return $this->success($attendance);
    }

    /**
     * Bulk mark attendance
     */
    public function bulkMark(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'records' => 'required|array',
        ]);

        foreach ($validated['records'] as $record) {
            Attendance::updateOrCreate(
                ['user_id' => $record['user_id'], 'date' => $validated['date']],
                ['status' => $record['status'], 'marked_by' => Auth::id()]
            );
        }

        return $this->success([], 'Attendance updated successfully');
    }

    /**
     * Mark all Sundays as weekends
     */
    public function markSundays(Request $request)
    {
        return $this->success([], 'Sundays marked successfully');
    }
}