import path from 'path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost/Service-scolarite/backend',
        changeOrigin: true,
        secure: false,
        // Ne pas réécrire le chemin, on veut garder /api dans l'URL
        rewrite: (path) => path,
        // Logs pour le débogage (désactivé par défaut, activer si nécessaire)
        // configure: (proxy, options) => {
        //   proxy.on('error', (err, req, res) => {
        //     console.log('proxy error', err);
        //   });
        // },
      },
    },
  },
})
