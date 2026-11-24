import { detectCapabilities } from './capabilities.js';
import { chooseRenderMode, parseConfig } from './config.js';

/** Only one active WebGL renderer per page (AGENTS.md section 8). */
const MAX_ACTIVE_VIEWERS = 1;

/**
 * Finds [data-product-viewer] elements and lazily loads the 3D viewer for them.
 * The server-rendered fallback image stays visible until the first frame is
 * ready, and remains if anything fails.
 *
 * @param {ParentNode} [root]
 */
export function mountProductViewers(root = document) {
    const containers = [...root.querySelectorAll('[data-product-viewer]:not([data-viewer-mounted])')];

    if (containers.length === 0) return;

    const capabilities = detectCapabilities();
    let active = 0;

    for (const container of containers) {
        container.setAttribute('data-viewer-mounted', '');

        const config = parseConfig(container.dataset.config);
        const mode = chooseRenderMode(config, capabilities);

        if (mode === 'image' || active >= MAX_ACTIVE_VIEWERS) {
            container.classList.add('is-static');
            continue;
        }

        active += 1;
        whenNearViewport(container, () => loadViewer(container, { ...config, renderMode: mode }, capabilities));
    }
}

function whenNearViewport(element, callback) {
    if (!('IntersectionObserver' in window)) {
        callback();
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                callback();
            }
        },
        { rootMargin: '200px 0px' },
    );

    observer.observe(element);
}

async function loadViewer(container, config, capabilities) {
    container.classList.add('is-loading');

    try {
        const { createViewer } = await import('./viewer.js');
        const viewer = await createViewer(container, config, capabilities);

        container.productViewer = viewer;
        container.classList.add('is-ready');
    } catch (error) {
        console.error('[product-viewer] 3D unavailable, keeping the fallback image.', error);
        container.classList.add('is-static');
    } finally {
        container.classList.remove('is-loading');
    }
}
