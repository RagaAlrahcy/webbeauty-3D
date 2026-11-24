import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';
import { KTX2Loader } from 'three/addons/loaders/KTX2Loader.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';

/**
 * Loads an uploaded .glb model (Draco, Meshopt and KTX2 compressed assets supported).
 * Decoders are served from /draco/ and /basis/ (copied from three/examples).
 *
 * @param {string} url
 * @param {import('three').WebGLRenderer} renderer
 * @returns {Promise<import('three').Group>}
 */
export async function loadModel(url, renderer) {
    const draco = new DRACOLoader().setDecoderPath('/draco/');
    const ktx2 = new KTX2Loader().setTranscoderPath('/basis/').detectSupport(renderer);

    const loader = new GLTFLoader().setDRACOLoader(draco).setKTX2Loader(ktx2).setMeshoptDecoder(MeshoptDecoder);

    try {
        const gltf = await loader.loadAsync(url);

        return gltf.scene;
    } finally {
        draco.dispose();
        ktx2.dispose();
    }
}
