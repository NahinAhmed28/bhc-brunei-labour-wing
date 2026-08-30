<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Token;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            ['label' => 'Tokens today', 'value' => Token::whereDate('created_at', today())->count(), 'url' => route('tokens.index', ['created' => 'today']), 'icon' => 'calendar-event'],
            ['label' => 'Total tokens', 'value' => Token::count(), 'url' => route('tokens.index'), 'icon' => 'ticket-detailed'],
            ['label' => 'Demanded workers', 'value' => Token::sum('demanded_workers'), 'url' => route('tokens.index'), 'icon' => 'people'],
            ['label' => 'Approved workers', 'value' => Token::sum('approved_workers'), 'url' => route('tokens.index'), 'icon' => 'person-check'],
            ['label' => 'Applicants entered', 'value' => Applicant::count(), 'url' => route('applicants.index'), 'icon' => 'person-vcard'],
            ['label' => 'Pending BHC No.', 'value' => Token::whereNull('bhc_number')->count(), 'url' => route('tokens.index', ['bhc_status' => 'pending']), 'icon' => 'file-earmark-text'],
            ['label' => 'Pending BOESL', 'value' => Token::where('boesl_status', 'pending')->count(), 'url' => route('tokens.index', ['boesl_status' => 'pending']), 'icon' => 'hourglass-split'],
            ['label' => 'Awaiting flight', 'value' => Applicant::where('flight_status', 'pending')->count(), 'url' => route('applicants.index', ['flight_status' => 'pending']), 'icon' => 'airplane'],
        ];

        return view('dashboard', [
            'metrics' => $metrics,
            'holderCounts' => User::withCount('heldTokens')->where('is_active', true)->has('heldTokens')->orderBy('name')->get(),
            'recent' => AuditLog::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
