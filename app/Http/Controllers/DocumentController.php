<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTokenDocumentRequest;
use App\Models\Applicant;
use App\Models\Document;
use App\Models\Token;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function storeToken(StoreTokenDocumentRequest $request, Token $token): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $version = (Document::where('token_id', $token->id)
            ->whereNull('applicant_id')
            ->where('type', $data['type'])
            ->max('version') ?? 0) + 1;
        $path = $file->store('documents/tokens/'.$token->id, 'local');

        if ($path === false) {
            return back()->withErrors(['file' => 'The file could not be stored. Try again.']);
        }

        $document = Document::create([
            'token_id' => $token->id,
            'type' => $data['type'],
            'version' => $version,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);
        AuditService::record('upload-document', 'documents', $document);

        return back()->with('success', ucwords(str_replace('-', ' ', $data['type'])).' uploaded as version '.$version.'.');
    }

    public function download(Document $document)
    {
        abort_unless(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry', 'viewer'), 403);
        AuditService::record('download-document', 'documents', $document);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function preview(Request $request, Document $document): StreamedResponse
    {
        abort_unless($request->user()->hasAnyRole('super-admin', 'administrator', 'data-entry', 'viewer'), 403);
        abort_unless(
            $document->mime_type === 'application/pdf' || in_array($document->mime_type, ['image/jpeg', 'image/png'], true),
            404,
        );
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
