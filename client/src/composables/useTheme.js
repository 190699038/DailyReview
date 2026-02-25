import { ref, watch, onMounted } from 'vue'

const isDark = ref(false)
const primaryColor = ref('#409EFF')

// 预定义的主题色
export const themeColors = [
  { name: '默认蓝', value: '#409EFF' },
  { name: '极客黑', value: '#181818' }, // 注意：极客黑通常作为背景，作为主色可能不明显，这里仅演示
  { name: '充满绿', value: '#67C23A' },
  { name: '活力橙', value: '#E6A23C' },
  { name: '热情红', value: '#F56C6C' },
  { name: '优雅紫', value: '#722ED1' },
  { name: '少女粉', value: '#eb2f96' },
]

export function useTheme() {
  
  // 切换暗黑模式
  const toggleDark = (value) => {
    isDark.value = value
    const html = document.documentElement
    if (value) {
      html.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      html.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }

  // 设置主题色
  const setPrimaryColor = (color) => {
    primaryColor.value = color
    localStorage.setItem('primaryColor', color)
    
    const el = document.documentElement
    // 设置 CSS 变量
    el.style.setProperty('--el-color-primary', color)
    
    // 计算并设置主色的混合色（用于 hover, active 等状态）
    // 这里简单处理，Element Plus 实际上有一套复杂的混合算法
    // 为了简化，我们使用 CSS 变量的 alpha 通道或者简单的混合
    // 但 Element Plus 依赖 --el-color-primary-light-x 变量
    
    // 生成 light-3, light-5, light-7, light-8, light-9, dark-2
    for (let i = 1; i <= 9; i++) {
      el.style.setProperty(`--el-color-primary-light-${i}`, mixColor(color, '#ffffff', i * 0.1))
    }
    el.style.setProperty(`--el-color-primary-dark-2`, mixColor(color, '#000000', 0.2))
  }

  // 颜色混合函数
  const mixColor = (color1, color2, weight) => {
    weight = Math.max(Math.min(Number(weight), 1), 0)
    let r1 = parseInt(color1.substring(1, 3), 16)
    let g1 = parseInt(color1.substring(3, 5), 16)
    let b1 = parseInt(color1.substring(5, 7), 16)
    let r2 = parseInt(color2.substring(1, 3), 16)
    let g2 = parseInt(color2.substring(3, 5), 16)
    let b2 = parseInt(color2.substring(5, 7), 16)
    let r = Math.round(r1 * (1 - weight) + r2 * weight)
    let g = Math.round(g1 * (1 - weight) + g2 * weight)
    let b = Math.round(b1 * (1 - weight) + b2 * weight)
    const _r = ('0' + (r || 0).toString(16)).slice(-2)
    const _g = ('0' + (g || 0).toString(16)).slice(-2)
    const _b = ('0' + (b || 0).toString(16)).slice(-2)
    return '#' + _r + _g + _b
  }

  // 初始化主题
  onMounted(() => {
    const savedTheme = localStorage.getItem('theme')
    const savedColor = localStorage.getItem('primaryColor')
    
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      toggleDark(true)
    } else {
      toggleDark(false)
    }

    if (savedColor) {
      setPrimaryColor(savedColor)
    }
  })

  return {
    isDark,
    toggleDark,
    primaryColor,
    setPrimaryColor,
    themeColors
  }
}
