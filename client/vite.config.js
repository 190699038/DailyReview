import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueJsx()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },
  server: {
    proxy: {
      '/DailyReview/server': {
        target: 'http://127.0.0.1/DailyReview/server',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/server/, '')
      }
    }
  }
})
