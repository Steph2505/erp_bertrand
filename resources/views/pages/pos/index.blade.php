@extends('layouts.app')
@section('title', 'Point de vente')
@section('breadcrumb')<span class="current">POS</span>@endsection


@section('content')
{{-- Bannière session + modal ouverture --}}
<div x-data="{ openSessionModal: {{ $activeSession ? 'false' : 'true' }} }">

@if($activeSession)
<div class="pos-session-banner">
    <div class="pos-session-banner__info">
        <strong>{{ $activeSession->caisse?->name ?? 'Caisse' }}</strong> — ouverte depuis {{ $activeSession->opened_at->format('H:i') }}
        @if($activeSession->warehouse) — {{ $activeSession->warehouse->name }} @endif
        — Ventes : <strong
            x-data="{ total: {{ $activeSession->total_sales }} }"
            @pos-sale-completed.window="total += $event.detail.amount"
            x-text="new Intl.NumberFormat('fr-FR').format(Math.round(total)) + ' ' + window.CURRENCY"></strong>
    </div>
    <div class="pos-session-banner__actions">
        <a href="{{ route('pos.sessions.show', $activeSession) }}" class="btn btn--ghost btn--sm">Voir détail</a>
        <button @click="document.getElementById('close-session-panel').classList.toggle('hidden')" class="btn btn--warning btn--sm">Clôturer la caisse</button>
    </div>
</div>
<div id="close-session-panel" class="pos-close-panel hidden">
    <form method="POST" action="{{ route('pos.sessions.close', $activeSession) }}" class="pos-close-panel__form">
        @csrf
        <span class="pos-close-panel__label">Clôturer la caisse :</span>
        <input type="number" name="closing_balance" step="1" min="0" class="form-control pos-close-panel__input-balance" placeholder="Montant compté" required>
        <input type="text" name="note" class="form-control pos-close-panel__input-note" placeholder="Note (optionnel)">
        <button type="submit" class="btn btn--danger btn--sm" onclick="return confirm('Confirmer la clôture ?')">Confirmer</button>
        <button type="button" class="btn btn--ghost btn--sm" onclick="document.getElementById('close-session-panel').classList.add('hidden')">Annuler</button>
    </form>
</div>
@endif

{{-- Modal ouverture caisse --}}
<div class="modal-overlay" x-show="openSessionModal" x-cloak x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Ouvrir la caisse</h3>
        </div>
        <form method="POST" action="{{ route('pos.sessions.open') }}" class="modal__body" @keydown.escape.prevent
              x-data="{
                  caisseSearch:'', caisseOpen:false, selectedCaisse:null,
                  caisses: {{ Js::from($caisses) }},
                  showCreate: false,
                  newName: '',
                  creating: false,
                  createError: '',
                  get filteredCaisses() {
                      const q = this.caisseSearch.toLowerCase();
                      return this.caisses.filter(c => c.name.toLowerCase().includes(q));
                  },
                  pickCaisse(c) {
                      this.selectedCaisse = c;
                      this.caisseSearch   = c.name;
                      this.caisseOpen     = false;
                  },
                  async quickCreate() {
                      if (!this.newName.trim()) return;
                      this.creating     = true;
                      this.createError  = '';
                      try {
                          const res  = await fetch('{{ route('pos.caisses.store') }}', {
                              method: 'POST',
                              headers: {
                                  'Content-Type': 'application/json',
                                  'Accept':        'application/json',
                                  'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                              },
                              body: JSON.stringify({ name: this.newName }),
                          });
                          const data = await res.json();
                          if (data.success) {
                              this.caisses.push(data.caisse);
                              this.pickCaisse(data.caisse);
                              this.showCreate = false;
                              this.newName    = '';
                              window.toast('Caisse créée avec succès.', 'success');
                          } else {
                              this.createError = data.message || 'Erreur lors de la création.';
                          }
                      } catch (e) {
                          this.createError = 'Erreur réseau.';
                      } finally {
                          this.creating = false;
                      }
                  }
              }">
            @csrf
            <p style="color:#64748B;font-size:13px;margin-bottom:20px;">Sélectionnez une caisse et entrez le fond de départ.</p>

            {{-- Champ caisse --}}
            <div class="form-group" @click.outside="caisseOpen=false; caisseSearch=selectedCaisse?.name??''">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <label style="margin-bottom:0;">Caisse <span class="required">*</span></label>
                    <button type="button"
                            @click="showCreate=!showCreate; createError=''"
                            style="font-size:12px;color:#1749B3;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;font-weight:600;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span x-text="showCreate ? 'Annuler' : 'Créer une caisse'"></span>
                    </button>
                </div>

                {{-- Mini formulaire création rapide --}}
                <div x-show="showCreate" x-collapse style="margin-bottom:10px;padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                    <div style="display:flex;gap:8px;align-items:flex-end;">
                        <div class="form-group" style="flex:1;margin-bottom:0;">
                            <label style="font-size:12px;">Nom de la nouvelle caisse</label>
                            <input type="text" x-model="newName"
                                   @keydown.enter.prevent="quickCreate()"
                                   @keydown.escape="showCreate=false"
                                   class="form-control" placeholder="Ex : Caisse 2" x-ref="newNameInput">
                        </div>
                        <button type="button" @click="quickCreate()"
                                class="btn btn--primary btn--sm"
                                :disabled="creating || !newName.trim()">
                            <span x-show="!creating">Créer & sélectionner</span>
                            <span x-show="creating">...</span>
                        </button>
                    </div>
                    <p x-show="createError" x-text="createError" style="color:#ef4444;font-size:12px;margin-top:6px;margin-bottom:0;"></p>
                </div>

                {{-- Autocomplete --}}
                <div class="col-relative" x-show="!showCreate">
                    <input type="text" x-model="caisseSearch"
                           @focus="caisseSearch=''; caisseOpen=true" @input="caisseOpen=true"
                           placeholder="Choisir une caisse..." class="form-control" autocomplete="off">
                    <input type="hidden" name="caisse_id" :value="selectedCaisse?.id??''" required>
                    <div x-show="caisseOpen" x-transition class="ac-dropdown">
                        <template x-if="filteredCaisses.length === 0">
                            <div class="ac-option__empty">Aucune caisse — créez-en une ci-dessus</div>
                        </template>
                        <template x-for="c in filteredCaisses" :key="c.id">
                            <div @mousedown.prevent="pickCaisse(c)"
                                 class="ac-option"
                                 :style="selectedCaisse?.id===c.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                 @mouseover="$el.style.background='#f8fafc'"
                                 @mouseout="$el.style.background=selectedCaisse?.id===c.id?'#eff6ff':'white'">
                                <span x-text="c.name"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Caisse sélectionnée (badge) --}}
                <div x-show="selectedCaisse && !showCreate" style="margin-top:6px;display:flex;align-items:center;gap:6px;">
                    <span style="font-size:12px;color:#64748B;">Sélectionnée :</span>
                    <span class="badge badge--green" x-text="selectedCaisse?.name"></span>
                    <button type="button" @click="selectedCaisse=null;caisseSearch=''"
                            style="font-size:11px;color:#94A3B8;background:none;border:none;cursor:pointer;padding:0;">✕</button>
                </div>
            </div>

            <div class="form-group">
                <label>Fond d'ouverture ({{ $currency }}) <span class="required">*</span></label>
                <input type="number" name="opening_balance" step="1" min="0" value="0" class="form-control input--payment-amount" required>
            </div>
            <div class="form-group">
                <label>Entrepôt <span class="required">*</span></label>
                <select name="warehouse_id" class="form-select" required>
                    <option value="">-- Sélectionner --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $wh->id === $defaultWarehouseId ? 'selected' : '' }}>
                            {{ $wh->name }}{{ $wh->id === $defaultWarehouseId ? ' (défaut)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="modal-footer-std" style="gap:8px;">
                <a href="{{ route('pos.caisses.index') }}" class="btn btn--ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                    Liste des caisses
                </a>
                <button type="submit" class="btn btn--primary" style="flex:1;justify-content:center;" :disabled="!selectedCaisse">
                    Ouvrir la caisse
                </button>
            </div>
        </form>
    </div>
</div>

</div>

<div x-data="posApp()" x-init="init()" class="pos-wrapper {{ $activeSession ? 'pos-wrapper--with-session' : '' }}">

    {{-- Catalogue --}}
    <div class="pos-catalog">
        <div class="pos-catalog__header">
            <div class="pos-catalog__search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" x-model="search" placeholder="Rechercher un produit ou pack...">
            </div>
            {{-- Client + raccourci création --}}
            <div style="min-width:200px;position:relative;" @click.outside="customerOpen=false; showCreateCustomerPOS=false; customerSearch=customers.find(c=>c.id===customerSelect)?.name??''">

                {{-- Toggle création / recherche --}}
                <div style="display:flex;gap:6px;align-items:center;margin-bottom:4px;">
                    <div class="col-relative" style="flex:1;">
                        <input type="text" x-model="customerSearch"
                               @focus="customerSearch=''; customerOpen=true; showCreateCustomerPOS=false" @input="customerOpen=true"
                               placeholder="Client comptoir..."
                               class="form-control" style="font-size:13px;" autocomplete="off">
                        <div x-show="customerOpen && !showCreateCustomerPOS" x-transition class="ac-dropdown" style="width:240px;right:0;">
                            <div @mousedown.prevent="customerSelect=''; customerSearch=''; customerOpen=false"
                                 class="ac-option ac-option--separator"
                                 :style="!customerSelect?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                 @mouseover="$el.style.background='#f8fafc'"
                                 @mouseout="$el.style.background=!customerSelect?'#eff6ff':'white'">
                                Comptoir
                            </div>
                            <template x-for="c in customers.filter(c=>c.name.toLowerCase().includes(customerSearch.toLowerCase()))" :key="c.id">
                                <div @mousedown.prevent="customerSelect=c.id; customerSearch=c.name; customerOpen=false"
                                     class="ac-option"
                                     :style="customerSelect===c.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'"
                                     @mouseout="$el.style.background=customerSelect===c.id?'#eff6ff':'white'">
                                    <span x-text="c.name"></span>
                                </div>
                            </template>
                            <template x-if="customers.filter(c=>c.name.toLowerCase().includes(customerSearch.toLowerCase())).length===0">
                                <div class="ac-option__empty">Aucun client trouvé</div>
                            </template>
                        </div>
                    </div>
                    <button type="button" @click.stop="showCreateCustomerPOS=!showCreateCustomerPOS; customerOpen=false; createCustomerErrorPOS=''"
                            style="background:#f0fdf4;border:1.5px solid #bbf7d0;color:#15803d;border-radius:8px;padding:7px 10px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                            :title="showCreateCustomerPOS ? 'Annuler' : 'Nouveau client'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:14px;height:14px;" x-show="!showCreateCustomerPOS"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:14px;height:14px;" x-show="showCreateCustomerPOS"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Mini form POS --}}
                <div x-show="showCreateCustomerPOS" x-collapse
                     style="position:absolute;top:calc(100% + 6px);right:0;z-index:9999;width:280px;padding:12px;background:white;border:1.5px solid #bbf7d0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);">
                    <p style="font-size:12px;font-weight:600;color:#15803d;margin-bottom:10px;">Nouveau client</p>
                    <div class="form-group" style="margin-bottom:8px;">
                        <label style="font-size:12px;">Nom <span class="required">*</span></label>
                        <input type="text" x-model="newCustomerNamePOS" @keydown.enter.prevent="quickCreateCustomerPOS()" class="form-control" placeholder="Nom du client" style="font-size:13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-size:12px;">Téléphone</label>
                        <input type="text" x-model="newCustomerPhonePOS" @keydown.enter.prevent="quickCreateCustomerPOS()" class="form-control" placeholder="Optionnel" style="font-size:13px;">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" @click="quickCreateCustomerPOS()"
                                class="btn btn--primary btn--sm" style="flex:1;justify-content:center;"
                                :disabled="creatingCustomerPOS||!newCustomerNamePOS.trim()">
                            <span x-show="!creatingCustomerPOS">Créer & sélectionner</span>
                            <span x-show="creatingCustomerPOS">...</span>
                        </button>
                    </div>
                    <p x-show="createCustomerErrorPOS" x-text="createCustomerErrorPOS" style="color:#ef4444;font-size:12px;margin-top:6px;margin-bottom:0;"></p>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="pos-catalog__categories">
            <button @click="filterCat=''"
                    class="pos-catalog__category-btn"
                    :class="{'pos-catalog__category-btn--active': filterCat===''}">
                Tous
            </button>
            <button @click="filterCat='packable'"
                    class="pos-catalog__category-btn"
                    :class="{'pos-catalog__category-btn--active': filterCat==='packable'}">
                Packs
            </button>
            @foreach($categories as $category)
                <button @click="filterCat='cat_{{ $category->id }}'"
                        class="pos-catalog__category-btn"
                        :class="{'pos-catalog__category-btn--active': filterCat==='cat_{{ $category->id }}'}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        <div class="pos-grid">
            <template x-if="filtered.length === 0">
                <div style="grid-column:1/-1;text-align:center;padding:60px;color:#94A3B8;">
                    <div style="font-size:40px;margin-bottom:12px;">🔍</div>
                    Aucun article trouvé
                </div>
            </template>
            <template x-for="item in filtered" :key="item.type+item.id">
                <div class="pos-item"
                     @click="handleItemClick(item)"
                     :style="(stockMap[item.type+'_'+item.id] ?? item.stock) < (packMode[item.type+'_'+item.id] && item.can_be_packed ? item.pack_quantity : 1) ? 'opacity:.45;cursor:not-allowed;' : ''">
                    <div class="pos-item__thumb">
                        <template x-if="item.image_url">
                            <img :src="item.image_url" class="pos-item__img" alt="">
                        </template>
                        <template x-if="!item.image_url">
                            <span x-text="item.type === 'pack' ? '📦' : '📦'"></span>
                        </template>
                    </div>
                    <div class="pos-item__name" x-text="item.name"></div>
                    <div class="pos-item__price"
                         x-text="fmt(packMode[item.type+'_'+item.id] && item.can_be_packed ? item.pack_price : item.price)"></div>
                    <div class="pos-item__stock"
                         :style="(stockMap[item.type+'_'+item.id] ?? item.stock) <= 0 ? 'color:#ef4444;font-weight:600;' : ''"
                         x-text="'Stock : ' + (stockMap[item.type+'_'+item.id] ?? item.stock)"></div>
                    <template x-if="item.can_be_packed && item.pack_quantity > 1">
                        <label @click.stop class="pack-toggle" style="justify-content:center;width:100%;margin-top:6px;padding:3px 6px;">
                            <input type="checkbox"
                                   :checked="packMode[item.type+'_'+item.id]"
                                   @change="packMode[item.type+'_'+item.id] = $event.target.checked">
                            Pack ×<span x-text="item.pack_quantity"></span>
                        </label>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Bouton flottant panier (mobile uniquement) --}}
    <button type="button" class="pos-cart-fab" @click="mobileCartOpen = true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        <span class="pos-cart-fab__badge" x-show="cart.length > 0" x-text="cart.length"></span>
    </button>

    {{-- Fond assombri (mobile, panier ouvert) --}}
    <div class="pos-cart-backdrop" x-show="mobileCartOpen" x-cloak x-transition.opacity @click="mobileCartOpen = false"></div>

    {{-- Panier --}}
    <div class="pos-cart" :class="{ 'pos-cart--open': mobileCartOpen }">
        <div class="pos-cart__header">
            <strong style="font-size:15px;">Panier <span x-text="'(' + cart.length + ')'" style="color:#64748B;font-weight:400;"></span></strong>
            <div style="display:flex;align-items:center;gap:12px;">
                <button @click="clearCart()" x-show="cart.length > 0" style="font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;">Vider</button>
                <button type="button" class="pos-cart__close" @click="mobileCartOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="pos-cart__items">
            <template x-if="cart.length === 0">
                <div class="pos-empty">
                    <div style="font-size:48px;margin-bottom:12px;">🛒</div>
                    <div>Cliquez sur un article pour l'ajouter</div>
                </div>
            </template>
            <template x-for="(item, idx) in cart" :key="idx">
                <div class="pos-cart__item">
                    <div class="pos-cart__item-name" x-text="item.item_name"></div>
                    <div class="pos-cart__item-qty">
                        <button @click="decQty(idx)" style="color:#C4231A;">−</button>
                        <span x-text="item.quantity"></span>
                        <button @click="incQty(idx)" style="color:#1749B3;">+</button>
                    </div>
                    <div class="pos-cart__item-price" x-text="fmt(item.quantity * item.unit_price)"></div>
                    <button @click="removeItem(idx)" style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:16px;">×</button>
                </div>
            </template>
        </div>

        <div class="pos-cart__footer">
            <div class="pos-total">
                <span>Total</span>
                <span style="color:#1749B3;" x-text="fmt(total)"></span>
            </div>

            {{-- Paiement --}}
            <div x-show="!showPayment">
                <button @click="showPayment = true" class="btn btn--primary w-full" style="justify-content:center;padding:14px;" :disabled="cart.length === 0">
                    Procéder au paiement →
                </button>
            </div>

            <div x-show="showPayment" x-cloak>
                <div class="form-group" style="margin-bottom:10px;">
                    <label style="font-size:12px;">Mode de paiement</label>
                    <select x-model="paymentMode" class="form-select">
                        <option value="cash">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank">Virement</option>
                    </select>
                </div>
                <label class="pos-payment-toggle">
                    <input type="checkbox" x-model="fullPayment" class="pos-payment-toggle__checkbox">
                    <span class="pos-payment-toggle__label">Paiement complet</span>
                    <span class="pos-payment-toggle__amount" x-text="fmt(total)"></span>
                </label>
                <div class="form-group" style="margin-bottom:10px;">
                    <label style="font-size:12px;">Montant reçu ({{ $currency }})</label>
                    <input type="number" x-model.number="amountReceived" class="form-control input--payment-amount" :placeholder="total" :readonly="fullPayment">
                </div>
                <div x-show="amountReceived >= total" class="pos-change-box">
                    Monnaie : <strong x-text="fmt(amountReceived - total)"></strong>
                </div>
                <div class="pos-payment-footer">
                    <button @click="showPayment = false" class="btn btn--ghost pos-payment-footer__back">Retour</button>
                    <button @click="checkout()" class="btn btn--primary pos-payment-footer__validate"
                        :disabled="cart.length === 0 || amountReceived < total">
                        Valider ✓
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal succès --}}
    <div class="modal-overlay" x-show="successSale" x-cloak x-transition>
        <div class="modal modal--sm pos-success-modal">
            <div class="modal__body">
                <div class="pos-success-modal__icon">✅</div>
                <h2 class="pos-success-modal__title">Vente enregistrée !</h2>
                <p class="pos-success-modal__ref">Référence : <strong x-text="lastRef"></strong></p>
                <p class="pos-success-modal__change">
                    Monnaie : <span x-text="fmt(lastChange)"></span>
                </p>
                <div class="pos-success-modal__actions">
                    <a :href="'/pos/'+lastSaleId+'/receipt'" target="_blank" class="btn btn--light">Imprimer le ticket</a>
                    <button @click="successSale = false; newSale()" class="btn btn--primary">Nouvelle vente</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        products: [],
        cart: [],
        search: '',
        filterCat: '',
        sessionWarehouseId: {{ $activeSession?->warehouse_id ?: ($defaultWarehouseId ?: 'null') }},
        customerSelect: {{ $customers->first()?->id ?? 'null' }},
        customerSearch: '{{ addslashes($customers->first()?->name ?? '') }}',
        customerOpen: false,
        customers: {{ Js::from($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
        showCreateCustomerPOS: false, newCustomerNamePOS: '', newCustomerPhonePOS: '', creatingCustomerPOS: false, createCustomerErrorPOS: '',
        showPayment: false,
        paymentMode: 'cash',
        amountReceived: 0,
        fullPayment: false,
        successSale: false,
        lastRef: '',
        lastChange: 0,
        lastSaleId: null,
        stockMap: {},
        packMode: {},
        mobileCartOpen: false,

        get total() {
            return this.cart.reduce((s, i) => s + i.quantity * i.unit_price, 0);
        },

        get filtered() {
            let list = this.products;
            if (this.search.length >= 1) {
                const q = this.search.toLowerCase();
                list = list.filter(p => p.name.toLowerCase().includes(q));
            }
            if (this.filterCat === 'packable') {
                list = list.filter(p => p.can_be_packed);
            } else if (this.filterCat === 'product') {
                list = list.filter(p => p.type === 'product');
            } else if (this.filterCat.startsWith('cat_')) {
                const catId = parseInt(this.filterCat.slice(4));
                list = list.filter(p => p.category_id === catId);
            }
            return list;
        },

        async init() {
            await this.loadProducts();
            this.$watch('total', v => { if (this.fullPayment) this.amountReceived = v; });
            this.$watch('fullPayment', v => { if (v) this.amountReceived = this.total; else this.amountReceived = 0; });
        },

        async loadProducts() {
            const params = new URLSearchParams({ q: '' });
            if (this.sessionWarehouseId) params.set('warehouse_id', this.sessionWarehouseId);
            const res = await fetch('/sales/api/search?' + params.toString());
            this.products = await res.json();
            const map = {};
            this.products.forEach(p => { map[p.type + '_' + p.id] = p.stock; });
            this.stockMap = map;
        },

        handleItemClick(item) {
            const key = item.type + '_' + item.id;
            const currentStock = this.stockMap[key] ?? item.stock;
            const isPack = this.packMode[key] && item.can_be_packed && item.pack_quantity > 1;
            const needed = isPack ? item.pack_quantity : 1;
            if (currentStock < needed) return;
            if (isPack) {
                this.addToCart(item, item.pack_quantity, item.pack_price);
            } else {
                this.addToCart(item, 1, item.price);
            }
        },

        addToCart(item, unitsPerItem, unitPrice) {
            const cartKey = item.type + item.id + '_' + unitsPerItem;
            const existing = this.cart.find(c => c._key === cartKey);
            if (existing) {
                existing.quantity++;
            } else {
                const label = unitsPerItem > 1
                    ? item.name + ' (Pack ×' + unitsPerItem + ')'
                    : item.name;
                this.cart.push({
                    _key:           cartKey,
                    item_type:      item.type,
                    product_id:     item.type === 'product' ? item.id : null,
                    pack_id:        item.type === 'pack'    ? item.id : null,
                    item_name:      label,
                    quantity:       1,
                    unit_price:     unitPrice,
                    units_per_item: unitsPerItem,
                });
            }
            if (item.type === 'product') {
                const smKey = 'product_' + item.id;
                this.stockMap[smKey] = (this.stockMap[smKey] ?? 0) - unitsPerItem;
            }
        },

        incQty(idx) {
            const item = this.cart[idx];
            if (item.item_type === 'product') {
                const k = 'product_' + item.product_id;
                if ((this.stockMap[k] ?? 0) < item.units_per_item) return;
                this.stockMap[k] = (this.stockMap[k] ?? 0) - item.units_per_item;
            }
            item.quantity++;
        },

        decQty(idx) {
            const item = this.cart[idx];
            if (item.item_type === 'product') {
                const k = 'product_' + item.product_id;
                this.stockMap[k] = (this.stockMap[k] ?? 0) + item.units_per_item;
            }
            if (item.quantity > 1) {
                item.quantity--;
            } else {
                this.cart.splice(idx, 1);
            }
        },

        removeItem(idx) {
            const item = this.cart[idx];
            if (item.item_type === 'product') {
                const k = 'product_' + item.product_id;
                this.stockMap[k] = (this.stockMap[k] ?? 0) + item.quantity * item.units_per_item;
            }
            this.cart.splice(idx, 1);
        },

        clearCart() {
            if (!confirm('Vider le panier ?')) return;
            this.cart.forEach(item => {
                if (item.item_type === 'product') {
                    const k = 'product_' + item.product_id;
                    this.stockMap[k] = (this.stockMap[k] ?? 0) + item.quantity * item.units_per_item;
                }
            });
            this.cart = [];
            this.showPayment = false;
            this.amountReceived = 0;
        },

        async checkout() {
            if (this.cart.length === 0 || this.amountReceived < this.total) return;
            const payload = {
                items:        this.cart,
                customer_id:  this.customerSelect || null,
                payment_mode: this.paymentMode,
                amount_paid:  this.amountReceived,
                total:        this.total,
            };
            const res = await fetch('/pos/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.success) {
                const saleAmount = this.total;
                this.lastRef     = data.reference;
                this.lastChange  = data.change;
                this.lastSaleId  = data.sale_id;
                this.successSale = true;
                window.dispatchEvent(new CustomEvent('pos-sale-completed', { detail: { amount: saleAmount } }));
                window.toast('Vente enregistrée avec succès (' + data.reference + ').', 'success');
            } else {
                window.toast(data.message || 'Erreur lors de la vente. Veuillez réessayer.', 'error');
            }
        },

        async newSale() {
            this.cart = [];
            this.amountReceived = 0;
            this.fullPayment = false;
            this.showPayment = false;
            this.mobileCartOpen = false;
            this.customerSelect = '';
            this.customerSearch = '';
            await this.loadProducts();
        },

        async quickCreateCustomerPOS() {
            if (!this.newCustomerNamePOS.trim()) return;
            this.creatingCustomerPOS = true; this.createCustomerErrorPOS = '';
            try {
                const res  = await fetch('{{ route('customers.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ name: this.newCustomerNamePOS, phone: this.newCustomerPhonePOS || null }),
                });
                const data = await res.json();
                if (data.success) {
                    this.customers.push(data.customer);
                    this.customerSelect = data.customer.id;
                    this.customerSearch = data.customer.name;
                    this.showCreateCustomerPOS = false; this.newCustomerNamePOS = ''; this.newCustomerPhonePOS = '';
                    window.toast('Client créé avec succès.', 'success');
                } else {
                    this.createCustomerErrorPOS = data.message || 'Erreur.';
                }
            } catch { this.createCustomerErrorPOS = 'Erreur réseau.'; }
            finally   { this.creatingCustomerPOS = false; }
        },
        fmt(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' ' + window.CURRENCY; },
    };
}

</script>
@endpush
