import { CanvasTexture, Color, MeshPhysicalMaterial, MeshStandardMaterial, SRGBColorSpace } from 'three';

/**
 * Packaging body material for a preset chosen in the CMS.
 *
 * @param {'glossy' | 'matte' | 'frosted_glass' | 'metallic'} preset
 * @param {string} color hex color
 * @param {{ lowPower?: boolean }} [options]
 */
export function createBodyMaterial(preset, color, { lowPower = false } = {}) {
    const base = { color: new Color(color) };

    switch (preset) {
        case 'matte':
            return new MeshPhysicalMaterial({ ...base, roughness: 0.82, metalness: 0, sheen: 0.4, sheenRoughness: 0.8 });

        case 'frosted_glass':
            // Transmission is expensive; low-power devices get simple transparency.
            return lowPower
                ? new MeshPhysicalMaterial({ ...base, roughness: 0.4, transparent: true, opacity: 0.82 })
                : new MeshPhysicalMaterial({
                      ...base,
                      roughness: 0.38,
                      metalness: 0,
                      transmission: 0.92,
                      thickness: 0.7,
                      ior: 1.45,
                      attenuationColor: new Color(color),
                      attenuationDistance: 1.4,
                  });

        case 'metallic':
            return new MeshPhysicalMaterial({ ...base, roughness: 0.26, metalness: 0.92, clearcoat: 0.6 });

        case 'glossy':
        default:
            return new MeshPhysicalMaterial({
                ...base,
                roughness: 0.16,
                metalness: 0,
                clearcoat: 1,
                clearcoatRoughness: 0.06,
            });
    }
}

/** Caps, lids and collars: soft champagne metal. */
export function createCapMaterial() {
    return new MeshPhysicalMaterial({
        color: new Color('#d8c3a5'),
        roughness: 0.3,
        metalness: 1,
        clearcoat: 0.4,
    });
}

/** Rubber bulb for dropper bottles. */
export function createRubberMaterial(color = '#2a2124') {
    return new MeshPhysicalMaterial({ color: new Color(color), roughness: 0.65, metalness: 0, sheen: 0.3 });
}

/**
 * @param {import('three').Texture} texture
 */
export function createLabelMaterial(texture) {
    return new MeshStandardMaterial({
        map: texture,
        roughness: 0.55,
        metalness: 0,
        transparent: true,
        polygonOffset: true,
        polygonOffsetFactor: -1,
    });
}

/**
 * Generated label used when the CMS has no label image: product name on a soft band.
 * The canvas matches the label surface's aspect ratio so text is not stretched.
 *
 * @param {string} text
 * @param {string} accent hex color
 * @param {{ aspect?: number, visibleFraction?: number }} [surface]
 *   aspect: label surface width / height; visibleFraction: part of the width
 *   that faces the camera (curved labels only show their middle).
 */
export function createDefaultLabelTexture(text, accent = '#a35d6a', { aspect = 2, visibleFraction = 0.5 } = {}) {
    // Texture budget: max 2048 px on either side (AGENTS.md section 8).
    const MAX = 2048;
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(Math.min(MAX, Math.max(256, 512 * aspect)));
    canvas.height = Math.round(Math.min(512, Math.max(128, canvas.width / Math.max(aspect, 0.25))));

    const ctx = canvas.getContext('2d');

    if (ctx) {
        const unit = canvas.height / 512; // scale drawing sizes with the canvas height
        const areaWidth = canvas.width * Math.min(1, visibleFraction) * 0.86;
        const areaX = (canvas.width - areaWidth) / 2;

        ctx.fillStyle = 'rgba(255, 252, 249, 0.94)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.strokeStyle = accent;
        ctx.lineWidth = Math.max(1.5, 3 * unit);
        ctx.strokeRect(areaX, 40 * unit, areaWidth, canvas.height - 80 * unit);

        ctx.fillStyle = '#2a2124';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const words = (text || '').toUpperCase().split(/\s+/).filter(Boolean);
        const lines = splitLines(words, areaWidth / canvas.height > 1.6 ? 16 : 10).slice(0, 3);

        const maxWidth = areaWidth * 0.84;
        let size = Math.round((lines.length > 2 ? 64 : 76) * unit);
        const font = (px) => `500 ${px}px "Cormorant Garamond", Georgia, serif`;

        ctx.font = font(size);
        const widest = Math.max(...lines.map((line) => ctx.measureText(line).width), 1);

        if (widest > maxWidth) {
            size = Math.floor(size * (maxWidth / widest));
            ctx.font = font(size);
        }

        lines.forEach((line, index) => {
            const y = canvas.height / 2 + (index - (lines.length - 1) / 2) * size * 1.15;
            ctx.fillText(line, canvas.width / 2, y);
        });

        ctx.fillStyle = accent;
        ctx.fillRect(canvas.width / 2 - 40 * unit, canvas.height / 2 + (lines.length / 2) * size * 1.15 + 14 * unit, 80 * unit, Math.max(1.5, 3 * unit));
    }

    const texture = new CanvasTexture(canvas);
    texture.colorSpace = SRGBColorSpace;
    texture.anisotropy = 4;

    return texture;
}

function splitLines(words, maxChars) {
    const lines = [];
    let current = '';

    for (const word of words) {
        const next = current ? `${current} ${word}` : word;

        if (next.length > maxChars && current) {
            lines.push(current);
            current = word;
        } else {
            current = next;
        }
    }

    if (current) lines.push(current);

    return lines;
}
