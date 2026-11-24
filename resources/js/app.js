import Alpine from 'alpinejs';
import { mountProductViewers } from './three/mount.js';

// Small UI helpers. Heavy libraries (three, gsap) are never imported here:
// mount.js loads them with dynamic import() only when a viewer is on the page.

Alpine.data('variantPicker', (variants = [], initialIndex = 0) => ({
    variants,
    active: initialIndex,

    /** Selected variant (price, size, BPOM number and links for the bindings), or null. */
    get current() {
        return this.variants[this.active] ?? null;
    },

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

// Conversion tracking: links with data-track="whatsapp_click" etc. report a click to
// GA4 and Meta Pixel when an ID is set in Site Settings (otherwise gtag/fbq are absent).
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('[data-track]') : null;

    if (!link) {
        return;
    }

    const name = link.dataset.track;
    const label = link.dataset.trackLabel ?? '';

    window.gtag?.('event', name, { event_label: label, link_url: link.href });
    window.fbq?.(name === 'whatsapp_click' ? 'track' : 'trackCustom', name === 'whatsapp_click' ? 'Contact' : name, { content_name: label });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mountProductViewers());
} else {
    mountProductViewers();
}
