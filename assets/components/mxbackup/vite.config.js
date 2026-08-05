import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'js/mgr/vue-dist',
        emptyOutDir: true,
        rollupOptions: {
            // Vue/Pinia/PrimeVue и composables приходят из Import Map пакета
            // VueTools — НЕ бандлим их, в выхлопе только код приложения.
            external: ['vue', 'pinia', 'primevue', /^@vuetools\//],
            input: {
                mxbackup: resolve(__dirname, 'js/mgr/main-mxbackup.js'),
            },
            output: {
                entryFileNames: '[name].min.js',
                chunkFileNames: '[name].min.js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'mxbackup.min.css';
                    }
                    return '[name].[ext]';
                },
                // Один файл: имя статичное, cache-bust по ?v=mtime из контроллера.
                inlineDynamicImports: true,
            },
        },
    },
});
