@extends('layouts.app')
@section('title', 'Comptes de paiement')
@section('breadcrumb')<span class="current">Comptes</span>@endsection

@section('content')
<div x-data="{ showModal: false, editAccount: null }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Comptes de paiement</h2>
        <p>Gestion des caisses et comptes bancaires</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true; editAccount = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau compte
        </button>
    </div>
</div>

{{-- Stat cards --}}
<div class="stat-grid mb-24">
    @foreach($accounts as $account)
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">{{ $account->name }}</div>
            <div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($account->current_balance) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">
                {{ match($account->type) { 'cash' => 'Caisse', 'bank' => 'Banque', 'mobile_money' => 'Mobile Money', default => $account->type } }}
                @if($account->is_default) &nbsp;<span class="badge badge--green text-xs">Défaut</span> @endif
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $account->type === 'cash' ? 'green' : ($account->type === 'bank' ? 'blue' : 'yellow') }}">
            @if($account->type === 'cash')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Tous les comptes</strong>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Nom</th>
            <th>Type</th>
            <th>N° Compte</th>
            <th>Solde initial</th>
            <th>Solde actuel</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td>
                        <strong>{{ $account->name }}</strong>
                        @if($account->is_default) <span class="badge badge--green text-xs" style="margin-left:4px;">Défaut</span> @endif
                    </td>
                    <td>{{ match($account->type) { 'cash' => 'Caisse', 'bank' => 'Banque', 'mobile_money' => 'Mobile Money', default => $account->type } }}</td>
                    <td>{{ $account->account_number ?? '—' }}</td>
                    <td>{{ \App\Helpers\FormatHelper::money($account->opening_balance) }}</td>
                    <td><strong>{{ \App\Helpers\FormatHelper::money($account->current_balance) }}</strong></td>
                    <td>{!! \App\Helpers\FormatHelper::statusBadge($account->is_active ? 'completed' : 'cancelled') !!}</td>
                    <td>
                        <div class="data-table__actions">
                            <a href="{{ route('payment-accounts.show', $account) }}"
                               class="btn btn--ghost btn--sm btn--icon" title="Voir le journal">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            <button @click="editAccount = {{ $account->toJson() }}; showModal = true"
                                class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('payment-accounts.destroy', $account) }}" @submit.prevent="window.confirmDialog('Supprimer ce compte ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
                                @csrf @method('DELETE')
                                <button class="btn btn--danger btn--sm btn--icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="account-table__empty">Aucun compte configuré</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--md">
        <div class="modal__header">
            <h3 x-text="editAccount ? 'Modifier le compte' : 'Nouveau compte'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="editAccount ? '/payment-accounts/'+editAccount.id : '{{ route('payment-accounts.store') }}'" method="POST" class="modal__body">
            @csrf
            <template x-if="editAccount"><input type="hidden" name="_method" value="PUT"></template>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editAccount ? editAccount.name : ''" required>
                </div>
                <div class="form-group">
                    <label>Type <span class="required">*</span></label>
                    <select name="type" class="form-select">
                        <option value="cash" :selected="editAccount && editAccount.type === 'cash'">Caisse</option>
                        <option value="bank" :selected="editAccount && editAccount.type === 'bank'">Banque</option>
                        <option value="mobile_money" :selected="editAccount && editAccount.type === 'mobile_money'">Mobile Money</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Solde initial ({{ $currency }})</label>
                    <input type="number" name="opening_balance" min="0" class="form-control" :value="editAccount ? editAccount.opening_balance : '0'">
                </div>
                <div class="form-group">
                    <label>N° de compte</label>
                    <input type="text" name="account_number" class="form-control" :value="editAccount ? editAccount.account_number : ''">
                </div>
            </div>
            <label class="form-checkbox">
                <input type="checkbox" name="is_default" value="1" :checked="editAccount && editAccount.is_default">
                Compte par défaut
            </label>
            <div class="modal-footer-std">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
