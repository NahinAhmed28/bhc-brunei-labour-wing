<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use App\Models\Agency;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function index(Request $r)
    {
        $items = Agency::when($r->q, fn ($q, $v) => $q->where('name', 'like', "%$v%"))->orderBy('name')->paginate(15)->withQueryString();

        return view('masters.index', ['items' => $items, 'type' => 'agencies', 'title' => 'Agencies List']);
    }

    public function create()
    {
        return view('masters.form', ['item' => new Agency, 'type' => 'agencies', 'title' => 'Create agency']);
    }

    public function store(MasterDataRequest $r)
    {
        $item = Agency::create($r->validated() + ['created_by' => $r->user()->id, 'updated_by' => $r->user()->id, 'is_active' => $r->boolean('is_active', true)]);
        AuditService::record('create', 'agencies', $item, [], $item->toArray());

        return redirect()->route('agencies.index')->with('success', 'Agency created.');
    }

    public function edit(Agency $agency)
    {
        return view('masters.form', ['item' => $agency, 'type' => 'agencies', 'title' => 'Edit agency']);
    }

    public function update(MasterDataRequest $r, Agency $agency)
    {
        $old = $agency->toArray();
        $agency->update($r->validated() + ['updated_by' => $r->user()->id, 'is_active' => $r->boolean('is_active')]);
        AuditService::record('update', 'agencies', $agency, $old, $agency->fresh()->toArray());

        return redirect()->route('agencies.index')->with('success', 'Agency updated.');
    }

    public function destroy(Agency $agency)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $agency->update(['is_active' => false]);
        $agency->delete();
        AuditService::record('deactivate', 'agencies', $agency);

        return back()->with('success','Agency deactivated.');
    }
}
