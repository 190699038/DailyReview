
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
    assetsDir: 'assets'
  },
  assetsInclude: ['**/*.svg'],
  server: {
    host: '0.0.0.0',

    proxy: {
      '/server': {
        target: 'https://daily.gameyzy.com',
        changeOrigin: true,
        secure: true
      }
    }
  }
})