
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  base: './', // 添加这行确保资源使用相对路径
  publicPath: './',
  plugins: [vue(), vueJsx()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },
  build: {
    sourcemap: true, // 生成 Sourcemap
  },
  server: {
    host: '0.0.0.0',

    proxy: {
      '/DailyReview/server': {
        target: 'http://127.0.0.1/DailyReview/server',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/server/, '')
      }
    }
  }
})