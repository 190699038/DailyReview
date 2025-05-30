<template>
  <el-dialog :model-value="visible" title="用户任务详情" width="70%" style="height:auto;margin-top: 25px;"
    @update:model-value="emit('update:visible', $event)">
    <div class="user-tabs-container">
      <el-tabs v-model="props.executorId" tab-position="left" class="user-tabs" @tab-change="handleUserChange">
        <el-tab-pane v-for="user in userList" :key="user.id" :label="user.partner_name" :name="user.id" />
      </el-tabs>
    </div>

    <div class="content-area">
      <el-tabs v-model="activeTab" type="card" @tab-change="handleTabChange">
        <el-tab-pane v-for="(day, index) in weekDays" :key="index" :label="day.label" :name="day.name">
          <div class="content_area">
            <h2>{{ day.date }} {{ day.label }} </h2>

            <div class="content-container-top">
              <div style="margin-bottom: 5px;margin-left: 1px;">周目标</div>

              <el-table :data="dailyGoals || []" border style="width: 100%" :row-class-name="rowClassName">
                <el-table-column prop="weekly_goals_id" label="序号" width="90" align="center" header-align="center" />
                <el-table-column prop="executor" label="执行人" width="90" align="center" header-align="center" />

                <el-table-column label="优先级" width="80" align="center" header-align="center">
                  <template #default="{ row }">{{ { 10: 'S', 9: 'A', 8: 'B', 7: 'C', 6: 'C', 5: 'C', 4: 'C', 3: 'C', 2: 'C' }[row.priority]
                    }}</template>
                </el-table-column>

                <el-table-column prop="weekly_goal" label="目标" header-align="center" />

                <el-table-column label="状态" width="120" align="center" header-align="center">
                  <template #default="{ row }">
                    {{ { 1: '进行中', 2: '测试中', 3: '已上线', 4: '已暂停', 0: '未开始' }[row.status] || '未知状态' }}
                  </template>
                </el-table-column>
              </el-table>
            </div>


            <div class="content-container">
              <div style="margin-bottom: 5px;margin-left: 1px;">任务清单</div>

              <el-table :key="tableKey" :data="dailyTasks.filter(task => parseFloat(task.time_spent) > 0) || []" border
                style="width: 100%" :row-class-name="taskClassName">
                <el-table-column prop="id" label="序号" width="90" align="center" header-align="center" />
                <el-table-column prop="date" label="日期" width="100" align="center" header-align="center" />

                <el-table-column prop="day_goal" label="目标" header-align="center" />
                <el-table-column prop="task_content" label="拆解任务" header-align="center" />
                <el-table-column prop="time_spent" label="耗时(小时)" width="100" align="center" header-align="center" />
                <el-table-column prop="progress" label="进度" width="90" align="center" header-align="center" />
                <el-table-column label="操作" width="100" header-align="center" align="center">
                  <template #default="{ row }">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 8px">
                      <el-button size="small" @click="showDialog('edit', row)">修改</el-button>
                      <!-- <el-button size="small" type="danger" @click="deleteTask(row)">删除</el-button> -->
                    </div>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </div>

        </el-tab-pane>

        <el-dialog v-model="dialogVisible" :title="dialogTitle" width="40%">
          <el-form :model="form" :rules="rules" label-width="100px">
            <el-form-item label="耗时（小时）" prop="time_spent">
              <el-input-number v-model="form.time_spent" :min="0" :precision="1" :step="0.5" />
            </el-form-item>
            <el-form-item label="进度" prop="progress">
              <el-input v-model.number="form.progress" placeholder="输入百分比（0-100）">
                <template #append>%</template>
              </el-input>
            </el-form-item>
            <el-form-item label="新增需求标记">
              <el-switch v-model="form.is_new_goal" :active-value="1" :inactive-value="0" active-text="是"
                inactive-text="否" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="submitForm">保存</el-button>
              <el-button @click="dialogVisible = false">取消</el-button>
            </el-form-item>
          </el-form>
        </el-dialog>
      </el-tabs>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import { parseExcelFile } from '@/utils/excelParser'
import { getDailyPlanWithExecutorId } from '@/utils/dailyPlanAsync'

const currentDate = ref({});
const tasks = ref({});
const obj = ref({})
const userinfo = ref({})
const dialogVisible = ref(false);
const dialogTitle = ref('');
const form = ref({
  id: null,
  time_spent: 0,
  progress: 0,
  is_new_goal: 0
});
const rules = {
  time_spent: [{ required: true, message: '请输入耗时', trigger: 'blur' }],
  progress: [
    { required: true, message: '请输入进度', trigger: 'blur' },
    { pattern: /^\d+(\.\d+)?$/, message: '进度必须为数字', trigger: 'blur' },
    { validator: (_, v) => v <= 100 ? Promise.resolve() : Promise.reject('进度不能超过100%'), trigger: 'blur' }
  ]
};
const dailyGoals = ref([]);
const dailyTasks = ref([]);
const tableKey = ref(0);
const today = new Date().toISOString().slice(0, 10).replace(/-/g, '')
const jinRi = ref(new Date().toISOString().slice(0, 10).replace(/-/g, ''))
const userList = ref([])



const handleUserChange = (newId) => {
  loadTaskData(newId, obj.monday_date, false)
}

const props = defineProps({
  visible: Boolean,
  executorId: Number,
})

const emit = defineEmits(['update:visible'])

const activeTab = ref('')
const taskData = ref([])
const index = ref(0)

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


const rowClassName = ({ row }) => {
  let style = ''
  if (row.status === 3) {
    style = 'green-row'
  } else {
    if (row.is_new_goal === 1) {
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

const submitForm = async () => {
  try {
    const formData = new URLSearchParams();
    formData.append('action', 'updateTask');
    formData.append('id', form.value.id);
    formData.append('time_spent', form.value.time_spent);
    formData.append('progress', form.value.progress + '%');
    formData.append('is_new_goal', form.value.is_new_goal);

    await http.post('DayTaskAPI.php', formData, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    ElMessage.success('修改成功');
    dialogVisible.value = false;
    loadTaskData(obj.executor_id, obj.monday_date, false);
  } catch (error) {
    console.error('修改失败:', error);
    ElMessage.error(`修改失败: ${error.response?.data?.message || '服务器异常'}`);
  }
};

const deleteTask = async (row) => {

}

const showDialog = (type, row) => {
  if (row.date === jinRi.value) {
    ElMessage.warning('当日计划不能修改');
    return;
  }

  form.value = {
    id: row.id,
    time_spent: parseFloat(row.time_spent),
    progress: parseInt(row.progress.replace('%', '')),
    is_new_goal: row.is_new_goal || 0
  };
  dialogTitle.value = '修改任务';
  dialogVisible.value = true;




}

// 加载任务数据
const loadTaskData = async (executor_id, monday_date, bFirst) => {
  console.log("loadTaskData called with executor_id:", executor_id);
  if (executor_id == null || monday_date == null) {
    return
  }

  if (bFirst) {
    setTimeout(() => {
      obj.executor_id = executor_id
      userinfo.id = executor_id
      props.executorId = executor_id
    }, 2000);
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

    for (let i = 0; i < tempTask.data.length; i++) {
      let item = tempTask.data[i]
      if (item.date == today && item.dailyTasks.length <= 0) {
        let newTasks = await getDailyPlanWithExecutorId(true, executor_id)
        for (let j = 0; j < newTasks.length; j++) {
          let o = newTasks[j]
          let t = {}
          t.executor = ''
          t.executor_id = executor_id
          t.date = o.createdAt.replace(/^(\d{4})-(\d{2})-(\d{2}).*/, '$1$2$3')
          t.progress = o.complete != null && o.complete != 'null' ? o.complete + '%' : '0%'
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
    drawTable()
    // ElMessage.success('获取任务成功');
  } catch (error) {
    console.error('获取任务失败:', error.response?.data || error.message);
    ElMessage.error(`获取任务失败: ${error.response?.data?.message || '服务器异常'}`);
  }
}
defineExpose({ loadTaskData }); // 关键！暴露方法给父组件


const drawTable = () => {
  taskData.value = [];
  const dailyGoal = Array.isArray(tasks.value?.data?.[obj.tabIndex]?.dailyGoals) ? tasks.value.data[obj.tabIndex].dailyGoals : [];
  const dailyTask = Array.isArray(tasks.value?.data?.[obj.tabIndex]?.dailyTasks) ? tasks.value.data[obj.tabIndex].dailyTasks : [];
  dailyGoals.value = dailyGoal;
  dailyTasks.value = [...dailyTask];
  tableKey.value += 1;
  console.log(dailyGoal)
  console.log(dailyTask)
}
const getWeekDates = () => {
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
  index.value = week - 1;
  obj.tabIndex = index.value;
  currentDate.value = getDateByWeekday(week)
  drawTable()

  //   loadTaskData(tabName)
}

// 初始化加载数据
// loadTaskData(activeTab.value)

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
    'Monday': '1',
    'Tuesday': '2',
    'Wednesday': '3',
    'Thursday': '4',
    'Friday': '5',
    'Saturday': '6',
    'Sunday': '7'
  };
  activeTab.value = weekdays[localWeekday];

  obj.tabIndex = parseInt(activeTab.value)

  for (let index = 0; index < weekDays.length; index++) {
    weekDays[index].date = getDateByWeekday(index + 1);
  }

  const cache = localStorage.getItem('departments_user_cache');
  let users = cache ? JSON.parse(cache) : [];
  userList.value = users;

  // 添加默认选择逻辑
  // const targetUser = userList.value.find(user => user.id === 15);
  // if (targetUser) {
  //   obj.executor_id = 15;
  //   handleUserChange(15, obj.monday_date);
  // }
}

initActiveTab()
</script>

<style scoped>
.user-tabs-container {
  width: 80px;
  float: left;
  margin-right: 20px;
  height: 500px;
  overflow-y: auto;
}

.content-area {
  overflow: hidden;
  padding-left: 20px;
}

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

.user-tabs>>>.el-tabs__item {
  height: 40px;
  line-height: 40px;
  font-size: 14px;
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

.content_area{
  width: 100%;
  max-height: 80vh;
  /* overflow-y: auto; */
}
</style>
