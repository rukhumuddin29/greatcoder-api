<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get paginated activity logs with filters
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name')->latest();

        // Standard Filters
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->filled('action')) {
            $query->forAction($request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->between($request->date_from, $request->date_to);
        }

        // Global Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('new_values', 'like', "%{$search}%")
                  ->orWhere('old_values', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($request->input('per_page', 30));
        
        return $this->success($logs);
    }

    /**
     * Get activity history for a specific model instance
     */
    public function forModel(Request $request, $modelType, $modelId)
    {
        $logs = ActivityLog::with('user:id,name')
            ->forModel($modelType, $modelId)
            ->latest()
            ->paginate(request('per_page', 20));

        return $this->success($logs);
    }
}
