<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\Worker;
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
        $tokens = Token::with(['company', 'agency', 'category', 'currentHolder'])->latest()->get();
        AuditService::record('export', 'reports', 'token-register', [], ['format' => $format]);
        if ($format === 'pdf') {
            return Pdf::loadView('pdf.token-register', compact('tokens'))->setPaper('a4', 'landscape')->download('token-register.pdf');
        }

        return response()->view('exports.tokens', compact('tokens'), 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="token-register.xls"']);
    }

    public function workers(string $format)
    {
        $workers = Worker::with('token.company', 'token.agency')->latest()->get();
        AuditService::record('export', 'reports', 'worker-register', [], ['format' => $format]);
        if ($format === 'pdf') {
            return Pdf::loadView('pdf.worker-register', compact('workers'))->setPaper('a4', 'landscape')->download('worker-register.pdf');
        }

        return response()->view('exports.workers', compact('workers'), 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="worker-register.xls"']);
    }
}
