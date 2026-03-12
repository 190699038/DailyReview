<template>
  <div class="upgrade-record">
    <!-- 原计划 el-tabs 切换，目前仅渲染 UpdateList -->
    <!-- <el-tabs v-model="activeTab">
      <el-tab-pane label="更新记录" name="list">
        <UpdateList />
      </el-tab-pane>
      <el-tab-pane label="更新记录数据统计" name="static">
        <UpdateStatic />
      </el-tab-pane>
    </el-tabs> -->
    <UpdateList />
  </div>
</template>

<script setup>
import UpdateList from '@/components/UpdateList.vue'
</script>

<style scoped>
.upgrade-record {
  height: 100%;
}
</style>
