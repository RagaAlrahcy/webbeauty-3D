import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
                }),
                bunny('Cormorant Garamond', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                // Keep three.js in its own lazily-loaded chunk (never in app.js).
                manualChunks(id) {
                    if (id.includes('node_modules/three/')) return 'three';
                    if (id.includes('node_modules/gsap/')) return 'gsap';
                },
            },
        },
    },
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
