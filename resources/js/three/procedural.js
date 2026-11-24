import {
    CircleGeometry,
    CylinderGeometry,
    Group,
    LatheGeometry,
    Mesh,
    SphereGeometry,
    Vector2,
} from 'three';
import {
    createBodyMaterial,
    createCapMaterial,
    createLabelMaterial,
    createRubberMaterial,
} from './materials.js';

const SEGMENTS = 96;

/**
 * Builds procedural packaging. Parts are named `body`, `label`, `cap`
 * (and `bulb` for droppers) so variants and presets can find them.
 *
 * @param {'bottle' | 'jar' | 'tube' | 'dropper' | 'compact'} shape
 * @param {{ color: string, material: string, labelTexture: import('three').Texture | null, makeLabel: (surface: { aspect: number, visibleFraction: number }) => import('three').Texture, lowPower?: boolean }} options
 * @returns {Group}
 */
export function createPackaging(shape, options) {
    const builders = { bottle, jar, tube, dropper, compact };
    const build = builders[shape] ?? bottle;

    const group = build(options);
    group.name = `packaging-${shape}`;

    return group;
}

function bodyMesh(points, options) {
    const mesh = new Mesh(
        new LatheGeometry(points, SEGMENTS),
        createBodyMaterial(options.material, options.color, options),
    );
    mesh.name = 'body';

    return mesh;
}

/**
 * Label wrapped around the front (+Z, facing the camera) of a cylinder section.
 * `arc` is the fraction of the circumference covered.
 */
function labelMesh(radius, height, y, options, arc = 0.62) {
    const geometry = new CylinderGeometry(radius, radius, height, SEGMENTS, 1, true, -Math.PI * arc, Math.PI * 2 * arc);
    const texture = labelTexture(options, {
        aspect: (2 * Math.PI * radius * arc) / height,
        // Roughly a third of the circumference faces the camera.
        visibleFraction: Math.min(1, 1 / 3 / arc),
    });
    const mesh = new Mesh(geometry, createLabelMaterial(texture));
    mesh.userData.defaultMap = texture;
    mesh.name = 'label';
    mesh.position.y = y;

    return mesh;
}

function capMesh(radiusTop, radiusBottom, height, y) {
    const mesh = new Mesh(new CylinderGeometry(radiusTop, radiusBottom, height, SEGMENTS), createCapMaterial());
    mesh.name = 'cap';
    mesh.position.y = y;

    return mesh;
}

/**
 * Uploaded label image when available, otherwise a generated one sized for this surface.
 */
function labelTexture(options, surface) {
    return options.labelTexture ?? options.makeLabel(surface);
}

/** Rounded profile helper: list of [radius, y] pairs to Vector2. */
const profile = (pairs) => pairs.map(([x, y]) => new Vector2(x, y));

function bottle(options) {
    const group = new Group();

    group.add(
        bodyMesh(
            profile([
                [0, -0.9], [0.36, -0.9], [0.42, -0.86], [0.44, -0.78], [0.44, 0.32],
                [0.42, 0.42], [0.34, 0.52], [0.2, 0.6], [0.15, 0.64], [0.15, 0.74], [0, 0.74],
            ]),
            options,
        ),
        labelMesh(0.445, 0.7, -0.24, options),
        capMesh(0.17, 0.17, 0.42, 0.95),
    );

    return group;
}

function jar(options) {
    const group = new Group();

    group.add(
        bodyMesh(
            profile([
                [0, -0.42], [0.6, -0.42], [0.66, -0.38], [0.68, -0.3], [0.68, 0.18],
                [0.64, 0.24], [0.6, 0.26], [0.6, 0.32], [0, 0.32],
            ]),
            options,
        ),
        labelMesh(0.685, 0.34, -0.07, options, 0.7),
        capMesh(0.69, 0.69, 0.3, 0.47),
    );

    return group;
}

function tube(options) {
    const group = new Group();

    // Squeeze tube standing on its cap: wide crimped end on top.
    const body = bodyMesh(
        profile([
            [0, -0.62], [0.12, -0.62], [0.2, -0.58], [0.32, -0.48], [0.34, -0.36],
            [0.36, 0.6], [0.36, 0.86], [0, 0.86],
        ]),
        options,
    );
    body.scale.set(1, 1, 0.72);

    const crimp = new Mesh(new CylinderGeometry(0.36, 0.36, 0.08, SEGMENTS), body.material);
    crimp.name = 'crimp';
    crimp.scale.set(1.08, 1, 0.12);
    crimp.position.y = 0.9;

    const label = labelMesh(0.362, 0.78, 0.18, options, 0.5);
    label.scale.set(1, 1, 0.72);

    group.add(body, crimp, label, capMesh(0.2, 0.22, 0.34, -0.79));

    return group;
}

function dropper(options) {
    const group = new Group();

    group.add(
        bodyMesh(
            profile([
                [0, -0.7], [0.3, -0.7], [0.34, -0.66], [0.35, -0.58], [0.35, 0.1],
                [0.32, 0.2], [0.22, 0.3], [0.13, 0.36], [0.13, 0.44], [0, 0.44],
            ]),
            options,
        ),
        labelMesh(0.355, 0.5, -0.26, options),
        capMesh(0.16, 0.16, 0.24, 0.55),
    );

    const bulb = new Mesh(new SphereGeometry(0.13, 48, 32), createRubberMaterial());
    bulb.name = 'bulb';
    bulb.scale.set(1, 1.6, 1);
    bulb.position.y = 0.84;
    group.add(bulb);

    return group;
}

function compact(options) {
    const group = new Group();

    const base = new Mesh(
        new CylinderGeometry(0.78, 0.74, 0.2, SEGMENTS),
        createBodyMaterial(options.material, options.color, options),
    );
    base.name = 'body';

    const lid = capMesh(0.78, 0.78, 0.12, 0.16);

    // Label sits on top of the lid as a disc.
    const texture = labelTexture(options, { aspect: 1, visibleFraction: 1 });
    const label = new Mesh(new CircleGeometry(0.56, SEGMENTS), createLabelMaterial(texture));
    label.name = 'label';
    label.userData.defaultMap = texture;
    label.rotation.x = -Math.PI / 2;
    label.position.y = 0.224;

    group.add(base, lid, label);
    group.rotation.x = 0.55;

    return group;
}
