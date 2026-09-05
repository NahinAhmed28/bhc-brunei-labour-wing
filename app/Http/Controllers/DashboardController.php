<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Token;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $showRecentActivity = $request->user()->isSuperAdmin();
        $metrics = [
            ['label' => 'Tokens today', 'value' => Token::whereDate('created_at', today())->count(), 'url' => route('tokens.index', ['created' => 'today']), 'icon' => 'calendar-event'],
            ['label' => 'Total tokens', 'value' => Token::count(), 'url' => route('tokens.index'), 'icon' => 'ticket-detailed'],
            ['label' => 'Demanded workers', 'value' => Token::sum('demanded_workers'), 'url' => route('tokens.index'), 'icon' => 'people'],
            ['label' => 'Approved workers', 'value' => Token::sum('approved_workers'), 'url' => route('tokens.index'), 'icon' => 'person-check'],
            ['label' => 'Total registered workers', 'value' => Worker::count(), 'url' => route('workers.index'), 'icon' => 'person-vcard'],
            ['label' => 'Pending BOESL', 'value' => Token::where('boesl_status', 'pending')->count(), 'url' => route('tokens.index', ['boesl_status' => 'pending']), 'icon' => 'hourglass-split'],
            ['label' => 'Awaiting flight', 'value' => Worker::where('flight_status', 'pending')->count(), 'url' => route('workers.index', ['flight_status' => 'pending']), 'icon' => 'airplane'],
        ];

        return view('dashboard', [
            'metrics' => $metrics,
            'userTokenCounts' => User::query()
                ->withCount(['heldTokens', 'createdTokens'])
                ->where('is_active', true)
                ->where(fn ($query) => $query->has('heldTokens')->orHas('createdTokens'))
                ->orderBy('name')
                ->get(),
            'showRecentActivity' => $showRecentActivity,
            'recent' => $showRecentActivity ? AuditLog::with('user')->latest()->limit(8)->get() : collect(),
        ]);
    }
}
