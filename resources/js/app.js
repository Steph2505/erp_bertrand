import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

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

window.Alpine = Alpine;
Alpine.start();
