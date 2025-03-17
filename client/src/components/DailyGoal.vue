<template>
  <div class="page-container">
    <h2>个人日目标管理</h2>
    <el-button type="primary" @click="showAddDialog">新增任务</el-button>
    
    <el-table :data="tasks" style="width: 100%" border>
      <el-table-column prop="name" label="任务名称" width="180" />
      <el-table-column prop="progress" label="进度" width="100">
        <template #default="{ row }">
          {{ row.progress }}%
        </template>
      </el-table-column>
      <el-table-column label="操作">
        <template #default="{ row }">
          <el-button size="small" @click="editTask(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="deleteTask(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新增/编辑弹窗 -->
  </div>
</template>

<script setup>
import { ref } from 'vue'
import http from '@/utils/http'

const tasks = ref([])

const loadData = async () => {
  try {
    // const res = await http.get('/daily-tasks')
    // tasks.value = res.data
  } catch (error) {
    console.error('获取数据失败:', error)
  }
}

const showAddDialog = () => {
  // 显示新增弹窗
}

loadData()
</script>