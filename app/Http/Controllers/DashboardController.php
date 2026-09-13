<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Token;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ];

        $userCounts = User::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->withCount('createdTokens')
            ->addSelect([
                'desk_tokens_count' => Token::query()->selectRaw('count(*)')->onDeskOf(DB::raw('users.id')),
            ])
            ->withCasts(['desk_tokens_count' => 'integer'])
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'metrics' => $metrics,
            'userTokenCounts' => $userCounts->filter(fn (User $user): bool => $user->created_tokens_count > 0),
            'deskTokenCounts' => $userCounts->filter(fn (User $user): bool => $user->desk_tokens_count > 0),
            'showRecentActivity' => $showRecentActivity,
            'recent' => $showRecentActivity ? AuditLog::with('user')->latest()->limit(8)->get() : collect(),
        ]);
    }
}
