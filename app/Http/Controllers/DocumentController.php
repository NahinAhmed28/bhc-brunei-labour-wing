<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Document;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $r, Applicant $applicant)
    {
        $data = $r->validate(['type' => 'required|in:passport,demand-letter,visa,contract,medical,insurance,flight-ticket,other', 'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $file = $r->file('file');
        $version = (Document::where('applicant_id', $applicant->id)->where('type', $data['type'])->max('version') ?? 0) + 1;
        $path = $file->store('documents/'.$applicant->id, 'local');
        $doc = Document::create(['applicant_id' => $applicant->id, 'token_id' => $applicant->token_id, 'type' => $data['type'], 'version' => $version, 'original_name' => $file->getClientOriginalName(), 'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => $r->user()->id]);
        AuditService::record('upload-document', 'documents', $doc);

        return back()->with('success', 'Document uploaded as version '.$version.'.');
    }

    public function download(Document $document)
    {
        abort_unless(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry', 'viewer'), 403);
        AuditService::record('download-document', 'documents', $document);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
