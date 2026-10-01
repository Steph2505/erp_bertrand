import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

// ─── Toast global ───────────────────────────────────────────────────────────
// Composant unique utilisé dans tout le système pour les notifications de
// confirmation/erreur (enregistrement, suppression, paiement...). Chaque toast
// disparaît automatiquement après 5 secondes.
let _toastId = 0;

Alpine.store('toast', {
    items: [],

    push(message, type = 'success', duration = 5000) {
        if (!message) return;
        const id = ++_toastId;
        this.items.push({ id, type, message });
        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }
        return id;
    },

    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

window.toast = (message, type = 'success', duration = 5000) => Alpine.store('toast').push(message, type, duration);

// Pour les actions AJAX suivies d'un window.location.reload() : le message
// survit au rechargement et s'affiche une fois la page revenue.
window.toastAfterReload = (message, type = 'success') => {
    try { sessionStorage.setItem('__pendingToast', JSON.stringify({ message, type })); } catch (e) {}
};

// Messages flash Laravel (session success/error, erreurs de validation) émis en toast au chargement.
// Les avertissements (ex: mot de passe temporaire à copier) restent affichés jusqu'à fermeture manuelle.
(window.__flashMessages || []).forEach((f) => window.toast(f.message, f.type, f.type === 'warning' ? 0 : 5000));

try {
    const pending = sessionStorage.getItem('__pendingToast');
    if (pending) {
        sessionStorage.removeItem('__pendingToast');
        const p = JSON.parse(pending);
        window.toast(p.message, p.type);
    }
} catch (e) {}

// ─── Modal de confirmation global ──────────────────────────────────────────
// Remplace les confirm() natifs du navigateur pour toutes les actions
// destructives ou irréversibles (suppression, clôture de caisse, validation
// d'achat/vente, paiement...). Usage : await window.confirmDialog('message').
Alpine.store('confirmDialog', {
    visible: false,
    title: 'Confirmation',
    message: '',
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    variant: 'default',
    _resolve: null,

    open(message, options = {}) {
        this.title        = options.title ?? 'Confirmation';
        this.message       = message;
        this.confirmLabel = options.confirmLabel ?? 'Confirmer';
        this.cancelLabel   = options.cancelLabel ?? 'Annuler';
        this.variant       = options.variant ?? 'default';
        this.visible       = true;
        return new Promise((resolve) => { this._resolve = resolve; });
    },

    confirm() {
        this.visible = false;
        this._resolve?.(true);
        this._resolve = null;
    },

    cancel() {
        this.visible = false;
        this._resolve?.(false);
        this._resolve = null;
    },
});

window.confirmDialog = (message, options = {}) => Alpine.store('confirmDialog').open(message, options);

// ─── Loader plein écran ────────────────────────────────────────────────────
// Affiché automatiquement dès qu'un formulaire est réellement soumis (y
// compris après confirmation via confirmDialog, puisque $el.submit() passe
// par la méthode native interceptée ci-dessous). Reste visible jusqu'au
// chargement de la page suivante, donc aucun appel pour le masquer n'est
// nécessaire dans le cas normal.
Alpine.store('pageLoader', {
    visible: false,
    message: 'Traitement en cours...',

    show(message = 'Traitement en cours...') {
        this.message = message;
        this.visible = true;
    },

    hide() {
        this.visible = false;
    },
});

window.showPageLoader = (message) => Alpine.store('pageLoader').show(message);
window.hidePageLoader = () => Alpine.store('pageLoader').hide();

const _nativeFormSubmit = HTMLFormElement.prototype.submit;
HTMLFormElement.prototype.submit = function () {
    if (!this.hasAttribute('data-no-loader')) {
        window.showPageLoader();
    }
    return _nativeFormSubmit.call(this);
};

// Capture les soumissions "classiques" (clic sur un bouton submit sans
// confirmDialog ni $el.submit() programmatique). Si un gestionnaire Alpine a
// déjà fait preventDefault() (ex: confirmDialog en attente, validation custom
// qui bloque l'envoi), on ne montre rien : soit le vrai submit() programmatique
// l'affichera plus tard, soit il n'y aura jamais d'envoi.
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    if (event.target.hasAttribute('data-no-loader')) return;
    window.showPageLoader();
});

Alpine.data('imagePreview', () => ({
    previews: [],
    onChange(event) {
        const maxSize = 2 * 1024 * 1024; // 2 Mo
        const files = Array.from(event.target.files || []);
        const oversized = files.filter((file) => file.size > maxSize);

        this.previews = [];

        if (oversized.length) {
            window.toast(
                `Image trop volumineuse (max 2 Mo) : ${oversized.map((file) => file.name).join(', ')}`,
                'error'
            );
            event.target.value = '';
            return;
        }

        files.forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => this.previews.push(e.target.result);
            reader.readAsDataURL(file);
        });
    },
}));

Alpine.data('productQuickCreate', (categories, units, categoryId, unitId) => ({
    categories,
    units,
    categoryId: categoryId ?? categories[0]?.id ?? null,
    unitId: unitId ?? units[0]?.id ?? null,

    showCreateCategory: false,
    newCategoryName: '',
    creatingCategory: false,
    createCategoryError: '',

    showCreateUnit: false,
    newUnitName: '',
    newUnitAbbr: '',
    creatingUnit: false,
    createUnitError: '',

    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    },

    async createCategory() {
        if (!this.newCategoryName.trim()) return;
        this.creatingCategory = true;
        this.createCategoryError = '';
        try {
            const res = await fetch('/categories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ name: this.newCategoryName }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.categories.push(data.category);
                this.categoryId = data.category.id;
                this.showCreateCategory = false;
                this.newCategoryName = '';
                window.toast('Catégorie créée avec succès.', 'success');
            } else {
                this.createCategoryError = data.message || data.errors?.name?.[0] || 'Erreur lors de la création.';
            }
        } catch (e) {
            this.createCategoryError = 'Erreur réseau.';
        } finally {
            this.creatingCategory = false;
        }
    },

    async createUnit() {
        if (!this.newUnitName.trim() || !this.newUnitAbbr.trim()) return;
        this.creatingUnit = true;
        this.createUnitError = '';
        try {
            const res = await fetch('/units', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ name: this.newUnitName, abbreviation: this.newUnitAbbr }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.units.push(data.unit);
                this.unitId = data.unit.id;
                this.showCreateUnit = false;
                this.newUnitName = '';
                this.newUnitAbbr = '';
                window.toast('Unité créée avec succès.', 'success');
            } else {
                this.createUnitError = data.message || data.errors?.name?.[0] || 'Erreur lors de la création.';
            }
        } catch (e) {
            this.createUnitError = 'Erreur réseau.';
        } finally {
            this.creatingUnit = false;
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
