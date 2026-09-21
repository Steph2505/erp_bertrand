@push('scripts')
<script>
function reportAjax(apiUrl, initialFilters = {}) {
    return {
        apiUrl,
        filters: { ...initialFilters },
        data: {},
        loading: true,

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) params.set(k, v); });
            try {
                const res = await fetch(this.apiUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                this.data = await res.json();
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; },
        formatNumber(v) { return new Intl.NumberFormat('fr-FR').format(v || 0); },
    };
}
</script>
<style>@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }</style>
@endpush
