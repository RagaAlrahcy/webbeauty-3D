/**
 * Animation presets. Every preset module exports the same contract:
 *
 * @callback CreatePreset
 * @param {import('three').Object3D} target
 * @param {{
 *   intensity?: number,
 *   enabled: boolean,
 *   camera: import('three').Camera,
 *   container: HTMLElement,
 *   controls?: import('three/addons/controls/OrbitControls.js').OrbitControls,
 *   requestRender?: () => void,
 * }} options
 * @returns {{ update(delta: number): void, dispose(): void, isRunning?(): boolean }}
 *
 * `isRunning()` tells the viewer whether the render loop must keep running
 * (time-based presets). Scroll-driven presets call `requestRender()` instead.
 */

const loaders = {
    rotate: () => import('./rotate.js'),
    float: () => import('./float.js'),
    scroll_reveal: () => import('./scroll-reveal.js'),
    explode: () => import('./explode.js'),
};

/**
 * @param {string} name
 * @param {import('three').Object3D} target
 * @param {object} options
 */
export async function loadPreset(name, target, options) {
    const load = loaders[name];

    if (!load || !options.enabled) return null;

    const module = await load();

    return module.createPreset(target, options);
}
