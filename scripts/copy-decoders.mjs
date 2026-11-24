// Copies the Draco and KTX2 (Basis) decoders from three.js into public/,
// where product-model.js loads them from (/draco/ and /basis/).
// Run after upgrading three: `npm run copy-decoders`.
import { copyFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';

const libs = 'node_modules/three/examples/jsm/libs';

const files = {
    'public/draco': ['draco/gltf/draco_decoder.js', 'draco/gltf/draco_decoder.wasm', 'draco/gltf/draco_wasm_wrapper.js'],
    'public/basis': ['basis/basis_transcoder.js', 'basis/basis_transcoder.wasm'],
};

for (const [target, sources] of Object.entries(files)) {
    mkdirSync(target, { recursive: true });

    for (const source of sources) {
        copyFileSync(join(libs, source), join(target, source.split('/').pop()));
    }
}

console.log('Decoders copied to public/draco and public/basis.');
