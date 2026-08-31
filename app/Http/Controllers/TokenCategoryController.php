<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenCategoryRequest;
use App\Models\TokenCategory;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TokenCategoryController extends Controller
{
    public function index(): View
    {
        $categories = TokenCategory::query()
            ->withCount('tokens')
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(15);

        return view('token-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('token-categories.form', ['tokenCategory' => new TokenCategory]);
    }

    public function store(TokenCategoryRequest $request): RedirectResponse
    {
        $tokenCategory = TokenCategory::create($request->safe()->except('is_active') + [
            'is_active' => $request->boolean('is_active', true),
        ]);
        AuditService::record('create', 'token-categories', $tokenCategory, [], $tokenCategory->toArray());

        return redirect()->route('token-categories.index')->with('success', 'Token category created.');
    }

    public function edit(TokenCategory $tokenCategory): View
    {
        return view('token-categories.form', compact('tokenCategory'));
    }

    public function update(TokenCategoryRequest $request, TokenCategory $tokenCategory): RedirectResponse
    {
        $old = $tokenCategory->toArray();
        $tokenCategory->update($request->safe()->except('is_active') + [
            'is_active' => $request->boolean('is_active'),
        ]);
        AuditService::record('update', 'token-categories', $tokenCategory, $old, $tokenCategory->fresh()->toArray());

        return redirect()->route('token-categories.index')->with('success', 'Token category updated.');
    }

    public function destroy(TokenCategory $tokenCategory): RedirectResponse
    {
        $old = $tokenCategory->toArray();
        $tokenCategory->update(['is_active' => false]);
        AuditService::record('deactivate', 'token-categories', $tokenCategory, $old, $tokenCategory->fresh()->toArray());

        return back()->with('success', 'Token category deactivated.');
    }
}
