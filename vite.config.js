import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            strategies: 'generateSW',

            includeAssets: [
                'favicon.ico',
                'apple-touch-icon.png',
            ],

            manifest: {
                name: 'Music Player',
                short_name: 'Player',
                display: 'standalone',
                theme_color: '#0f172a',
                background_color: '#0f172a',
                start_url: '/',
                icons: [
                { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
                ],
            },

            workbox: {
                cleanupOutdatedCaches: true,
                navigateFallback: '/',
            },
        }),
    ],
});
