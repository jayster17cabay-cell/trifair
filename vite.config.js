import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // The project keeps Tailwind's compiled file in public/css for
            // cache-busted Blade layouts. Use the actual source entry here so
            // `npm run build` remains reproducible for deployments and CI.
            input: ['resources/css/tailwind.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
