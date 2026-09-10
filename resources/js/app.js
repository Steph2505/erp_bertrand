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

// Messages flash Laravel (session success/error, erreurs de validation) émis en toast au chargement
(window.__flashMessages || []).forEach((f) => window.toast(f.message, f.type));

try {
    const pending = sessionStorage.getItem('__pendingToast');
    if (pending) {
        sessionStorage.removeItem('__pendingToast');
        const p = JSON.parse(pending);
        window.toast(p.message, p.type);
    }
} catch (e) {}

Alpine.data('imagePreview', () => ({
    previews: [],
    onChange(event) {
        this.previews = [];
        Array.from(event.target.files || []).forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => this.previews.push(e.target.result);
            reader.readAsDataURL(file);
        });
    },
}));

Alpine.data('productQuickCreate', (categories, units, categoryId, unitId) => ({
    categories,
    units,
    categoryId,
    unitId,

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
