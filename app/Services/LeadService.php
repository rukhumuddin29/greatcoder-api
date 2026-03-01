<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCallLog;
use Carbon\Carbon;

class LeadService extends BaseService
{
    public function getAll(array $filters = [])
    {
        $query = Lead::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->with(['assignedTo', 'createdBy', 'interestedCourse'])->latest()->paginate(20);
    }

    public function create(array $data, int $userId)
    {
        return $this->transactional(function () use ($data, $userId) {
            $data['created_by'] = $userId;
            $data['status'] = 'new';
            $lead = Lead::create($data);
            
            $this->logActivity('lead.created', $lead, null, $lead->only(['name', 'phone', 'status']));
            
            return $lead;
        });
    }

    public function update(Lead $lead, array $data)
    {
        return $this->transactional(function () use ($lead, $data) {
            $oldValues = $lead->only(array_keys($data));
            $lead->update($data);
            $this->logActivity('lead.updated', $lead, $oldValues, $lead->only(array_keys($data)));
            return $lead;
        });
    }

    public function addCallLog(Lead $lead, array $data, int $userId)
    {
        return $this->transactional(function () use ($lead, $data, $userId) {
            $log = $lead->callLogs()->create(array_merge($data, [
                'called_by' => $userId
            ]));

            // Update lead status based on call outcome if needed
            if (isset($data['status'])) {
                $lead->status = $data['status'];
            }

            if (isset($data['next_follow_up'])) {
                $lead->follow_up_date = $data['next_follow_up'];
            }

            $lead->save();

            return $log;
        });
    }

    public function assign(Lead $lead, int $employeeId)
    {
        return $this->transactional(function () use ($lead, $employeeId) {
            $oldValues = ['assigned_to' => $lead->assigned_to, 'status' => $lead->status];
            $lead->assigned_to = $employeeId;
            $lead->status = 'assigned';
            $lead->save();
            
            $this->logActivity('lead.assigned', $lead, $oldValues, ['assigned_to' => $employeeId, 'status' => 'assigned']);
            
            return $lead;
        });
    }

    public function getUnassignedCounts(?string $status = null, ?string $leadType = null)
    {
        $query = Lead::whereNull('assigned_to');

        if ($status) {
            $query->where('status', $status);
        }

        if ($leadType) {
            $query->where('lead_type', $leadType);
        }

        return $query->count();
    }

    public function bulkAssign(int $employeeId, int $count, ?string $status = null, ?string $leadType = null)
    {
        return $this->transactional(function () use ($employeeId, $count, $status, $leadType) {
            $query = Lead::whereNull('assigned_to');

            if ($status) {
                $query->where('status', $status);
            }
            if ($leadType) {
                $query->where('lead_type', $leadType);
            }

            if (!$status && !$leadType) {
                $query->where('status', 'new');
            }

            $leadsToAssign = $query->take($count)->get();
            $assignedCount = 0;

            foreach ($leadsToAssign as $lead) {
                $lead->assigned_to = $employeeId;
                $lead->status = 'assigned';
                $lead->save();
                $assignedCount++;
            }

            return $assignedCount;
        });
    }

    public function bulkImport(array $leadsData, int $userId)
    {
        $chunks = array_chunk($leadsData, 1000);
        $totalImported = 0;

        foreach ($chunks as $chunk) {
            $this->transactional(function () use ($chunk, $userId, &$totalImported) {
                foreach ($chunk as $data) {
                    $data['created_by'] = $userId;
                    $data['status'] = $data['status'] ?? 'new';
                    Lead::create($data);
                    $totalImported++;
                }
            });
        }

        return $totalImported;
    }
}
