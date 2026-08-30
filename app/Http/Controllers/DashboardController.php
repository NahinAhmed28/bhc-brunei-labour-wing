<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Desk;
use App\Models\Token;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', ['stats' => ['tokens_today' => Token::whereDate('created_at', today())->count(), 'tokens_total' => Token::count(), 'demanded' => Token::sum('demanded_workers'), 'approved' => Token::sum('approved_workers'), 'applicants' => Applicant::count(), 'pending_bhc' => Token::whereNull('bhc_number')->count(), 'pending_boesl' => Token::where('boesl_status', 'pending')->count(), 'awaiting_flight' => Applicant::where('flight_status', 'pending')->count()], 'deskCounts' => Desk::withCount('tokens')->orderBy('display_order')->get(), 'recent' => AuditLog::with('user')->latest()->limit(8)->get()]);
    }
}
