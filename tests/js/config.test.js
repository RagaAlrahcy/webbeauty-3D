import { describe, expect, it } from 'vitest';
import { chooseRenderMode, choosePreset, parseConfig } from '../../resources/js/three/config.js';

const capable = { canRender3d: true, reducedMotion: false };

describe('parseConfig', () => {
    it('returns null for invalid JSON or non-objects', () => {
        expect(parseConfig('{broken')).toBeNull();
        expect(parseConfig('[]')).toBeNull();
        expect(parseConfig(null)).toBeNull();
    });

    it('normalizes unknown values to safe defaults', () => {
        const config = parseConfig(
            JSON.stringify({
                renderMode: 'hologram',
                baseColor: 'pink',
                material: 'plastic',
                animationPreset: 'spin',
                containerShape: 'box',
                camera: { x: 'a', y: 1, z: 2 },
                variants: [{ label: 'Rose', color: '#zzzzzz' }, null],
            }),
        );

        expect(config.renderMode).toBe('image');
        expect(config.baseColor).toBe('#f2d4cf');
        expect(config.material).toBe('glossy');
        expect(config.animationPreset).toBe('none');
        expect(config.containerShape).toBeNull();
        expect(config.camera).toBeNull();
        expect(config.variants).toEqual([
            { id: null, label: 'Rose', color: '#f2d4cf', labelTextureUrl: null, imageUrl: null },
        ]);
    });

    it('keeps valid values', () => {
        const config = parseConfig({
            renderMode: 'procedural',
            containerShape: 'jar',
            baseColor: '#AABBCC',
            camera: { x: 0, y: '0.5', z: 4 },
        });

        expect(config.renderMode).toBe('procedural');
        expect(config.containerShape).toBe('jar');
        expect(config.baseColor).toBe('#AABBCC');
        expect(config.camera).toEqual({ x: 0, y: 0.5, z: 4 });
    });
});

describe('chooseRenderMode', () => {
    it('uses the image when the device cannot render 3D', () => {
        const config = parseConfig({ renderMode: 'procedural', containerShape: 'bottle' });

        expect(chooseRenderMode(config, { canRender3d: false })).toBe('image');
    });

    it('uses the glb model when available', () => {
        expect(chooseRenderMode(parseConfig({ renderMode: 'model3d', modelUrl: '/m.glb' }), capable)).toBe('model3d');
    });

    it('falls back from model3d to procedural, then to image', () => {
        expect(chooseRenderMode(parseConfig({ renderMode: 'model3d', containerShape: 'tube' }), capable)).toBe('procedural');
        expect(chooseRenderMode(parseConfig({ renderMode: 'model3d' }), capable)).toBe('image');
    });

    it('needs a shape for procedural rendering', () => {
        expect(chooseRenderMode(parseConfig({ renderMode: 'procedural' }), capable)).toBe('image');
    });
});

describe('choosePreset', () => {
    it('disables presets for reduced motion', () => {
        expect(choosePreset({ animationPreset: 'rotate' }, { reducedMotion: true }, 'procedural')).toBe('none');
    });

    it('replaces explode for procedural packaging', () => {
        expect(choosePreset({ animationPreset: 'explode' }, capable, 'procedural')).toBe('rotate');
        expect(choosePreset({ animationPreset: 'explode' }, capable, 'model3d')).toBe('explode');
    });
});
