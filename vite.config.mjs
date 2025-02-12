import { defineConfig } from 'vite';
import { resolve } from 'path';
import sass from 'sass';
import basicSsl from '@vitejs/plugin-basic-ssl'
import cleanPlugin from 'vite-plugin-clean';

export default defineConfig({
  // Base public path when served in development or production
  base: '/',

  // Development server options
  server: {
    // Enable HTTPS
    https: true,
    hmr: {
      cssModules: true,
      protocol: 'wss', // Using secure WebSocket for HMR with HTTPS
    },
    cors: {
      origin: '*', // Allow all origins
      methods: ['GET', 'POST', 'PUT', 'DELETE'], // Allowed methods
      allowedHeaders: ['Content-Type', 'Authorization'], // Allowed headers
    },
  },

  // CSS configuration
  css: {
    devSourcemap: true,
    preprocessorOptions: {
      scss: {
        implementation: sass,
      },
    },
  },

  // Build configuration
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: false,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/js/main.js'),
        style: resolve(__dirname, 'src/scss/main.scss'),
      },
      output: {
        entryFileNames: 'js/[name].[hash].js',
        chunkFileNames: 'js/[name].[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name].[hash][extname]';
          }
          return 'assets/[name].[hash][extname]';
        }
      },
      external: ['jquery'],
    },
  },

  esbuild: {
    target: 'es2015',
  },

  plugins: [
    basicSsl(),
    cleanPlugin({
      targetFiles: ['dist'],
    }),
  ],
});