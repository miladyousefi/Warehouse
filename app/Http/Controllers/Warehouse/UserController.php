<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('users.view');

        $users = User::query()
            ->with('roles:id,name')
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('warehouse/users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('users.create');

        $roles = Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('warehouse/users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('users.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (! empty($validated['role_ids'])) {
            $roles = Role::whereIn('id', $validated['role_ids'])->pluck('name');
            $user->syncRoles($roles);
        }

        return redirect()->route('warehouse.users.index')->with('success', __('User created.'));
    }

    public function edit(User $user): Response
    {
        $this->authorize('users.edit');

        $user->load('roles:id,name');
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('warehouse/users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (isset($validated['password']) && $validated['password']) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if (array_key_exists('role_ids', $validated)) {
            $roles = empty($validated['role_ids'])
                ? []
                : Role::whereIn('id', $validated['role_ids'])->pluck('name');
            $user->syncRoles($roles);
        }

        return redirect()->route('warehouse.users.index')->with('success', __('User updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('users.delete');

        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete yourself.'));
        }

        $user->delete();

        return redirect()->route('warehouse.users.index')->with('success', __('User deleted.'));
    }
}
