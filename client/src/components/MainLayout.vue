<template>
  <div class="main-layout">
    <!-- PC端导航 -->
    <el-menu
      v-if="!isMobile"
      :default-active="activeIndex"
      mode="horizontal"
      router
      class="nav-menu"
    >
          <!-- <el-menu-item index="/daily-goal">日目标管理</el-menu-item> -->
      <el-menu-item index="/week-goal">周目标管理</el-menu-item>
      <el-menu-item index="/test-task">测试任务</el-menu-item>
      <el-menu-item index="/system-setting">部门设置</el-menu-item>
      <div class="flex-grow" />
      <el-menu-item @click="themeDrawerVisible = true">
        <el-icon><Setting /></el-icon>
        主题设置
      </el-menu-item>
    </el-menu>

    <!-- 移动端Header -->
    <div v-else class="mobile-header">
      <div class="logo">周目标管理</div>
      <div class="mobile-actions">
        <el-icon class="action-icon" @click="themeDrawerVisible = true"><Setting /></el-icon>
        <el-icon class="menu-icon" @click="drawerVisible = true"><Menu /></el-icon>
      </div>
    </div>

    <!-- 移动端抽屉菜单 -->
    <el-drawer
      v-model="drawerVisible"
      title="菜单"
      direction="rtl"
      size="60%"
    >
      <el-menu
        :default-active="activeIndex"
        mode="vertical"
        router
        @select="drawerVisible = false"
      >
        <el-menu-item index="/week-goal">周目标管理</el-menu-item>
        <el-menu-item index="/test-task">测试任务</el-menu-item>
        <el-menu-item index="/system-setting">部门设置</el-menu-item>
      </el-menu>
    </el-drawer>

    <!-- 主题设置抽屉 -->
    <el-drawer
      v-model="themeDrawerVisible"
      title="主题设置"
      direction="rtl"
      :size="isMobile ? '80%' : '300px'"
    >
      <div class="theme-setting-content">
        <div class="setting-item">
          <span class="label">暗黑模式</span>
          <el-switch v-model="isDark" @change="toggleDark" />
        </div>
        
        <div class="setting-item">
          <span class="label">主题色</span>
          <div class="color-picker-container">
            <div 
              v-for="color in themeColors" 
              :key="color.value"
              class="color-block"
              :style="{ backgroundColor: color.value }"
              :class="{ active: primaryColor === color.value }"
              @click="setPrimaryColor(color.value)"
            >
              <el-icon v-if="primaryColor === color.value" class="check-icon"><Check /></el-icon>
            </div>
          </div>
        </div>
      </div>
    </el-drawer>

    <router-view class="content-container" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { onMounted, ref } from 'vue'
import http from '@/utils/http'
import {megerOAUserIDS} from '@/utils/dailyPlanAsync'
import { useResponsive } from '@/composables/useResponsive'
import { Menu, Setting, Check } from '@element-plus/icons-vue'
import { useTheme, themeColors } from '@/composables/useTheme'

const { isMobile } = useResponsive()
const { isDark, toggleDark, primaryColor, setPrimaryColor } = useTheme()
const drawerVisible = ref(false)
const themeDrawerVisible = ref(false)

const route = useRoute()
const activeIndex = route.path

onMounted(async () => {
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2
    const res = await http.get(`UserInfoAPI.php?action=get_users&department_id=${departmentId}`)
    localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
    megerOAUserIDS(departmentId)

    const all_users = await http.get('UserInfoAPI.php?action=get_all_users')

    localStorage.setItem('all_users', JSON.stringify(all_users.data))


  } catch (error) {
    console.error('用户信息初始化失败:', error)
  }
})
</script>

<style scoped>
.main-layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
}
.nav-menu {
  flex-shrink: 0;
}
.mobile-header {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  background-color: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color);
  transition: background-color 0.3s, border-color 0.3s;
}
.logo {
  font-weight: bold;
  font-size: 18px;
  color: var(--el-text-color-primary);
}
.menu-icon {
  font-size: 24px;
  cursor: pointer;
  color: var(--el-text-color-primary);
}
.content-container {
  flex: 1;
  padding: 20px;
  overflow: auto;
}
@media screen and (max-width: 768px) {
  .content-container {
    padding: 0;
  }
}

.flex-grow {
  flex-grow: 1;
}
.mobile-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}
.action-icon {
  font-size: 24px;
  cursor: pointer;
  color: var(--el-text-color-regular);
}
.theme-setting-content {
  padding: 0 10px;
}
.setting-item {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 24px;
}
.setting-item .label {
  font-size: 14px;
  color: var(--el-text-color-primary);
  font-weight: 500;
}
.color-picker-container {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.color-block {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}
.color-block:hover {
  transform: scale(1.1);
}
.color-block.active {
  box-shadow: 0 0 0 2px var(--el-bg-color), 0 0 0 4px var(--el-color-primary);
}
.check-icon {
  color: #fff;
  font-size: 14px;
}
</style>