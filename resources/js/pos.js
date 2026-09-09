// POS — Logique dédiée au point de vente
// Ce fichier est chargé uniquement sur la page POS

document.addEventListener('alpine:init', () => {
    Alpine.data('posApp', () => ({
        cart: [],
        search: '',
        searchResults: [],
        activeFilter: 'all', // all, product, pack
        paymentMethod: 'cash',
        cashGiven: 0,

        get subtotal() {
            return this.cart.reduce((s, i) => s + i.price * i.quantity, 0);
        },
        get total() { return this.subtotal; },
        get change() { return Math.max(0, this.cashGiven - this.total); },
        get cartCount() { return this.cart.reduce((s, i) => s + i.quantity, 0); },

        async searchItems() {
            if (this.search.length < 2) { this.searchResults = []; return; }
            const r = await fetch(`/sales/api/search?q=${encodeURIComponent(this.search)}`);
            const all = await r.json();
            this.searchResults = this.activeFilter === 'all' ? all
                : all.filter(i => i.type === this.activeFilter);
        },

        addToCart(item) {
            const existing = this.cart.find(c => c.id === item.id && c.type === item.type);
            if (existing) { existing.quantity++; }
            else { this.cart.push({ ...item, quantity: 1 }); }
            this.search = '';
            this.searchResults = [];
        },

        increaseQty(index) { this.cart[index].quantity++; },
        decreaseQty(index) {
            if (this.cart[index].quantity > 1) this.cart[index].quantity--;
            else this.removeFromCart(index);
        },
        removeFromCart(index) { this.cart.splice(index, 1); },
        clearCart() { this.cart = []; },

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' XOF'; },
    }));
});
