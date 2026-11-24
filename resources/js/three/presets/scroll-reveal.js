import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Model turns and rises as the viewer scrolls through the viewport (scrubbed).
 *
 * @type {import('./index.js').CreatePreset}
 */
export function createPreset(target, options) {
    const intensity = options.intensity ?? 1;

    target.rotation.y = -0.9 * intensity;
    target.position.y -= 0.12 * intensity;

    const timeline = gsap.timeline({
        scrollTrigger: {
            trigger: options.container,
            start: 'top bottom',
            end: 'bottom top',
            scrub: 0.6,
            onUpdate: () => options.requestRender?.(),
        },
    });

    timeline
        .to(target.rotation, { y: 0.9 * intensity, ease: 'none' }, 0)
        .to(target.position, { y: `+=${0.24 * intensity}`, ease: 'none' }, 0);

    return {
        update() {},
        isRunning() {
            return false;
        },
        dispose() {
            timeline.scrollTrigger?.kill();
            timeline.kill();
        },
    };
}
