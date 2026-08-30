<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $r)
    {
        $items = Company::when($r->q, fn ($q, $v) => $q->where('name', 'like', "%$v%"))->orderBy('name')->paginate(15)->withQueryString();

        return view('masters.index', ['items' => $items, 'type' => 'companies', 'title' => 'Company List']);
    }

    public function create()
    {
        return view('masters.form', ['item' => new Company, 'type' => 'companies', 'title' => 'Create company']);
    }

    public function store(MasterDataRequest $r)
    {
        $item = Company::create($r->validated() + ['created_by' => $r->user()->id, 'updated_by' => $r->user()->id, 'is_active' => $r->boolean('is_active', true)]);
        AuditService::record('create', 'companies', $item, [], $item->toArray());

        return redirect()->route('companies.index')->with('success', 'Company created.');
    }

    public function edit(Company $company)
    {
        return view('masters.form', ['item' => $company, 'type' => 'companies', 'title' => 'Edit company']);
    }

    public function update(MasterDataRequest $r, Company $company)
    {
        $old = $company->toArray();
        $company->update($r->validated() + ['updated_by' => $r->user()->id, 'is_active' => $r->boolean('is_active')]);
        AuditService::record('update', 'companies', $company, $old, $company->fresh()->toArray());

        return redirect()->route('companies.index')->with('success', 'Company updated.');
    }

    public function destroy(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $company->update(['is_active' => false]);
        $company->delete();
        AuditService::record('deactivate', 'companies', $company);

        return back()->with('success','Company deactivated.');
    }
}
