/**
 * Parsing and validation of the viewer config produced by Product::viewerConfig().
 * Dependency-free so it can be unit tested and used before three.js loads.
 */

export const RENDER_MODES = ['model3d', 'procedural', 'image'];
export const SHAPES = ['bottle', 'jar', 'tube', 'dropper', 'compact'];
export const MATERIALS = ['glossy', 'matte', 'frosted_glass', 'metallic'];
export const PRESETS = ['rotate', 'float', 'scroll_reveal', 'explode', 'none'];

export const DEFAULT_COLOR = '#f2d4cf';

const HEX = /^#[0-9a-fA-F]{6}$/;

const oneOf = (value, allowed, fallback) => (allowed.includes(value) ? value : fallback);
const hexOr = (value, fallback) => (typeof value === 'string' && HEX.test(value) ? value : fallback);
const urlOrNull = (value) => (typeof value === 'string' && value.length > 0 ? value : null);

/**
 * @param {string | object | null | undefined} raw JSON string from data-config, or an object
 * @returns {object | null} normalized config, or null when invalid
 */
export function parseConfig(raw) {
    let data = raw;

    if (typeof raw === 'string') {
        try {
            data = JSON.parse(raw);
        } catch {
            return null;
        }
    }

    if (!data || typeof data !== 'object' || Array.isArray(data)) return null;

    const camera = data.camera;
    const validCamera =
        camera && ['x', 'y', 'z'].every((axis) => Number.isFinite(Number(camera[axis])))
            ? { x: Number(camera.x), y: Number(camera.y), z: Number(camera.z) }
            : null;

    return {
        renderMode: oneOf(data.renderMode, RENDER_MODES, 'image'),
        modelUrl: urlOrNull(data.modelUrl),
        labelTextureUrl: urlOrNull(data.labelTextureUrl),
        labelText: typeof data.labelText === 'string' ? data.labelText.slice(0, 60) : '',
        containerShape: oneOf(data.containerShape, SHAPES, null),
        baseColor: hexOr(data.baseColor, DEFAULT_COLOR),
        material: oneOf(data.material, MATERIALS, 'glossy'),
        animationPreset: oneOf(data.animationPreset, PRESETS, 'none'),
        backgroundTheme: typeof data.backgroundTheme === 'string' ? data.backgroundTheme : null,
        camera: validCamera,
        variants: Array.isArray(data.variants)
            ? data.variants
                  .filter((variant) => variant && typeof variant === 'object')
                  .map((variant) => ({
                      id: variant.id ?? null,
                      label: String(variant.label ?? ''),
                      color: hexOr(variant.color, DEFAULT_COLOR),
                      labelTextureUrl: urlOrNull(variant.labelTextureUrl),
                      imageUrl: urlOrNull(variant.imageUrl),
                  }))
            : [],
    };
}

/**
 * Picks how to render a product on this device.
 *
 * @param {object | null} config result of parseConfig()
 * @param {{ canRender3d: boolean }} capabilities result of detectCapabilities()
 * @returns {'model3d' | 'procedural' | 'image'}
 */
export function chooseRenderMode(config, capabilities) {
    if (!config || !capabilities?.canRender3d) return 'image';

    if (config.renderMode === 'model3d') {
        if (config.modelUrl) return 'model3d';

        return config.containerShape ? 'procedural' : 'image';
    }

    if (config.renderMode === 'procedural' && config.containerShape) return 'procedural';

    return 'image';
}

/**
 * Presets run only when motion is allowed.
 *
 * @param {object} config
 * @param {{ reducedMotion: boolean }} capabilities
 * @param {'model3d' | 'procedural'} mode
 * @returns {string} preset name or 'none'
 */
export function choosePreset(config, capabilities, mode) {
    if (capabilities?.reducedMotion) return 'none';

    const preset = config?.animationPreset ?? 'none';

    // Explode needs named nodes, which only uploaded GLB models have.
    if (preset === 'explode' && mode !== 'model3d') return 'rotate';

    return preset;
}
