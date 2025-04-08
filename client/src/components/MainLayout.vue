<template>
  <div class="main-layout">
    <el-menu
      :default-active="activeIndex"
      mode="horizontal"
      router
      class="nav-menu"
    >
      <el-menu-item index="/daily-goal">日目标管理</el-menu-item>
      <el-menu-item index="/week-goal">周目标管理</el-menu-item>
      <el-menu-item index="/history-daily">历史记录</el-menu-item>
      <el-menu-item index="/system-setting">部门设置</el-menu-item>
    </el-menu>
    <router-view class="content-container" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { onMounted } from 'vue'
import http from '@/utils/http'
import {megerOAUserIDS} from '@/utils/dailyPlanAsync'

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
.content-container {
  flex: 1;
  padding: 20px;
  overflow: auto;
}
</style>