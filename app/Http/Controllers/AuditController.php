<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $r)
    {
        $logs = AuditLog::with('user')->when($r->q, fn ($q, $v) => $q->where('module', 'like', "%$v%")->orWhere('action', 'like', "%$v%"))->latest()->paginate(25)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
