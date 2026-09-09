@push('scripts')
<script>
function listPage(apiUrl) {
    return {
        apiUrl,
        rows: [], extra: {}, total: 0, from: 0, to: 0,
        currentPage: 1, lastPage: 1, loading: true,
        filters: {},

        get hasFilters() {
            return Object.values(this.filters).some(v => v !== '' && v !== false && v !== null && v !== undefined);
        },

        get pages() {
            const t = this.lastPage, c = this.currentPage;
            if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1);
            const p = [1];
            if (c > 3) p.push('…');
            for (let i = Math.max(2, c - 1); i <= Math.min(t - 1, c + 1); i++) p.push(i);
            if (c < t - 2) p.push('…');
            p.push(t);
            return p;
        },

        reset() { this.currentPage = 1; this.fetch(); },

        goTo(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.fetch();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        clearFilters() {
            Object.keys(this.filters).forEach(k => this.filters[k] = '');
            this.reset();
        },

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams({ page: this.currentPage });
            Object.entries(this.filters).forEach(([k, v]) => { if (v !== '' && v !== false && v !== null) params.set(k, v); });
            try {
                const res  = await fetch(this.apiUrl + '?' + params, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                this.rows        = data.data;
                this.total       = data.total;
                this.from        = data.from;
                this.to          = data.to;
                this.currentPage = data.current_page;
                this.lastPage    = data.last_page;
                // Données supplémentaires (period_total, warehouse_filter…)
                const { data: _, total: __, from: ___, to: ____, current_page: _____, last_page: ______, per_page: _______ , ...extra } = data;
                this.extra = extra;
            } catch(e) { console.error(e); }
            finally { this.loading = false; }
        },
    };
}
</script>
<style>@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }</style>
@endpush
