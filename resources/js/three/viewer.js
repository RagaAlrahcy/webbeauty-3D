import {
    ACESFilmicToneMapping,
    Box3,
    CanvasTexture,
    Clock,
    DirectionalLight,
    Group,
    HemisphereLight,
    Mesh,
    MeshBasicMaterial,
    PerspectiveCamera,
    PlaneGeometry,
    PMREMGenerator,
    Scene,
    SRGBColorSpace,
    TextureLoader,
    Vector3,
    WebGLRenderer,
} from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { choosePreset } from './config.js';
import { disposeObject } from './dispose.js';
import { createDefaultLabelTexture } from './materials.js';
import { loadPreset } from './presets/index.js';

const MODEL_HEIGHT = 1.8;
const MODEL_WIDTH = 1.35;
const DEFAULT_CAMERA = { x: 0, y: 0.35, z: 4.4 };
const IDLE_FRAMES_BEFORE_STOP = 24;

/**
 * @param {HTMLElement} container element with [data-product-viewer]
 * @param {object} config normalized config (see config.js)
 * @param {object} capabilities see capabilities.js
 * @returns {Promise<ProductViewer>}
 */
export async function createViewer(container, config, capabilities) {
    const viewer = new ProductViewer(container, config, capabilities);

    try {
        await viewer.init();
    } catch (error) {
        viewer.destroy();
        throw error;
    }

    return viewer;
}

export class ProductViewer {
    constructor(container, config, capabilities) {
        this.container = container;
        this.config = config;
        this.capabilities = capabilities;
        this.clock = new Clock();
        this.frame = null;
        this.idleFrames = 0;
        this.visible = true;
        this.destroyed = false;
        this.textureCache = new Map();
        this.cleanups = [];

        this.tick = this.tick.bind(this);
    }

    async init() {
        const { lowPower } = this.capabilities;
        const { width, height } = this.size();

        // Renderer
        const renderer = new WebGLRenderer({ antialias: !lowPower, alpha: true, powerPreference: 'high-performance' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, lowPower ? 1.5 : 2));
        renderer.setSize(width, height, false);
        renderer.outputColorSpace = SRGBColorSpace;
        renderer.toneMapping = ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.05;
        renderer.domElement.setAttribute('aria-hidden', 'true');
        this.renderer = renderer;

        // Scene & soft studio lighting
        const scene = new Scene();
        const pmrem = new PMREMGenerator(renderer);
        this.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
        scene.environment = this.environment;
        pmrem.dispose();

        scene.add(new HemisphereLight('#fff7f2', '#e9d6d0', 0.6));
        const key = new DirectionalLight('#ffffff', 1.2);
        key.position.set(2.5, 4, 3);
        scene.add(key);
        this.scene = scene;

        // Camera
        const start = this.config.camera ?? DEFAULT_CAMERA;
        this.camera = new PerspectiveCamera(30, width / height, 0.1, 100);
        this.camera.position.set(start.x, start.y, start.z);
        this.initialCamera = this.camera.position.clone();

        // Product
        this.pivot = new Group();
        this.pivot.add(await this.createProduct());
        const floorY = this.fitToView(this.pivot);
        scene.add(this.pivot);
        scene.add(this.createGroundShadow(floorY));

        // Controls
        const controls = new OrbitControls(this.camera, renderer.domElement);
        controls.enablePan = false;
        controls.enableDamping = true;
        controls.dampingFactor = 0.08;
        controls.enableZoom = false; // wheel would hijack page scroll; zoom via buttons
        controls.minDistance = 2.4;
        controls.maxDistance = 7;
        controls.minPolarAngle = Math.PI * 0.22;
        controls.maxPolarAngle = Math.PI * 0.62;
        controls.target.set(0, 0, 0);
        controls.addEventListener('change', () => this.requestRender());
        this.controls = controls;

        // Animation preset (skipped for prefers-reduced-motion)
        const presetName = choosePreset(this.config, this.capabilities, this.config.renderMode);
        this.preset = await loadPreset(presetName, this.pivot, {
            enabled: presetName !== 'none',
            camera: this.camera,
            container: this.container,
            controls,
            requestRender: () => this.requestRender(),
        });

        if (this.destroyed) return;

        this.container.prepend(renderer.domElement);
        this.bindEvents();
        this.bindControls();

        // First frame before the fallback image fades out.
        renderer.render(scene, this.camera);
        this.requestRender();
    }

    async createProduct() {
        if (this.config.renderMode === 'model3d') {
            const { loadModel } = await import('./product-model.js');

            return loadModel(this.config.modelUrl, this.renderer);
        }

        const { createPackaging } = await import('./procedural.js');

        const uploadedLabel = this.config.labelTextureUrl
            ? await this.loadTexture(this.config.labelTextureUrl).catch(() => null)
            : null;

        if (!uploadedLabel) await document.fonts?.ready; // generated label text uses the display font

        const accent = getComputedStyle(this.container).getPropertyValue('--brand').trim() || undefined;

        return createPackaging(this.config.containerShape, {
            color: this.config.baseColor,
            material: this.config.material,
            labelTexture: uploadedLabel,
            makeLabel: (surface) => createDefaultLabelTexture(this.config.labelText, accent, surface),
            lowPower: this.capabilities.lowPower,
        });
    }

    /** Scale and center any object so it fits the default camera framing. */
    fitToView(object) {
        const box = new Box3().setFromObject(object);
        const size = box.getSize(new Vector3());
        const scale = Math.min(
            MODEL_HEIGHT / Math.max(size.y, 0.001),
            MODEL_WIDTH / Math.max(size.x, size.z, 0.001),
        );

        object.scale.setScalar(scale);

        const centered = new Box3().setFromObject(object);
        const center = centered.getCenter(new Vector3());
        object.position.sub(center);

        // Bottom of the object after centering, where the ground shadow sits.
        return -(centered.max.y - centered.min.y) / 2;
    }

    createGroundShadow(floorY) {
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 128;
        const ctx = canvas.getContext('2d');

        if (ctx) {
            const gradient = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
            gradient.addColorStop(0, 'rgba(60, 30, 40, 0.28)');
            gradient.addColorStop(1, 'rgba(60, 30, 40, 0)');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, 128, 128);
        }

        const shadow = new Mesh(
            new PlaneGeometry(2.2, 2.2),
            new MeshBasicMaterial({ map: new CanvasTexture(canvas), transparent: true, depthWrite: false }),
        );
        shadow.rotation.x = -Math.PI / 2;
        shadow.position.y = floorY - 0.02;
        shadow.name = 'ground-shadow';

        return shadow;
    }

    bindEvents() {
        const resizeObserver = new ResizeObserver(() => this.resize());
        resizeObserver.observe(this.container);
        this.cleanups.push(() => resizeObserver.disconnect());

        const visibilityObserver = new IntersectionObserver((entries) => {
            this.visible = entries.some((entry) => entry.isIntersecting);
            this.visible ? this.requestRender() : this.stopLoop();
        });
        visibilityObserver.observe(this.container);
        this.cleanups.push(() => visibilityObserver.disconnect());

        const onVisibility = () => (document.hidden ? this.stopLoop() : this.requestRender());
        document.addEventListener('visibilitychange', onVisibility);
        this.cleanups.push(() => document.removeEventListener('visibilitychange', onVisibility));

        const onVariant = (event) => this.setVariant(event.detail);
        window.addEventListener('product:variant-change', onVariant);
        this.cleanups.push(() => window.removeEventListener('product:variant-change', onVariant));
    }

    bindControls() {
        const actions = {
            reset: () => this.resetView(),
            'zoom-in': () => this.zoom(0.85),
            'zoom-out': () => this.zoom(1.18),
            'toggle-rotation': (button) => {
                const playing = this.preset?.toggle?.();
                if (playing === undefined) return;
                button.setAttribute('aria-pressed', String(!playing));
                this.requestRender();
            },
        };

        this.container.querySelectorAll('[data-viewer-action]').forEach((button) => {
            const action = actions[button.dataset.viewerAction];

            if (!action) return;

            if (button.dataset.viewerAction === 'toggle-rotation' && !this.preset?.toggle) {
                button.hidden = true;
                return;
            }

            const onClick = () => action(button);
            button.addEventListener('click', onClick);
            this.cleanups.push(() => button.removeEventListener('click', onClick));
        });
    }

    resetView() {
        this.camera.position.copy(this.initialCamera);
        this.controls.target.set(0, 0, 0);
        this.controls.update();
        this.requestRender();
    }

    zoom(factor) {
        const offset = this.camera.position.clone().sub(this.controls.target);
        const distance = Math.min(Math.max(offset.length() * factor, this.controls.minDistance), this.controls.maxDistance);

        this.camera.position.copy(this.controls.target).add(offset.setLength(distance));
        this.controls.update();
        this.requestRender();
    }

    /**
     * Applies a variant without reloading the model.
     *
     * @param {{ color?: string, labelTextureUrl?: string | null }} variant
     */
    async setVariant(variant) {
        if (!variant || this.destroyed) return;

        this.pivot.traverse((node) => {
            if (node.isMesh && /^(body|crimp)$/i.test(node.name) && node.material?.color && variant.color) {
                node.material.color.set(variant.color);
                node.material.attenuationColor?.set?.(variant.color);
            }
        });

        const texture = variant.labelTextureUrl
            ? await this.loadTexture(variant.labelTextureUrl).catch(() => null)
            : null;

        if (this.destroyed) return;

        this.pivot.traverse((node) => {
            if (!node.isMesh || !/label/i.test(node.name) || !node.material) return;

            // Remember the product's own label so variants without one can restore it.
            node.userData.defaultMap ??= node.material.map;
            const map = texture ?? node.userData.defaultMap;

            if (map && node.material.map !== map) {
                node.material.map = map;
                node.material.needsUpdate = true;
            }
        });

        this.requestRender();
    }

    loadTexture(url) {
        if (!this.textureCache.has(url)) {
            const promise = new TextureLoader().loadAsync(url).then((texture) => {
                texture.colorSpace = SRGBColorSpace;
                texture.anisotropy = Math.min(8, this.renderer.capabilities.getMaxAnisotropy());
                return texture;
            });
            this.textureCache.set(url, promise);
        }

        return this.textureCache.get(url);
    }

    size() {
        const rect = this.container.getBoundingClientRect();

        return { width: Math.max(1, Math.round(rect.width)), height: Math.max(1, Math.round(rect.height)) };
    }

    resize() {
        const { width, height } = this.size();
        this.renderer.setSize(width, height, false);
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.requestRender();
    }

    /** Starts the loop if needed; it stops by itself when nothing moves. */
    requestRender() {
        this.idleFrames = 0;

        if (this.frame !== null || this.destroyed || !this.visible || document.hidden) return;

        this.clock.getDelta(); // discard time spent while stopped
        this.frame = requestAnimationFrame(this.tick);
    }

    tick() {
        // Placeholder while ticking, so 'change' events fired by controls.update()
        // do not schedule a second loop through requestRender().
        this.frame = -1;

        if (this.destroyed) return;

        const delta = Math.min(this.clock.getDelta(), 0.1);
        this.preset?.update(delta);
        const moved = this.controls.update(delta);
        this.renderer.render(this.scene, this.camera);

        this.idleFrames = moved ? 0 : this.idleFrames + 1;

        const animating = this.preset?.isRunning?.() ?? false;

        this.frame =
            this.visible && !document.hidden && (animating || this.idleFrames < IDLE_FRAMES_BEFORE_STOP)
                ? requestAnimationFrame(this.tick)
                : null;
    }

    stopLoop() {
        if (this.frame !== null && this.frame !== -1) cancelAnimationFrame(this.frame);
        this.frame = null;
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.stopLoop();
        this.cleanups.forEach((cleanup) => cleanup());
        this.cleanups = [];

        this.preset?.dispose();
        this.controls?.dispose();

        if (this.scene) disposeObject(this.scene);
        this.environment?.dispose();
        this.textureCache.forEach((promise) => promise.then((texture) => texture.dispose()).catch(() => {}));
        this.textureCache.clear();

        if (this.renderer) {
            this.renderer.dispose();
            this.renderer.forceContextLoss();
            this.renderer.domElement.remove();
        }

        this.container.classList.remove('is-ready');
    }
}
