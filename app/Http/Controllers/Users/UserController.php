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
use Throwable;

class UserController extends Controller
{
    private const PERMISSION_GROUPS = [
        'Commerce'       => ['manage products', 'manage purchases', 'manage sales', 'manage pos'],
        'Stock'          => ['manage stock'],
        'Contacts'       => ['manage customers', 'manage suppliers'],
        'Finance'        => ['manage expenses', 'manage accounts', 'view reports'],
        'Administration' => ['manage settings', 'manage users'],
    ];

    public function index(Request $request): View|RedirectResponse
    {
        try {
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
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des utilisateurs');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
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

        try {
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
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de l\'utilisateur', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de l\'utilisateur.');
        }

        try {
            $user->notify(new UserCredentials($plainPassword));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'envoi des identifiants par email', ['user_id' => $user->id]);
        }

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

        try {
            $user->update([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            $user->syncRoles($data['role'] ? [$data['role']] : []);
            $user->syncPermissions($data['permissions'] ?? []);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de l\'utilisateur', ['user_id' => $user->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'utilisateur.');
        }

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            $user->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de l\'utilisateur', ['user_id' => $user->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'utilisateur.');
        }

        return back()->with('success', 'Utilisateur supprimé.');
    }

    public function profile(): View|RedirectResponse
    {
        try {
            return view('pages.users.profile', ['user' => auth()->user()]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du profil utilisateur');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
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

        try {
            $user->update($data);

            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8|confirmed']);
                $user->update(['password' => Hash::make($request->password)]);
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du profil', ['user_id' => $user->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du profil.');
        }

        return back()->with('success', 'Profil mis à jour.');
    }
}
