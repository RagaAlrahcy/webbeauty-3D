/**
 * Frees GPU memory held by an object tree: geometries, materials and textures.
 *
 * @param {import('three').Object3D} root
 */
export function disposeObject(root) {
    root.traverse((object) => {
        object.geometry?.dispose();

        if (object.material) {
            const materials = Array.isArray(object.material) ? object.material : [object.material];
            materials.forEach(disposeMaterial);
        }
    });
}

/**
 * @param {import('three').Material} material
 */
export function disposeMaterial(material) {
    for (const value of Object.values(material)) {
        if (value && value.isTexture) value.dispose();
    }

    material.dispose();
}
