<template>
  <div class="content-container">
    <div class="filter-area">
      <el-date-picker
        v-model="dateRange"
        type="daterange"
        range-separator="至"
        start-placeholder="开始日期"        end-placeholder="结束日期"
        style="margin-right: 10px; max-width: 400px;"
      />
      <el-button type="primary" @click="handleSearch">查询</el-button>
      <el-button type="success" @click="handleScreenshot" style="margin-left: 10px;">列表截图</el-button>
      <el-button type="warning" @click="handleCopy">内容拷贝</el-button>
    </div>

    <el-table :data="tableData" border style="width: 100%; margin-top: 20px">
      <el-table-column type="index" label="序号" width="60" align="center" header-align="center"/>
      <el-table-column prop="date" label="日期" width="120" align="center"  header-align="center"/>
      <el-table-column prop="executor_name" label="用户姓名" width="120" align="center"  header-align="center"/>
      <el-table-column prop="department_name" label="项目组" width="150" align="center"  header-align="center"/>
      <el-table-column prop="day_goal" label="目标" min-width="200" />
      <el-table-column prop="task_content" label="目标分解" min-width="250" />
      <el-table-column prop="time_spent" label="预计耗时" width="100" align="center"  header-align="center" />
    </el-table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import html2canvas from 'html2canvas'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'

const dateRange = ref([new Date(Date.now() - 6 * 24 * 60 * 60 * 1000), new Date()])
const userOptions = ref([])
const tableData = ref([])

const fetchUserOptions = async () => {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_watch_users')
    userOptions.value = res.data
    handleSearch()
  } catch (error) {
    ElMessage.error('获取用户列表失败')
    console.error(error)
  }
}

const handleSearch = async () => {
  try {
    // const params = {
    //   users: selectedUsers.value.join(','),
    //   start_date: dateRange.value[0]?.toISOString().split('T')[0] || '',
    //   end_date: dateRange.value[1]?.toISOString().split('T')[0] || ''
    // }

    // const res = await http.get('DayTaskAPI.php', { params })
    // tableData.value = res.data.map(item => ({
    //   ...item,
    //   sub_goals: item.sub_goals?.join('\n') || ''
    // }))

    const formData = new URLSearchParams();
    formData.append('action', 'getUserTodayTask');
    formData.append('uids', userOptions.value.map(user => user.executor_id).join(','));
    formData.append('start_date', dateRange.value[0] ? 
      `${dateRange.value[0].getFullYear()}${(dateRange.value[0].getMonth() + 1).toString().padStart(2, '0')}${dateRange.value[0].getDate().toString().padStart(2, '0')}` : '' );
    formData.append('end_date', dateRange.value[1] ? 
      `${dateRange.value[1].getFullYear()}${(dateRange.value[1].getMonth() + 1).toString().padStart(2, '0')}${dateRange.value[1].getDate().toString().padStart(2, '0')}` : '' );

    let tempTask = await http.post('DayTaskAPI.php', formData, {
      timeout: 30000,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });

    tableData.value = tempTask;

  } catch (error) {
    ElMessage.error('查询失败')
    console.error(error)
  }
}

onMounted(() => {
  fetchUserOptions()
})

const handleScreenshot = () => {
  html2canvas(document.querySelector('.el-table')).then(async canvas => {
    try {
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
      await navigator.clipboard.write([
        new ClipboardItem({ 'image/png': blob })
      ]);
      ElMessage.success('截图已复制到剪贴板')
    } catch (error) {
      if (error.name === 'SecurityError') {
        ElMessage.error('请授权剪贴板权限')
      } else {
        ElMessage.error('截图复制失败')
      }
    }
  }).catch(() => {
    ElMessage.error('截图生成失败')
  })
}

const handleCopy = async () => {
  try {
    const text = tableData.value.map(item => 
      `日期：${item.date}｜姓名：${item.executor_name}｜项目组：${item.department_name}｜目标：${item.day_goal}｜任务：${item.task_content.replace(/\n/g, ' ')}｜耗时：${item.time_spent}小时`
    ).join(' \n')
    
    await navigator.clipboard.writeText(text)
    ElMessage.success('已复制到剪贴板')
  } catch (error) {
    ElMessage.error('复制失败')
    console.error(error)
  }
}
</script>

<style scoped>
.filter-area {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px;
  background: #f5f7fa;
  border-radius: 4px;
}
</style>