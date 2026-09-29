import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Allows `npm run dev` to work out of the box inside common local
        // VM/container setups (e.g. Homestead, WSL) without extra config.
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});
