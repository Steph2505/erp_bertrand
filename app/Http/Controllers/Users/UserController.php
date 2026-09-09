<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const PERMISSION_GROUPS = [
        'Commerce'       => ['manage products', 'manage purchases', 'manage sales', 'manage pos'],
        'Stock'          => ['manage stock'],
        'Contacts'       => ['manage customers', 'manage suppliers'],
        'Finance'        => ['manage expenses', 'manage accounts', 'view reports'],
        'Administration' => ['manage settings', 'manage users'],
    ];

    public function index(Request $request): View
    {
        $users = User::with(['roles', 'permissions'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
            ->latest()
            ->paginate(20);

        $roles            = Role::where('name', '!=', 'Super Admin')->orderBy('name')->get();
        $permissionGroups = self::PERMISSION_GROUPS;
        $roleDefaultPerms = Role::with('permissions')->get()->mapWithKeys(fn($r) => [
            $r->name => $r->permissions->pluck('name')->values(),
        ]);

        return view('pages.users.index', compact('users', 'roles', 'permissionGroups', 'roleDefaultPerms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'email'         => 'required|email|unique:users,email',
            'role'          => 'nullable|exists:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $plainPassword = Str::random(10);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($plainPassword),
            'is_active' => true,
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        $user->syncPermissions($data['permissions'] ?? []);

        try {
            $user->notify(new UserCredentials($plainPassword));
        } catch (\Throwable) {}

        return back()->with('success', 'Utilisateur créé. Les identifiants ont été envoyés par email.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'nullable|exists:roles,name',
            'is_active'     => 'boolean',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        $user->syncRoles($data['role'] ? [$data['role']] : []);
        $user->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        $user->delete();
        return back()->with('success', 'Utilisateur supprimé.');
    }

    public function profile(): View
    {
        return view('pages.users.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Profil mis à jour.');
    }
}
