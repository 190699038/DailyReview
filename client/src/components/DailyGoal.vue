<template>
  <div class="page-container">
    <el-row :gutter="20">
      <!-- 左侧队长目标管理 -->
      <el-col :span="8">
        <div class="goal-container">
          <h3>当日主要目标</h3>
          <el-form label-width="80px">
            <el-date-picker v-model="currentDate" type="date" value-format="YYYYMMDD" placeholder="选择日期"
              @change="getDailyGoal" style="margin-bottom: 8px;width: 130px;" />
            <el-input v-model="goalContent" type="textarea" :rows="30" placeholder="请输入当日主要目标" />
            <el-button type="primary" @click="saveGoal" style="margin-top: 8px;">保存目标</el-button>
          </el-form>
        </div>
      </el-col>

      <!-- 右侧任务卡片 -->
      <el-col :span="15">
        <div class="task-header">
          <el-button type="success" @click="handleImport"
            style="margin-bottom: 15px;margin-top: 10px;">导入昨日计划</el-button>
        </div>
        <!-- <div style="width: 100%;height: 95%;"> -->
        <el-row :gutter="16" style="margin-top:10px;height: 600px;">
          <el-col v-for="(user, index) in allTasks.data || []" :key="index" :xs="24" :sm="12" :md="12" :lg="16" :xl="12"
            style="margin-bottom:16px;">
            <el-card width="50%" height="600px" :style="{
                 backgroundColor: colorMap[user.id] || colors[index % colors.length],
                borderRadius: '8px'}" shadow="hover" @click="showUserDetail(user)" v-if="user.dailyGoals.length > 0">
              <div class="card-container">
                <!-- 顶部执行人 -->
                <div class="card-header">
                  <el-tag effect="dark">{{ user.dailyGoals&&user.dailyGoals.length>0&&user.dailyGoals[0].executor ? user.dailyGoals[0].executor:'' }}</el-tag>
                </div>

                <!-- 周目标区域 -->
                <div class="goal-area">
                  <div class="section-title">周目标</div>
                  <div class="scroll-content">
                    <template v-for="goal in user.dailyGoals" :key="goal.id">
                      <div class="goal-item" :style="{ backgroundColor: goal.is_new_goal === 1 ? '#FFF3CE' : '' }">{{ goal.weekly_goal }}</div>
                    </template>
                  </div>
                </div>

                <!-- 任务分解区域 -->
                <div class="task-area">
                  <div class="section-title">昨日任务清单</div>
                  <div class="scroll-content">
                    <el-table :key="tableKey" :data="user.dailyTasks.filter(task => parseFloat(task.time_spent) != 0) || []" border style="width: 100%" :row-class-name="taskClassName" >
                    <!-- <el-table-column prop="id" label="序号"  width="90" align="center" header-align="center" />
                    <el-table-column prop="date" label="日期"  width="100" align="center" header-align="center" /> -->
                    <el-table-column prop="merger" label="目标-方案" header-align="center"/>
                    <!-- <el-table-column prop="task_content" label="拆解任务" header-align="center"/> -->
                    <el-table-column prop="time_spent" label="耗时(小时)"  width="100" align="center" header-align="center"/>
                    <el-table-column prop="progress" label="进度"  width="90" align="center" header-align="center"/>
                  </el-table>
                  </div>
                </div>
              </div>
            </el-card>
          </el-col>
        </el-row>
        <!-- </div> -->
      </el-col>
    </el-row>

    <!-- 任务详情弹窗 -->
    <DayTaskInfo 
      ref="taskDetailRef"
      v-model:visible="taskDetailVisible"
      :executor-id="currentExecutorId"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { parseExcelFile } from '@/utils/excelParser'
import DayTaskInfo from '@/components/DayTaskInfo.vue'
import { megerOAUserIDS} from '@/utils/dailyPlanAsync'

const currentDate = ref(new Date().toISOString().slice(0, 10).replace(/-/g, ''))
const goalContent = ref('')
const tasks = ref([])
const allTasks = ref([])
const detailVisible = ref(false)
const currentTask = ref(null)
const colors = ref(['#f0f9eb', '#fdf6ec', '#fef0f0', '#f0f9ff'])
const colorMap = ref({})
const tableKey = ref(0);

const progressType = (value) => {
  const types = {
    0: '未开始',
    1: '进行中',
    2: '已完成',
    3: '已延期'
  }
  return types[value] || '未知状态'
}

const taskClassName = ({ row }) => {
  let style = ''
  if (row.progress === '100%') {
    style = 'green-row'
  }
  return style
}


const taskDetailVisible = ref(false)
const currentExecutorId = ref(0)
const taskDetailRef = ref(null)

const showUserDetail = (user) => {
  currentExecutorId.value = user.dailyGoals[0].executor_id
  taskDetailVisible.value = true

  taskDetailRef.value.loadTaskData(user.dailyGoals[0].executor_id, getMondayDate(currentDate.value))
}

// 获取当日目标
const getDailyGoal = async () => {
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2

    const res = await http.get('/DayGoalAPI.php', {
      params: {
        action: 'get_target',
        report_date: currentDate.value,
        department_id: departmentId
      }
    })
    goalContent.value = res?.content || ''
    // console.log('获取目标成功:', res?.content || '')
    fenxTodayTarget()
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
    const departmentId = localStorage.getItem('department_id_cache') || 2

    const formData = new URLSearchParams();
    formData.append('action', 'save_target');
    formData.append('report_date', currentDate.value);
    formData.append('content', goalContent.value);
    formData.append('department_id', departmentId);

    

    await http.post('DayGoalAPI.php', formData, {
      timeout: 30000,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
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
  const year = dateStr.substr(0, 4)
  const month = dateStr.substr(4, 2) - 1
  const day = dateStr.substr(6, 2)
  const date = new Date(year, month, day)
  const dayOfWeek = date.getDay()
  const adjustDays = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
  date.setDate(date.getDate() + adjustDays)
  return [
    date.getFullYear(),
    (date.getMonth() + 1).toString().padStart(2, '0'),
    date.getDate().toString().padStart(2, '0')
  ].join('')
}


const getYesterdayDate = () => {
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
    if(userIds.length == 0){
      return 
    }

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

    for(let i = 0; i < allTasks.value.data.length; i++){
      let user = allTasks.value.data[i]
      for(let j = 0; j < user.dailyTasks.length; j++){
        allTasks.value.data[i].dailyTasks[j].merger = user.dailyTasks[j].day_goal+'-'+user.dailyTasks[j].task_content
        tableKey.value = tableKey+1
      }
    }

    console.log(allTasks)


  } catch (error) {
    console.error('获取任务失败:', error);
    ElMessage.error('数据获取失败，请检查控制台');
  }

}

const fenxTodayTarget = ()=>{
    //goalContent.value


  }

// 保存目标
const batchGoal = async (transformedData) => {

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
        time_spent: task.time_spent == 'None'? '-1' : task.time_spent,
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

  //  let users = localStorage.getItem('departments_user_cache');
  //  users = JSON.parse(users)
  //  let singleOAID =  users[0].oa_userid; 
  //   if (singleOAID == null || singleOAID == ''){
  //     megerOAUserIDS()
  //     return 
  //   }



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
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
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

/* 新增卡片样式 */
.card-container {
  height: auto;
  display: flex;
  flex-direction: column;
}

.card-header {
  height: 10%;
  margin-bottom: 8px;
}

.goal-area {
  height: 40%;
  margin-bottom: 12px;
}

.task-area {
  height: 50%;
}

.scroll-content {
  overflow-y: auto;
  height: calc(100% - 24px);
  padding-right: 8px;
}

.section-title {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
}

.goal-item,
.task-item {
  font-size: 13px;
  padding: 6px 8px;
  margin-bottom: 6px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.8);
}

.task-item {
  transition: all 0.3s;
  cursor: pointer;
}

.task-item:hover {
  transform: translateX(4px);
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


.green-row {
  background-color: #A9D08D !important;
}
</style>


