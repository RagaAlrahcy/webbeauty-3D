/**
 * Detects what the current device can handle. Pure and dependency-free so it
 * can run before three.js is downloaded (and be unit tested).
 *
 * @param {{ window?: any, document?: any, navigator?: any }} [env]
 * @returns {{ webgl: boolean, webgl2: boolean, reducedMotion: boolean, saveData: boolean, lowPower: boolean, canRender3d: boolean }}
 */
export function detectCapabilities(env = {}) {
    const win = env.window ?? globalThis.window;
    const doc = env.document ?? win?.document;
    const nav = env.navigator ?? win?.navigator ?? {};

    const reducedMotion = Boolean(win?.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches);
    const saveData = Boolean(nav.connection?.saveData);
    const { webgl, webgl2, softwareOnly } = probeWebGL(doc);

    const memory = typeof nav.deviceMemory === 'number' ? nav.deviceMemory : 8;
    const cores = typeof nav.hardwareConcurrency === 'number' ? nav.hardwareConcurrency : 8;

    // Low power: render with fewer effects. Too weak: keep the static image.
    const lowPower = softwareOnly || memory <= 2 || cores <= 2;
    const tooWeak = saveData || memory < 1 || (softwareOnly && cores <= 2);

    return {
        webgl,
        webgl2,
        reducedMotion,
        saveData,
        lowPower,
        canRender3d: webgl && !tooWeak,
    };
}

/**
 * @param {Document | undefined} doc
 * @returns {{ webgl: boolean, webgl2: boolean, softwareOnly: boolean }}
 */
export function probeWebGL(doc) {
    const result = { webgl: false, webgl2: false, softwareOnly: false };

    if (!doc?.createElement) return result;

    try {
        const canvas = doc.createElement('canvas');
        let gl = canvas.getContext('webgl2', { failIfMajorPerformanceCaveat: true });
        result.webgl2 = Boolean(gl);

        if (!gl) {
            gl = canvas.getContext('webgl', { failIfMajorPerformanceCaveat: true });
        }

        if (!gl) {
            // Only a software renderer is available.
            gl = canvas.getContext('webgl');
            result.softwareOnly = Boolean(gl);
        }

        result.webgl = Boolean(gl);

        // Free the probe context right away: only one renderer per page is allowed.
        gl?.getExtension?.('WEBGL_lose_context')?.loseContext();
    } catch {
        return { webgl: false, webgl2: false, softwareOnly: false };
    }

    return result;
}
