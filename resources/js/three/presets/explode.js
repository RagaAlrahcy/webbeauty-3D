import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const CAP_NAMES = /cap|lid|tutup|pump|dropper/i;
const BODY_NAMES = /body|badan|bottle|jar|base/i;

/**
 * Separates named parts of a GLB model (cap/lid and body) while scrolling.
 * Falls back to a scroll-driven turn when the model has no named parts.
 *
 * @type {import('./index.js').CreatePreset}
 */
export function createPreset(target, options) {
    const intensity = options.intensity ?? 1;
    const caps = [];
    const bodies = [];

    target.traverse((node) => {
        if (node === target || !node.name) return;
        if (CAP_NAMES.test(node.name)) caps.push(node);
        else if (BODY_NAMES.test(node.name)) bodies.push(node);
    });

    const timeline = gsap.timeline({
        scrollTrigger: {
            trigger: options.container,
            start: 'top 70%',
            end: 'bottom 30%',
            scrub: 0.6,
            onUpdate: () => options.requestRender?.(),
        },
    });

    if (caps.length === 0 && bodies.length === 0) {
        timeline.to(target.rotation, { y: Math.PI * 0.6 * intensity, ease: 'none' }, 0);
    } else {
        caps.forEach((node) => {
            timeline.to(node.position, { y: node.position.y + 0.55 * intensity, ease: 'power1.inOut' }, 0);
            timeline.to(node.rotation, { y: node.rotation.y + Math.PI * 0.5, ease: 'none' }, 0);
        });
        bodies.forEach((node) => {
            timeline.to(node.position, { y: node.position.y - 0.15 * intensity, ease: 'power1.inOut' }, 0);
        });
        timeline.to(target.rotation, { y: 0.5, ease: 'none' }, 0);
    }

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
