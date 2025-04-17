<template>
  <el-dialog :model-value="visible" title="用户任务详情" width="70%" @update:model-value="emit('update:visible', $event)">
    <el-tabs v-model="activeTab" type="card" @tab-change="handleTabChange">
      <el-tab-pane
        v-for="(day, index) in weekDays"
        :key="index"
        :label="day.label"
        :name="day.name"
      >
      <h2>{{ day.date }}  {{ day.label }} </h2>

      <div class="content-container-top">
        <div style="margin-bottom: 5px;margin-left: 1px;">周目标</div>

          <el-table :data="dailyGoals || []" border style="width: 100%" :row-class-name="rowClassName">
            <el-table-column prop="weekly_goals_id" label="序号" width="90" />
            <el-table-column prop="executor" label="执行人"  width="90" align="center" header-align="center"/>
            
            <el-table-column label="优先级" width="80" align="center" header-align="center">
              <template #default="{row}">{{  {10:'S',9:'A',8:'B',7:'C',6:'C',5:'C',4:'C',3:'C',2:'C'}[row.priority] }}</template>
            </el-table-column>
            
            <el-table-column prop="weekly_goal" label="目标" header-align="center"/>
            
            <el-table-column label="状态" width="120" align="center" header-align="center">
              <template #default="{row}">
                {{ {1:'进行中',2:'测试中',3:'已上线',4:'已暂停',0:'未开始'}[row.status] || '未知状态' }}
              </template>
            </el-table-column>
          </el-table>
        </div>


        <div class="content-container">
          <div style="margin-bottom: 5px;margin-left: 1px;">任务清单</div>

          <el-table :key="tableKey" :data="dailyTasks.filter(task => parseFloat(task.time_spent) != 0)  || []" border style="width: 100%" :row-class-name="taskClassName">
            <el-table-column prop="id" label="序号"  width="90" align="center" header-align="center" />
            <el-table-column prop="date" label="日期"  width="100" align="center" header-align="center" />

            <el-table-column prop="day_goal" label="目标" header-align="center"/>
            <el-table-column prop="task_content" label="拆解任务" header-align="center"/>
            <el-table-column prop="time_spent" label="耗时(小时)"  width="100" align="center" header-align="center"/>
            <el-table-column prop="progress" label="进度"  width="90" align="center" header-align="center"/>

          </el-table>
        </div>
      </el-tab-pane>
    </el-tabs>
  </el-dialog>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { parseExcelFile } from '@/utils/excelParser'
import { getDailyPlanWithExecutorId} from '@/utils/dailyPlanAsync'

const currentDate = ref({});
const tasks = ref({});
const obj = ref({})
const dailyGoals = ref([]);
const dailyTasks = ref([]);
const tableKey = ref(0);
const today =  new Date().toISOString().slice(0, 10).replace(/-/g, '')
var idx = 1;

const props = defineProps({
  visible: Boolean,
  executorId: Number
})

const emit = defineEmits(['update:visible'])

const activeTab = ref('0')
const taskData = ref([])
const index = ref(0)

const rowClassName = ({ row }) => {
  let style = ''
  if (row.status === 3) {
    style = 'green-row'
  }else{
    if(row.is_new_goal === 1){
      style = 'highlight-row'
    }
  }
  return style
}

const taskClassName = ({ row }) => {
  let style = ''
  if (row.progress === '100%') {
    style = 'green-row'
  }
  return style
}


// 加载任务数据
const loadTaskData = async (executor_id,monday_date) => {
  console.log("loadTaskData called with executor_id:", executor_id);
  if(executor_id == null || monday_date == null){
    return
  }
  try {
    obj.monday_date = monday_date
    obj.executor_id = executor_id


    let weeeks = getWeekDates()
    let date_str = ""
    for (let i = 0; i < weeeks.length; i++) {
      const week = weeeks[i];
      date_str += week + ","
    }
    const formData = new URLSearchParams();
    formData.append('action', 'getUserGoalAndTasks');
    formData.append('monday_date', monday_date);
    formData.append('executor_id', executor_id); 
    formData.append('dates', date_str); //周一到今天

    let tempTask = await http.post('DayTaskAPI.php', formData, {
      timeout: 30000,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });

    
    for( let i = 0; i < tempTask.data.length; i++) {
      let item = tempTask.data[i]
      if(item.date == today && item.dailyTasks.length <= 0){
        let newTasks  = await getDailyPlanWithExecutorId(true,executor_id)
        for(let j = 0; j < newTasks.length; j++){
          let o = newTasks[j]
          let t = {}
          t.executor = ''
          t.executor_id = executor_id
          t.date = o.createdAt.replace(/^(\d{4})-(\d{2})-(\d{2}).*/, '$1$2$3')
          t.progress = o.complete != null && o.complete != 'null' ? o.complete+'%' : '0%'
          t.time_spent = o.e_time == null ? '-1' : o.e_time
          t.day_goal = o.d_describe
          t.task_content = o.p_describe
          t.id = o.id
          console.log(t)
          item.dailyTasks.push(t)
        }

      }
    }
    tasks.value = tempTask;
    console.log(tempTask)
    idx = idx + 1
    initActiveTab()
    drawTable()


  
    
    // drawTable()
    // ElMessage.success('获取任务成功');
  } catch (error) {
    console.error('获取任务失败:', error.response?.data || error.message);
    ElMessage.error(`获取任务失败: ${error.response?.data?.message || '服务器异常'}`);
  }
}
defineExpose({ loadTaskData }); // 关键！暴露方法给父组件




const drawTable =  () => {
  taskData.value = [];

  const dailyGoal = Array.isArray(tasks.value?.data?.[obj.tabIndex]?.dailyGoals) ? tasks.value.data[obj.tabIndex].dailyGoals : [];

  const dailyTask = Array.isArray(tasks.value?.data?.[obj.tabIndex]?.dailyTasks) ? tasks.value.data[obj.tabIndex].dailyTasks : [];

  dailyGoals.value = dailyGoal;

  dailyTasks.value = dailyTask//[...dailyTask];

  tableKey.value += 1;

  console.log(dailyGoal)

  console.log(dailyTask)
}
const  getWeekDates = () => {
  const today = new Date(); // 当前日期（2025-03-20）
  const currentDay = today.getDay() === 0 ? 7 : today.getDay(); // 转换为ISO星期码（周四=4）
  const monday = new Date(today);
  monday.setDate(today.getDate() - (currentDay - 1)); // 计算本周一（2025-03-17）

  const dates = [];
  for (let i = 0; i < 7; i++) {
    const date = new Date(monday);
    date.setDate(monday.getDate() + i); // 从周一开始累加天数
    dates.push(formatDate(date));
  }
  return dates;
}
const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // 补零处理
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}${month}${day}`; // 格式：YYYYMMDD
}
 const getDateByWeekday = (weekday) => {
  const dates = getWeekDates();
  return dates[weekday - 1]; // 索引0=周一，索引6=周日
}


// 处理标签切换
const handleTabChange = (tabName) => {
    console.log(tabName)
    let week = parseInt(tabName)
    index.value = week ;
    obj.tabIndex = index.value;
    currentDate.value = getDateByWeekday(week)
    drawTable()

//   loadTaskData(tabName)
}

// 初始化加载数据
loadTaskData(activeTab.value)

// 原临时空数据

const weekDays = [
  { label: '周一', name: '1' },
  { label: '周二', name: '2' },
  { label: '周三', name: '3' },
  { label: '周四', name: '4' },
  { label: '周五', name: '5' },
  { label: '周六', name: '6' },
  { label: '周日', name: '7' }
]


// 自动设置默认激活的Tab
const initActiveTab = () => {
  const now = new Date();
  const options = { weekday: 'long', timeZone: 'Asia/Shanghai' };
  const localWeekday = now.toLocaleDateString('en-US', options);
  const weekdays = {
    'Monday': '0',
    'Tuesday': '1',
    'Wednesday': '2',
    'Thursday': '3',
    'Friday': '4',
    'Saturday': '5',
    'Sunday': '6'
  };
  activeTab.value = parseInt(weekdays[localWeekday]);

  obj.tabIndex = parseInt(activeTab.value) 

  for (let index = 0; index < weekDays.length; index++) {
    weekDays[index].date = getDateByWeekday(index+1);
  }

  handleTabChange(weekdays[localWeekday])
}

initActiveTab()
</script>

<style scoped>
.content-container {
  padding: 20px;
  background: #f8f8f8;
  border-radius: 8px;
}

.content-container-top {
  padding: 20px;
  background: #f8f8f8;
  border-radius: 8px;
}
.highlight-row {
  background-color: #FFF3CE !important;
}

.green-row {
  background-color: #A9D08D !important;
}

.el-tag {
  margin-right: 10px;
}
</style>