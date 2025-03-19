<template>
  <div class="page-container">
    <el-row :gutter="20">
      <!-- 左侧队长目标管理 -->
      <el-col :span="8">
        <div class="goal-container">
          <h3>当日主要目标</h3>
          <el-form label-width="80px">
            <el-date-picker
                v-model="currentDate"
                type="date"
                value-format="YYYYMMDD"
                placeholder="选择日期"
                @change="getDailyGoal"
                style="margin-bottom: 8px;width: 130px;"
              />
              <el-input
                v-model="goalContent"
                type="textarea"
                :rows="30"
                placeholder="请输入当日主要目标"
              />
            <el-button type="primary" @click="saveGoal" style="margin-top: 8px;">保存目标</el-button>
          </el-form>
        </div>
      </el-col>

      <!-- 右侧任务卡片 -->
      <el-col :span="15">
        <div class="task-header">
            <el-button type="success" @click="handleImport" style="margin-bottom: 15px;margin-top: 10px;">导入昨日计划</el-button>
          </div>
          <div class="page_view">
            
          </div>
      </el-col>
    </el-row>

    <!-- 任务详情弹窗 -->
    <el-dialog v-model="detailVisible" title="任务详情" width="60%">
      <div v-if="currentTask">
        <h4>{{ currentTask.title }}</h4>
        <p>{{ currentTask.content }}</p>
        <el-tag type="info">创建时间：{{ currentTask.create_time }}</el-tag>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { parseExcelFile } from '@/utils/excelParser'

const currentDate = ref(new Date().toISOString().slice(0,10).replace(/-/g, ''))
const goalContent = ref('')
const tasks = ref([])
const allTasks = ref([])
const detailVisible = ref(false)
const currentTask = ref(null)

// 获取当日目标
const getDailyGoal = async () => {
  try {
    const res = await http.get('/DayGoalAPI.php', {
      params: {
        action: 'get_target',
        report_date: currentDate.value
      }
    })
    goalContent.value = res?.content || ''
    // console.log('获取目标成功:', res?.content || '')
  } catch (error) {
    console.error('获取目标失败:', error)
  }
}

const saveGoal = async () => {
  if (!goalContent.value.trim()) {
    ElMessage.warning('目标内容不能为空');
    return;
  }
  try {
    const formData = new URLSearchParams();
    formData.append('action', 'save_target');
    formData.append('report_date', currentDate.value);
    formData.append('content', goalContent.value);

    await http.post('DayGoalAPI.php', formData, {
      timeout: 30000,
      headers: {
        'Content-Type':'application/x-www-form-urlencoded'
      }
    });
    ElMessage.success('保存成功');
    getDailyGoal();
  } catch (error) {
    console.error('保存失败:', error.response?.data || error.message);
    ElMessage.error(`保存失败: ${error.response?.data?.message || '服务器异常'}`);
  }
}

const getMondayDate = (dateStr) => {
  const year = dateStr.substr(0,4)
  const month = dateStr.substr(4,2) - 1
  const day = dateStr.substr(6,2)
  const date = new Date(year, month, day)
  const dayOfWeek = date.getDay()
  const adjustDays = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  date.setDate(date.getDate() + adjustDays)
  return [
    date.getFullYear(),
    (date.getMonth()+1).toString().padStart(2,'0'),
    date.getDate().toString().padStart(2,'0')
  ].join('')
}


const getYesterdayDate = () =>{
  const date = new Date();
  date.setDate(date.getDate() - 1); // 减去一天
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // 补零到两位
  const day = String(date.getDate()).padStart(2, '0');         // 补零到两位
  return `${year}${month}${day}`;
}

const getUserGoalAndTasks = async () => {
  try {
    const cache = localStorage.getItem('departments_user_cache');
    let users = cache ? JSON.parse(cache) : [];
    const userIds = users.map(u => u.id);

    const formData = new URLSearchParams();
    formData.append('action', 'getUserGoalAndTasks');
    formData.append('last_date', getYesterdayDate());
    formData.append('monday_date', getMondayDate(currentDate.value));
    formData.append('user_ids', userIds.join(','));

    const res = await http.post('DayGoalAPI.php', formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });
    allTasks.value = res || [];
    console.log(allTasks)

    console.log(JSON.stringify(allTasks.value.data[0].dailyGoals[0]))
    console.log(JSON.stringify(allTasks.value.data[1].dailyTasks[0]))

  } catch (error) {
    console.error('获取任务失败:', error);
    ElMessage.error('数据获取失败，请检查控制台');
  }
}


// 保存目标
const batchGoal = async (transformedData) => {
  if (!goalContent.value.trim()) {
    ElMessage.warning('目标内容不能为空');
    return;
  }

  try {

    await http.post('DayGoalAPI.php', transformedData, {
      params: { action: 'batch_create' },
      timeout: 30000,
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    ElMessage.success('保存成功');
    getDailyGoal();
  } catch (error) {
    console.error('保存失败:', error.response?.data || error.message);
    ElMessage.error(`保存失败: ${error.response?.data?.message || '服务器异常'}`);
  }
}

// 显示任务详情
const showTaskDetail = (task) => {
  currentTask.value = task
  detailVisible.value = true
}

// 文件导入处理
const handleImport = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.xlsx'
  
  input.onchange = async (e) => {
    const file = e.target.files[0]
    if (!file) return
    try {
      const importedTasks = await parseExcelFile(file, 'daily')
      tasks.value = importedTasks.map(task => ({
        executor: task.executor,
        progress: task.progress,
        time_spent: task.time_spent,
        date: task.date,
        day_goal: task.day_goal,
        task_content: task.task_content,
        executor_id: task.executor_id,
      }))
      ElMessage.success(`成功导入${importedTasks.length}条任务`)
      batchGoal(tasks.value)
    } catch (error) {
      console.error('导入失败:', error)
      ElMessage.error('文件解析失败，请使用标准模板文件')
    }
  }
  
  input.click()
}

// 加载初始数据
onMounted(() => {
  getDailyGoal()
  getUserGoalAndTasks()
})
</script>

<style scoped>
.goal-container {
  padding: 20px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}
.task-grid {
  padding: 10px;
  background-color: aqua;
}
.task-card {
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.2s;
}
.task-card:hover {
  transform: translateY(-5px);
}
.card-content {
  height: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.goal-container h3 {
  margin: 0 0 10px !important;
  padding-bottom: 8px !important;
  border-bottom: 1px solid #eee;
}
</style>



