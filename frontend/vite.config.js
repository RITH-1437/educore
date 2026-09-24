import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import laravel from 'laravel-vite-plugin'
import path from 'node:path'

const appUrl = process.env.APP_URL || 'http://localhost'

// Place the Vite manifest + built assets where Laravel can serve them.
// In the Docker frontend container this is a bind-mounted /backend/public;
// outside Docker it resolves to ../backend/public relative to this project.
const backendPublic = process.env.BACKEND_PUBLIC_DIR
  ? path.resolve(process.env.BACKEND_PUBLIC_DIR)
  : path.resolve(process.cwd(), '../backend/public')

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    laravel({
      input: ['src/app.js'],
      buildDirectory: path.join(backendPublic, 'build'),
      hotFile: path.join(backendPublic, 'hot'),
    }),
    {
      name: 'redirect-root-to-app',
      configureServer(server) {
        server.middlewares.use((req, res, next) => {
          if (req.url === '/') {
            res.statusCode = 302
            res.setHeader('Location', appUrl + '/')
            res.end()
            return
          }
          next()
        })
      },
    },
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      host: 'localhost',
      port: 5173,
    },
  },
  build: {
    outDir: path.join(backendPublic, 'build'),
    emptyOutDir: true,
  },
})