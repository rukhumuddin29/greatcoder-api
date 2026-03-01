<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class LeadController extends Controller
{
    protected $leadService;
    protected $notificationService;

    public function __construct(LeadService $leadService, NotificationService $notificationService)
    {
        $this->leadService = $leadService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['status', 'assigned_to', 'search']);

        // Restrictions for BDE
        if ($user->hasRole('bde') && !$user->hasAnyRole(['admin', 'super_admin'])) {
            $filters['assigned_to'] = $user->id;
        }

        $leads = $this->leadService->getAll($filters);
        return $this->success($leads);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:150',
            'lead_type' => 'nullable|string|in:student,professional,other',
        ]);

        $dupeService = app(\App\Services\DuplicateDetectionService::class);
        $duplicates = $dupeService->findDuplicates($request->only(['name', 'phone', 'email', 'alternate_phone']));
        $highConfidence = array_filter($duplicates, fn($d) => $d['score'] >= 50);

        if (!empty($highConfidence) && !$request->boolean('force_create')) {
            return response()->json([
                'status' => 'duplicate_warning',
                'message' => 'Potential duplicate leads found.',
                'duplicates' => array_values($highConfidence)
            ], 409);
        }

        $lead = $this->leadService->create($request->all(), $request->user()->id);
        return $this->success($lead, 'Lead created successfully', 201);
    }

    public function show(Lead $lead, Request $request)
    {
        $user = $request->user();

        // If user doesn't have "view all" but only "view assigned", check ownership
        if (!$user->hasPermission('leads.view') && $user->hasPermission('leads.view_assigned')) {
            if ($lead->assigned_to !== $user->id && $lead->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized to view this lead.'], 403);
            }
        }

        return $this->success($lead->load(['assignedTo', 'createdBy', 'callLogs.calledByUser', 'interestedCourse']));
    }

    public function update(Request $request, Lead $lead)
    {
        if (!$this->authorizeLeadAccess($lead, $request)) {
            return response()->json(['message' => 'Unauthorized to update this lead.'], 403);
        }

        $lead = $this->leadService->update($lead, $request->all());
        return $this->success($lead, 'Lead updated successfully');
    }

    public function addCallLog(Request $request, Lead $lead)
    {
        if (!$this->authorizeLeadAccess($lead, $request)) {
            return response()->json(['message' => 'Unauthorized to add logs for this lead.'], 403);
        }

        $data = $request->validate([
            'call_outcome' => 'required|string',
            'notes' => 'nullable|string',
            'next_follow_up' => 'nullable|date',
            'channel' => 'nullable|string|in:phone,whatsapp,email',
        ]);

        $log = $this->leadService->addCallLog($lead, $request->all(), $request->user()->id);
        
        $this->logActivity('lead.call_logged', $lead, null, [
            'outcome' => $request->call_outcome,
            'notes' => $request->notes,
            'next_follow_up' => $request->next_follow_up
        ]);

        return $this->success($log->load('calledByUser'), 'Call log added successfully');
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $lead = $this->leadService->assign($lead, $request->assigned_to);

        // ✅ Dispatch notification to BDE
        $this->notificationService->notify(
            $request->assigned_to,
            'lead_assigned',
            'New Lead Assigned',
            "Lead \"{$lead->name}\" has been assigned to you.",
            [
                'link' => "/leads/{$lead->id}",
                'data' => ['lead_id' => $lead->id],
            ]
        );

        return $this->success($lead->load('assignedTo'), 'Lead assigned successfully');
    }

    public function snoozeFollowUp(Request $request, Lead $lead)
    {
        if (!$this->authorizeLeadAccess($lead, $request)) {
            return response()->json(['message' => 'Unauthorized to reschedule this lead.'], 403);
        }

        $days = $request->input('days', 1);
        $oldDate = $lead->follow_up_date;
        $lead->follow_up_date = Carbon::parse($lead->follow_up_date)->addDays($days);
        $lead->save();

        $this->logActivity('lead.follow_up_snoozed', $lead, ['old_date' => $oldDate], ['new_date' => $lead->follow_up_date]);

        return $this->success($lead, 'Follow-up rescheduled');
    }

    public function completeFollowUp(Request $request, Lead $lead)
    {
        if (!$this->authorizeLeadAccess($lead, $request)) {
            return response()->json(['message' => 'Unauthorized to complete this follow-up.'], 403);
        }

        $oldDate = $lead->follow_up_date;
        $lead->follow_up_date = null;
        $lead->save();

        $this->logActivity('lead.follow_up_completed', $lead, ['old_date' => $oldDate], ['new_date' => null]);
        return $this->success($lead, 'Follow-up marked as done');
    }

    public function unassignedCounts(Request $request)
    {
        $status = $request->query('status');
        $leadType = $request->query('lead_type');
        $count = $this->leadService->getUnassignedCounts($status, $leadType);
        return $this->success(['count' => $count]);
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'count' => 'required|integer|min:1',
            'status' => 'nullable|string',
            'lead_type' => 'nullable|string|in:student,professional,other'
        ]);

        $count = $this->leadService->bulkAssign(
            $validated['employee_id'],
            $validated['count'],
            $validated['status'] ?? null,
            $validated['lead_type'] ?? null
        );

        $this->logActivity('lead.bulk_assigned', null, [], ['assigned_to' => $validated['employee_id'], 'count' => $count, 'filters' => $validated]);

        // ✅ Dispatch notification to BDE
        if ($count > 0) {
            $this->notificationService->notify(
                $validated['employee_id'],
                'lead_assigned',
                'New Leads Assigned',
                "{$count} new leads have been assigned to you.",
                ['link' => '/leads']
            );
        }

        return $this->success(['count' => $count], "{$count} leads assigned successfully");
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'leads' => 'required|array',
            'leads.*.name' => 'required|string|max:150',
            'leads.*.phone' => 'required|string|max:20',
        ]);

        $dupeService = app(\App\Services\DuplicateDetectionService::class);
        $batchResults = $dupeService->checkBatch($request->leads);
        
        $toImport = [];
        $skipped = [];

        foreach ($request->leads as $index => $data) {
            $check = $batchResults[$index] ?? null;
            if ($check && $check['action'] === 'skip') {
                $skipped[] = [
                    'row' => $index + 1,
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'reason' => 'Definite duplicate detected',
                    'matches' => $check['duplicates']
                ];
                continue;
            }
            $toImport[] = $data;
        }

        $count = $this->leadService->bulkImport($toImport, $request->user()->id);
        $this->logActivity('lead.bulk_imported', null, [], ['imported_count' => $count, 'skipped_count' => count($skipped)]);
        
        return $this->success([
            'count' => $count,
            'skipped_count' => count($skipped),
            'skipped' => $skipped
        ], "{$count} leads imported, " . count($skipped) . " duplicates skipped.");
    }

    public function pipeline(Request $request)
    {
        $user = $request->user();
        $isBde = $user->hasRole('bde') && !$user->hasAnyRole(['admin', 'super_admin']);

        $query = Lead::query()->with(['assignedTo', 'interestedCourse']);

        // Role scoping
        if ($isBde) {
            $query->where('assigned_to', $user->id);
        }

        // Filters
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('interested_course_id')) {
            $query->where('interested_course_id', $request->interested_course_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $leads = $query->orderBy('updated_at', 'desc')->get();

        // Group into pipeline columns
        $columns = [
            'new' => ['statuses' => ['new', 'assigned'], 'label' => 'NEW', 'color' => 'blue', 'icon' => 'mdi-star-outline', 'leads' => []],
            'contacted' => ['statuses' => ['contacted', 'callback', 'no_response'], 'label' => 'CONTACTED', 'color' => 'indigo', 'icon' => 'mdi-phone-outline', 'leads' => []],
            'interested' => ['statuses' => ['interested', 'thinking'], 'label' => 'INTERESTED', 'color' => 'success', 'icon' => 'mdi-heart-outline', 'leads' => []],
            'demo_scheduled' => ['statuses' => ['demo_scheduled'], 'label' => 'DEMO SCHEDULED', 'color' => 'amber', 'icon' => 'mdi-calendar-star', 'leads' => []],
            'converted' => ['statuses' => ['converted'], 'label' => 'CONVERTED', 'color' => 'primary', 'icon' => 'mdi-check-circle', 'leads' => []],
            'lost' => ['statuses' => ['lost', 'not_interested'], 'label' => 'LOST', 'color' => 'error', 'icon' => 'mdi-close-circle', 'leads' => []],
        ];

        foreach ($leads as $lead) {
            foreach ($columns as $key => &$col) {
                if (in_array($lead->status, $col['statuses'])) {
                    $col['leads'][] = $lead;
                    break;
                }
            }
        }

        // Add counts
        foreach ($columns as &$col) {
            $col['count'] = count($col['leads']);
        }

        return $this->success($columns);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        if (!$this->authorizeLeadAccess($lead, $request)) {
            return response()->json(['message' => 'Unauthorized to update this lead stage.'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:new,contacted,interested,demo_scheduled,converted,lost'
        ]);

        $newStatus = $request->status;
        $oldStatus = $lead->status;

        $lead->status = $newStatus;

        // Auto-set converted_at timestamp when moving to converted
        if ($newStatus === 'converted' && $oldStatus !== 'converted') {
            $lead->converted_at = now();
        }

        // Clear converted_at if moved out of converted
        if ($newStatus !== 'converted' && $oldStatus === 'converted') {
            $lead->converted_at = null;
        }

        $lead->save();

        $this->logActivity('lead.status_changed', $lead, ['old_status' => $oldStatus], ['new_status' => $newStatus]);

        // ✅ Dispatch notification to Admin when lead is converted
        if ($newStatus === 'converted' && $oldStatus !== 'converted') {
            $this->notificationService->notifyByPermission(
                'reports.view',
                'lead_converted',
                'Lead Converted! 🏆',
                "Lead \"{$lead->name}\" has been converted by " . $request->user()->name,
                [
                    'link' => "/leads/{$lead->id}",
                    'color' => 'success'
                ]
            );
        }

        return $this->success($lead->load(['assignedTo', 'interestedCourse']), 'Status updated');
    }

    public function checkDuplicates(Request $request)
    {
        $dupeService = app(\App\Services\DuplicateDetectionService::class);
        $duplicates = $dupeService->findDuplicates($request->only(['name', 'phone', 'email', 'alternate_phone']));
        
        return $this->success([
            'has_duplicates' => count($duplicates) > 0,
            'duplicates' => $duplicates
        ]);
    }

    public function mergeLeads(Request $request)
    {
        $request->validate([
            'primary_id' => 'required|exists:leads,id',
            'secondary_id' => 'required|exists:leads,id|different:primary_id',
            'field_overrides' => 'nullable|array'
        ]);

        $primary = Lead::findOrFail($request->primary_id);
        $secondary = Lead::findOrFail($request->secondary_id);

        $dupeService = app(\App\Services\DuplicateDetectionService::class);
        $merged = $dupeService->mergeLeads($primary, $secondary, $request->field_overrides ?? []);

        return $this->success($merged, 'Leads merged successfully');
    }

    public function duplicates(Request $request)
    {
        // Simple dashboard logic: search for leads with shared phone or email
        // Real production would use a background job to build this table
        $minScore = $request->input('min_score', 50);
        
        $groups = [];

        // 1. Check Phone Duplicates
        $phoneDupes = Lead::whereNotNull('phone')
            ->select('phone', DB::raw('count(*) as count'))
            ->groupBy('phone')
            ->having('count', '>', 1)
            ->limit(25)
            ->get();

        foreach ($phoneDupes as $dupe) {
            $leads = Lead::where('phone', $dupe->phone)->get();
            $groups[] = [
                'field' => 'phone',
                'value' => $dupe->phone,
                'leads' => $leads->load(['assignedTo', 'interestedCourse']),
                'score' => 100 
            ];
        }

        // 2. Check Email Duplicates
        $emailDupes = Lead::whereNotNull('email')
            ->select('email', DB::raw('count(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->limit(25)
            ->get();

        foreach ($emailDupes as $dupe) {
            $leads = Lead::where('email', $dupe->email)->get();
            $groups[] = [
                'field' => 'email',
                'value' => $dupe->email,
                'leads' => $leads->load(['assignedTo', 'interestedCourse']),
                'score' => 100
            ];
        }

        return $this->success($groups);
    }

    protected function authorizeLeadAccess(Lead $lead, Request $request)
    {
        $user = $request->user();

        // 1. Super admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. If user has view-all/edit-all, they pass
        if ($user->hasPermission('leads.view') || $user->hasPermission('leads.update_all')) {
            return true;
        }

        // 3. Fallback to ownership check (Assigned or Created)
        return $lead->assigned_to === $user->id || $lead->created_by === $user->id;
    }
}
