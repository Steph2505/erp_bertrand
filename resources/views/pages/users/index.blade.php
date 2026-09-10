@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('breadcrumb')<span class="current">Utilisateurs</span>@endsection

@section('content')

@php
$roleDefaultPermsJs = $roleDefaultPerms->toJson();
@endphp

<div x-data="{
    showModal:   false,
    editUser:    null,
    selectedRole: '',
    checkedPerms: [],
    roleDefaults: {{ $roleDefaultPermsJs }},

    openCreate() {
        this.editUser     = null;
        this.selectedRole = '';
        this.checkedPerms = [];
        this.showModal    = true;
    },
    openEdit(user, role, perms) {
        this.editUser     = user;
        this.selectedRole = role;
        this.checkedPerms = perms;
        this.showModal    = true;
    },
    onRoleChange() {
        const defaults = this.roleDefaults[this.selectedRole] || [];
        defaults.forEach(p => { if (!this.checkedPerms.includes(p)) this.checkedPerms.push(p); });
    },
    togglePerm(perm) {
        const idx = this.checkedPerms.indexOf(perm);
        idx === -1 ? this.checkedPerms.push(perm) : this.checkedPerms.splice(idx, 1);
    }
}">

<div class="page-header">
    <div class="page-header__title">
        <h2>Utilisateurs</h2>
        <p>{{ $users->total() }} utilisateur(s) dans le système</p>
    </div>
    <div class="page-header__actions">
        <button @click="openCreate()" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvel utilisateur
        </button>
    </div>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead><tr>
            <th>Utilisateur</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Droits</th>
            <th>Statut</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:#1749B3;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <strong>{{ $user->name }}</strong>
                        </div>
                    </td>
                    <td style="color:#64748B;font-size:13px;">{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge badge--blue">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:300px;">
                            @php
                        $permFr = ['manage products'=>'Produits','manage purchases'=>'Achats','manage sales'=>'Ventes','manage pos'=>'POS','manage stock'=>'Stock','manage customers'=>'Clients','manage suppliers'=>'Fournisseurs','manage expenses'=>'Dépenses','manage accounts'=>'Comptes','view reports'=>'Rapports','manage settings'=>'Paramètres','manage users'=>'Utilisateurs'];
                        @endphp
                        @foreach($user->getAllPermissions() as $perm)
                                <span style="font-size:11px;background:#f1f5f9;color:#475569;border-radius:4px;padding:1px 6px;">
                                    {{ $permFr[$perm->name] ?? $perm->name }}
                                </span>
                            @endforeach
                            @if($user->getAllPermissions()->isEmpty())
                                <span style="font-size:12px;color:#94A3B8;">—</span>
                            @endif
                        </div>
                    </td>
                    <td><span class="badge badge--{{ $user->is_active ? 'green' : 'gray' }}">{{ $user->is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td>
                        <div class="data-table__actions">
                            <button @click="openEdit(
                                    {{ $user->toJson() }},
                                    '{{ $user->roles->first()?->name ?? '' }}',
                                    {{ $user->permissions->pluck('name')->toJson() }}
                                )" class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                            </button>
                            @if($user->id !== auth()->id() && !$user->hasRole('Super Admin'))
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn--danger btn--sm btn--icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucun utilisateur</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} sur {{ $users->total() }}</span>
        {{ $users->withQueryString()->links() }}
    </div>
</div>

{{-- Modal création / édition --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal" style="max-width:640px;width:100%;">
        <div class="modal__header">
            <h3 x-text="editUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form :action="editUser ? '/users/'+editUser.id : '{{ route('users.store') }}'"
              method="POST" class="modal__body" style="max-height:80vh;overflow-y:auto;">
            @csrf
            <template x-if="editUser"><input type="hidden" name="_method" value="PUT"></template>

            {{-- Infos de base --}}
            <div class="form-grid form-grid--2" style="margin-bottom:16px;">
                <div class="form-group">
                    <label>Nom complet <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editUser?.name ?? ''" required>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" :value="editUser?.email ?? ''" required>
                </div>
                <template x-if="!editUser">
                    <div class="form-group">
                        <div style="padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:13px;color:#15803d;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;display:inline;margin-right:4px;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            Un mot de passe sera généré automatiquement et envoyé par email.
                        </div>
                    </div>
                </template>
                <div class="form-group">
                    <label>Rôle <small style="color:#94A3B8;">(suggère des droits)</small></label>
                    <select name="role" class="form-select" x-model="selectedRole" @change="onRoleChange()">
                        <option value="">-- Aucun rôle --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <template x-if="editUser">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" :checked="editUser?.is_active">
                        Compte actif
                    </label>
                </div>
            </template>

            {{-- Droits d'accès --}}
            <div style="border:1.5px solid #e2e8f0;border-radius:10px;padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <h4 style="font-size:13px;font-weight:700;color:#0E1726;">Droits d'accès</h4>
                    <div style="display:flex;gap:8px;">
                        <button type="button" @click="checkedPerms = {{ collect(array_merge(...array_values($permissionGroups)))->toJson() }}"
                                style="font-size:11px;color:#3b82f6;background:none;border:none;cursor:pointer;padding:0;">Tout cocher</button>
                        <span style="color:#e2e8f0;">|</span>
                        <button type="button" @click="checkedPerms = []"
                                style="font-size:11px;color:#94a3b8;background:none;border:none;cursor:pointer;padding:0;">Tout décocher</button>
                    </div>
                </div>

                @foreach($permissionGroups as $group => $perms)
                <div style="margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
                        {{ $group }}
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @php
                        $permLabels = [
                            'manage products'  => 'Produits',
                            'manage purchases' => 'Achats',
                            'manage sales'     => 'Ventes',
                            'manage pos'       => 'Point de vente',
                            'manage stock'     => 'Stock',
                            'manage customers' => 'Clients',
                            'manage suppliers' => 'Fournisseurs',
                            'manage expenses'  => 'Dépenses',
                            'manage accounts'  => 'Comptes',
                            'view reports'     => 'Rapports',
                            'manage settings'  => 'Paramètres',
                            'manage users'     => 'Utilisateurs',
                        ];
                        @endphp
                        @foreach($perms as $perm)
                        @php $label = $permLabels[$perm] ?? ucfirst($perm); @endphp
                        <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:13px;transition:all .15s;"
                               :style="checkedPerms.includes('{{ $perm }}') ? 'border-color:#3b82f6;background:#eff6ff;color:#3b82f6;font-weight:600;' : ''">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm }}"
                                   :checked="checkedPerms.includes('{{ $perm }}')"
                                   @change="togglePerm('{{ $perm }}')"
                                   style="accent-color:#3b82f6;">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="modal__footer" style="padding:0;border:none;margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
