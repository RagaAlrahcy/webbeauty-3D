import Alpine from 'alpinejs';
import { mountProductViewers } from './three/mount.js';

// Small UI helpers. Heavy libraries (three, gsap) are never imported here:
// mount.js loads them with dynamic import() only when a viewer is on the page.

Alpine.data('variantPicker', (variants = [], initialIndex = 0) => ({
    variants,
    active: initialIndex,

    select(index) {
        this.active = index;
        const variant = this.variants[index];

        window.dispatchEvent(new CustomEvent('product:variant-change', { detail: variant }));
    },

    isActive(index) {
        return this.active === index;
    },
}));

window.Alpine = Alpine;
Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mountProductViewers());
} else {
    mountProductViewers();
}
