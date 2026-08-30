<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::with('role')->orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User, 'roles' => Role::orderBy('label')->get()]);
    }

    public function store(Request $r)
    {
        $data = $this->validateUser($r);
        $user = User::create($data + ['is_active' => $r->boolean('is_active')]);
        AuditService::record('create', 'users', $user, [], $user->only('name', 'email', 'role_id', 'is_active'));

        return redirect()->route('users.index')->with('success', 'User account created.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'roles' => Role::orderBy('label')->get()]);
    }

    public function update(Request $r, User $user)
    {
        $data = $this->validateUser($r, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }$old = $user->only('name', 'email', 'role_id', 'is_active');
        $user->update($data + ['is_active' => $r->boolean('is_active')]);
        AuditService::record('update', 'users', $user, $old, $user->fresh()->only('name', 'email', 'role_id', 'is_active'));

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    private function validateUser(Request $r, ?User $user = null): array
    {
        return $r->validate(['name' => 'required|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)], 'role_id' => 'required|exists:roles,id', 'password' => [$user ? 'nullable' : 'required', 'string', 'min:10', 'confirmed'], 'is_active' => 'nullable|boolean']);
    }
}
