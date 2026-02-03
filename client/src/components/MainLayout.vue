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
    </el-menu>

    <!-- 移动端Header -->
    <div v-else class="mobile-header">
      <div class="logo">周目标管理</div>
      <el-icon class="menu-icon" @click="drawerVisible = true"><Menu /></el-icon>
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

    <router-view class="content-container" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { onMounted, ref } from 'vue'
import http from '@/utils/http'
import {megerOAUserIDS} from '@/utils/dailyPlanAsync'
import { useResponsive } from '@/composables/useResponsive'
import { Menu } from '@element-plus/icons-vue'

const { isMobile } = useResponsive()
const drawerVisible = ref(false)

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
  background-color: #fff;
  border-bottom: 1px solid #dcdfe6;
}
.logo {
  font-weight: bold;
  font-size: 18px;
}
.menu-icon {
  font-size: 24px;
  cursor: pointer;
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
</style>