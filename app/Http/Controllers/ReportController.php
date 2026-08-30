<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Token;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function tokens(string $format)
    {
        $tokens = Token::with(['company', 'agency', 'category', 'currentDesk'])->latest()->get();
        AuditService::record('export', 'reports', 'token-register', [], ['format' => $format]);
        if ($format === 'pdf') {
            return Pdf::loadView('pdf.token-register', compact('tokens'))->setPaper('a4', 'landscape')->download('token-register.pdf');
        }

return response()->view('exports.tokens', compact('tokens'), 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="token-register.xls"']);
    }

    public function applicants(string $format)
    {
        $applicants = Applicant::with('token.company', 'token.agency')->latest()->get();
        AuditService::record('export', 'reports', 'applicant-register', [], ['format' => $format]);
        if ($format === 'pdf') {
            return Pdf::loadView('pdf.applicant-register', compact('applicants'))->setPaper('a4', 'landscape')->download('applicant-register.pdf');
        }

return response()->view('exports.applicants', compact('applicants'), 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="applicant-register.xls"']);
    }
}
