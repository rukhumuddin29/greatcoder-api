<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LeadDocumentController extends Controller
{

    public function index(Lead $lead)
    {
        $documents = $lead->documents()->with('user')->orderBy('created_at', 'desc')->get();
        return $this->success($documents);
    }

    public function store(Request $request, Lead $lead)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:5120',
            'category' => 'required|string|in:id_proof,certificate,photo,resume,other',
            'name' => 'required|string|max:100'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store("leads/{$lead->id}", 'local');

            $document = $lead->documents()->create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'category' => $request->category
            ]);

            return $this->success($document->load('user'), 'Document uploaded successfully', 201);
        }

        return $this->error('File upload failed', 422);
    }

    public function download(LeadDocument $document)
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($document->file_path, "{$document->name}.{$document->file_type}");
    }

    public function destroy(LeadDocument $document)
    {
        $document->delete();
        return $this->success(null, 'Document deleted successfully');
    }
}
