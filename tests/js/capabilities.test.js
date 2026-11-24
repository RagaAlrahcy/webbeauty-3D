import { describe, expect, it, vi } from 'vitest';
import { detectCapabilities } from '../../resources/js/three/capabilities.js';

function fakeEnv({ webgl = true, software = false, reducedMotion = false, navigator = {} } = {}) {
    const loseContext = vi.fn();
    const context = { getExtension: () => ({ loseContext }) };

    const document = {
        createElement: () => ({
            getContext: (type, options) => {
                if (!webgl) return null;
                if (software && options?.failIfMajorPerformanceCaveat) return null;
                return type === 'webgl2' || type === 'webgl' ? context : null;
            },
        }),
    };

    const window = { matchMedia: () => ({ matches: reducedMotion }), document };

    return { env: { window, document, navigator }, loseContext };
}

describe('detectCapabilities', () => {
    it('detects a capable device and frees the probe context', () => {
        const { env, loseContext } = fakeEnv({ navigator: { deviceMemory: 8, hardwareConcurrency: 8 } });
        const caps = detectCapabilities(env);

        expect(caps).toMatchObject({ webgl: true, webgl2: true, lowPower: false, canRender3d: true, reducedMotion: false });
        expect(loseContext).toHaveBeenCalled();
    });

    it('cannot render 3D without WebGL', () => {
        const { env } = fakeEnv({ webgl: false });

        expect(detectCapabilities(env).canRender3d).toBe(false);
    });

    it('keeps the image when data saver is on', () => {
        const { env } = fakeEnv({ navigator: { connection: { saveData: true } } });

        expect(detectCapabilities(env).canRender3d).toBe(false);
    });

    it('marks weak devices as low power', () => {
        const { env } = fakeEnv({ navigator: { deviceMemory: 2, hardwareConcurrency: 4 } });
        const caps = detectCapabilities(env);

        expect(caps.lowPower).toBe(true);
        expect(caps.canRender3d).toBe(true);
    });

    it('treats software rendering on very weak CPUs as too weak', () => {
        const { env } = fakeEnv({ software: true, navigator: { hardwareConcurrency: 2 } });

        expect(detectCapabilities(env).canRender3d).toBe(false);
    });

    it('reports reduced motion', () => {
        const { env } = fakeEnv({ reducedMotion: true });

        expect(detectCapabilities(env).reducedMotion).toBe(true);
    });
});
