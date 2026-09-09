@extends('layouts.app')
@section('title', 'Taux de TVA')
@section('breadcrumb')
    <a href="{{ route('settings.company') }}">Paramètres</a>
    <span class="sep">/</span><span class="current">Taux de TVA</span>
@endsection

@section('content')
<div x-data="taxRates()" x-init="init()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Taux de TVA</h2>
        <p>Taux disponibles lors de la création de produits et factures</p>
    </div>
    <div class="page-header__actions">
        <button type="button" @click="addRate()" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Ajouter un taux
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert--success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="settings-grid--sidebar">

    <form method="POST" action="{{ route('settings.tax-rates.save') }}">
        @csrf

        <div class="table-wrapper">
            <div class="table-wrapper__header">
                <strong>Taux configurés</strong>
                <span class="settings-tax__count" x-text="rates.length + ' taux'"></span>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Taux (%)</th>
                    <th>Libellé</th>
                    <th class="th-center">Par défaut</th>
                    <th class="th-right">Supprimer</th>
                </tr></thead>
                <tbody>
                    <template x-for="(rate, i) in rates" :key="i">
                        <tr>
                            <td class="settings-tax__col-rate">
                                <input type="number" :name="'rates['+i+'][rate]'"
                                       x-model="rate.rate" min="0" max="100" step="0.01"
                                       class="form-control settings-tax__rate-input" required>
                            </td>
                            <td>
                                <input type="text" :name="'rates['+i+'][label]'"
                                       x-model="rate.label" class="form-control" required
                                       placeholder="Ex : TVA normale (18%)">
                            </td>
                            <td style="text-align:center;">
                                <input type="radio" name="default_index" :value="i"
                                       :checked="rate.is_default"
                                       @change="setDefault(i)"
                                       class="settings-tax__radio">
                                <input type="hidden" :name="'rates['+i+'][is_default]'"
                                       :value="rate.is_default ? '1' : '0'">
                            </td>
                            <td style="text-align:right;">
                                <button type="button" @click="removeRate(i)"
                                        class="btn btn--danger btn--sm btn--icon"
                                        :disabled="rates.length <= 1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="settings-tax__table-actions">
                <button type="button" @click="addRate()" class="btn btn--ghost">+ Ajouter un taux</button>
                <button type="submit" class="btn btn--primary">Sauvegarder</button>
            </div>
        </div>
    </form>

    <div class="settings-tax__sidebar">
        <div class="card settings-tax__info-card">
            <h3 class="settings-tax__info-title">Guide TVA</h3>
            <div class="settings-tax__info-list">
                <div class="settings-tax__info-item">
                    <span class="badge badge--green settings-tax__info-badge">0%</span>
                    <span>Produits exonérés : pain, farine, médicaments…</span>
                </div>
                <div class="settings-tax__info-item">
                    <span class="badge badge--yellow settings-tax__info-badge">10%</span>
                    <span>Taux réduit : restauration, hôtellerie, transport…</span>
                </div>
                <div class="settings-tax__info-item">
                    <span class="badge badge--blue settings-tax__info-badge">18%</span>
                    <span>Taux normal UEMOA : produits et services standard</span>
                </div>
            </div>
        </div>

        <div class="card settings-tax__default-card">
            <h3 class="settings-tax__default-title">Taux par défaut</h3>
            <p class="settings-tax__default-text">
                Le taux marqué <strong>Par défaut</strong> sera pré-sélectionné lors de la création de nouveaux produits et appliqué au rapport fiscal.
            </p>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function taxRates() {
    return {
        rates: [],
        init() {
            this.rates = @json($rates);
        },
        addRate() {
            this.rates.push({ rate: 0, label: '', is_default: false });
        },
        removeRate(i) {
            if (this.rates.length <= 1) return;
            const wasDefault = this.rates[i].is_default;
            this.rates.splice(i, 1);
            if (wasDefault && this.rates.length > 0) {
                this.rates[0].is_default = true;
            }
        },
        setDefault(i) {
            this.rates.forEach((r, idx) => r.is_default = idx === i);
        },
    };
}
</script>
@endpush
