@props([
    'customerGroups',
    'nameModel',
    'groupModel',
    'creating',
    'error',
    'onSubmit',
])

<div class="form-group" style="margin-bottom:8px;">
    <label style="font-size:12px;">Nom <span class="required">*</span></label>
    <input type="text" x-model="{{ $nameModel }}" @keydown.enter.prevent="{{ $onSubmit }}"
           class="form-control" placeholder="Nom du client" style="font-size:13px;">
</div>
<div class="form-group" style="margin-bottom:10px;">
    <label style="font-size:12px;">Type</label>
    <select x-model.number="{{ $groupModel }}" class="form-select" style="font-size:13px;">
        @foreach($customerGroups as $group)
            <option value="{{ $group->id }}">{{ $group->name }}</option>
        @endforeach
    </select>
</div>
<div style="display:flex;gap:8px;align-items:center;">
    <button type="button" @click="{{ $onSubmit }}"
            class="btn btn--primary btn--sm" style="flex:1;justify-content:center;"
            :disabled="{{ $creating }}||!{{ $nameModel }}.trim()">
        <span x-show="!{{ $creating }}">Créer &amp; sélectionner</span>
        <span x-show="{{ $creating }}">...</span>
    </button>
</div>
<p x-show="{{ $error }}" x-text="{{ $error }}" style="color:#ef4444;font-size:12px;margin-top:6px;margin-bottom:0;"></p>
