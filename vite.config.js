import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

const path = (relative) => fileURLToPath(new URL(relative, import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.jsx',
                // The visual page builder and the renderer for pages built with it.
                'resources/js/builder/main.tsx',
                'resources/js/builder-page.tsx',
            ],
            refresh: true,
        }),
        react(),
        // Only CSS that imports tailwindcss (the builder's styles) is processed.
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path('./resources/js'),
            '@shared': path('./resources/js/shared'),
            '@builder': path('./resources/js/builder'),
            '@renderer': path('./resources/js/renderer'),
        },
    },
    build: {
        target: 'es2022',
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
