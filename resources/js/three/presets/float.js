/**
 * Gentle up-and-down float with a slight sway.
 *
 * @type {import('./index.js').CreatePreset}
 */
export function createPreset(target, options) {
    const amplitude = 0.06 * (options.intensity ?? 1);
    const baseY = target.position.y;
    const baseRotation = target.rotation.y;
    let elapsed = 0;
    let stopped = false;

    return {
        update(delta) {
            if (stopped) return;

            elapsed += delta;
            target.position.y = baseY + Math.sin(elapsed * 1.3) * amplitude;
            target.rotation.y = baseRotation + Math.sin(elapsed * 0.5) * 0.25;
        },
        isRunning() {
            return !stopped;
        },
        toggle() {
            stopped = !stopped;
            return !stopped;
        },
        dispose() {
            target.position.y = baseY;
        },
    };
}
