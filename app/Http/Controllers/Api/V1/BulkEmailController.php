<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BulkEmail;
use App\Models\Lead;
use App\Jobs\SendBulkEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkEmailController extends Controller
{
    public function getCounts(Request $request)
    {
        $status = $request->query('status');
        $query = Lead::whereNotNull('email');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $this->success(['count' => $query->count()]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string|in:leads,random',
            'target_status' => 'nullable|string',
            'recipient' => 'required_if:type,random|nullable|email'
        ]);

        $bulkEmail = BulkEmail::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'type' => $validated['type'],
            'target_status' => $validated['target_status'] ?? null,
            'recipient' => $validated['recipient'] ?? null,
            'sent_by' => Auth::id(),
            'status' => 'pending'
        ]);

        // Dispatch job
        SendBulkEmailJob::dispatch($bulkEmail);

        return $this->success($bulkEmail, 'Bulk email scheduled successfully');
    }

    public function index(Request $request)
    {
        $query = BulkEmail::with('sender');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sent_by')) {
            $query->where('sent_by', $request->sent_by);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $perPage = $request->query('per_page', 20);
        $emails = $query->latest()->paginate($perPage);

        return $this->success($emails);
    }
}
