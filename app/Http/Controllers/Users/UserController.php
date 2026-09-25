<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    private const PERMISSION_GROUPS = [
        'Commerce'       => ['manage products', 'manage purchases', 'manage pos'],
        'Stock'          => ['manage stock'],
        'Contacts'       => ['manage customers', 'manage suppliers'],
        'Finance'        => ['manage expenses', 'manage accounts', 'view reports', 'view financial dashboard'],
        'Administration' => ['manage settings', 'manage users'],
        'Notifications'  => ['receive notifications'],
    ];

    private const PERMISSION_LABELS = [
        'manage products'       => 'Articles',
        'manage purchases'      => 'Achats',
        'manage pos'            => 'Point de vente',
        'manage stock'          => 'Stock',
        'manage customers'      => 'Clients',
        'manage suppliers'      => 'Fournisseurs',
        'manage expenses'       => 'Dépenses',
        'manage accounts'       => 'Comptes',
        'view reports'          => 'Rapports',
        'view financial dashboard' => 'Dashboard financier',
        'manage settings'       => 'Paramètres',
        'manage users'          => 'Utilisateurs',
        'receive notifications' => 'Recevoir les notifications',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $users = User::with(['roles', 'permissions'])
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))
                ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
                ->latest()
                ->paginate(20);

            $roles             = Role::where('name', '!=', 'Super Admin')->orderBy('name')->get();
            $permissionGroups  = self::PERMISSION_GROUPS;
            $permissionLabels  = self::PERMISSION_LABELS;
            $roleDefaultPerms  = Role::with('permissions')->get()->mapWithKeys(fn($r) => [
                $r->name => $r->permissions->pluck('name')->values(),
            ]);

            return view('pages.users.index', compact('users', 'roles', 'permissionGroups', 'permissionLabels', 'roleDefaultPerms'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des utilisateurs');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function show(User $user): View|RedirectResponse
    {
        try {
            $user->load(['roles', 'permissions']);

            $permissionGroups = self::PERMISSION_GROUPS;
            $permissionLabels = self::PERMISSION_LABELS;

            return view('pages.users.show', compact('user', 'permissionGroups', 'permissionLabels'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la fiche utilisateur', ['user_id' => $user->id]);
            return redirect()->route('users.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'email'         => 'required|email|unique:users,email',
            'role'          => 'required|exists:roles,name',
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

            $user->assignRole($data['role']);

            $user->syncPermissions($data['permissions'] ?? []);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de l\'utilisateur', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de l\'utilisateur.');
        }

        try {
            $user->notify(new UserCredentials($plainPassword));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'envoi des identifiants par email', ['user_id' => $user->id]);
            return back()->with('warning', "Utilisateur créé, mais l'email n'a pas pu être envoyé (vérifier la configuration mail). Mot de passe temporaire : {$plainPassword}");
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

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        try {
            $user->update(['is_active' => !$user->is_active]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du changement de statut de l\'utilisateur', ['user_id' => $user->id]);
            return back()->with('error', 'Une erreur est survenue lors du changement de statut.');
        }

        $msg = $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé. Il n\'a plus accès au système.';
        return back()->with('success', $msg);
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

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => 'required|current_password',
                'password'         => ['confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            ], [
                'current_password.required'         => 'Veuillez saisir votre mot de passe actuel.',
                'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
                'password.min'                       => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
                'password.mixed'                     => 'Le nouveau mot de passe doit contenir au moins une majuscule et une minuscule.',
                'password.numbers'                   => 'Le nouveau mot de passe doit contenir au moins un chiffre.',
                'password.symbols'                   => 'Le nouveau mot de passe doit contenir au moins un caractère spécial.',
                'password.confirmed'                 => 'La confirmation du mot de passe ne correspond pas.',
            ]);
        }

        try {
            $user->update($data);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du profil', ['user_id' => $user->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du profil.');
        }

        return back()->with('success', 'Profil mis à jour.');
    }
}
