/** Seconds to wait after the user lets go before rotating again. */
const RESUME_DELAY = 2.5;

/**
 * Slow rotation around Y. Pauses while the user interacts.
 *
 * @type {import('./index.js').CreatePreset}
 */
export function createPreset(target, options) {
    const speed = 0.32 * (options.intensity ?? 1); // radians per second
    let paused = false;
    let resumeIn = 0;
    let stopped = false;

    const onStart = () => {
        paused = true;
    };
    const onEnd = () => {
        paused = false;
        resumeIn = RESUME_DELAY;
    };

    options.controls?.addEventListener('start', onStart);
    options.controls?.addEventListener('end', onEnd);

    return {
        update(delta) {
            if (stopped || paused) return;

            if (resumeIn > 0) {
                resumeIn -= delta;
                return;
            }

            target.rotation.y += speed * delta;
        },
        isRunning() {
            return !stopped;
        },
        toggle() {
            stopped = !stopped;
            return !stopped;
        },
        dispose() {
            options.controls?.removeEventListener('start', onStart);
            options.controls?.removeEventListener('end', onEnd);
        },
    };
}
