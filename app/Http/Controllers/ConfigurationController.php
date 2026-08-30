<?php

namespace App\Http\Controllers;

use App\Models\Desk;
use App\Models\TokenCategory;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index()
    {
        return view('configuration', ['categories' => TokenCategory::orderBy('display_order')->get(), 'desks' => Desk::orderBy('display_order')->get()]);
    }

    public function category(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:255', 'code' => 'required|max:50|unique:token_categories,code', 'description' => 'nullable|max:1000', 'default_fee' => 'nullable|numeric|min:0', 'display_order' => 'nullable|integer|min:0']);
        $item = TokenCategory::create($data + ['is_active' => true]);
        AuditService::record('create', 'token-categories', $item);

        return back()->with('success', 'Token category added.');
    }

    public function desk(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:255', 'code' => 'required|max:50|unique:desks,code', 'description' => 'nullable|max:1000', 'display_order' => 'nullable|integer|min:0']);
        $item = Desk::create($data + ['is_active' => true]);
        AuditService::record('create', 'desks', $item);

        return back()->with('success', 'Desk added.');
    }
}
