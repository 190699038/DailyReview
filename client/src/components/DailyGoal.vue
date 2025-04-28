<template>
  <div>
    <div class="page-container">
      <div class="user-tabs-container">
        <el-tabs v-model="executorId" tab-position="left" class="user-tabs" @tab-change="handleUserChange">
          <el-tab-pane v-for="user in userList" :key="user.id" :label="user.partner_name" :name="user.id" />
        </el-tabs>
        <el-button type="primary" @click="drawerVisible = true"
          style="margin-top: 20px;width: 40px;height: 100px;writing-mode: vertical-rl;text-orientation: upright;line-height: 120px;font-size:12px;padding:2px 8px;letter-spacing:1px;">今日目标</el-button>

      </div>

      <div class="content-area">
        <el-select
        v-model="mondayDate"
        @change="changeMonday"
        placeholder="选择周范围"
        style="max-width: 200px;margin-left: 10px;margin-right: 10px;"
      >
      <el-option
        width="200"
        v-for="option in mondayOptions"
        :key="option.value"
        :label="option.label"
        :value="option.value"
        
      />
      </el-select>
        <el-tabs v-model="activeTab" type="card" @tab-change="handleTabChange">
          <el-tab-pane v-for="(day, index) in weekDays" :key="index" :label="day.label" :name="day.name">           
              <!-- <h2>{{ day.date }} {{ day.label }} </h2> -->
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

              <div class="content_area">
              <div class="content-container">
                <div style="margin-bottom: 5px;margin-left: 1px;">日计划</div>

                <el-table :key="tableKey"
                  :data="dailyTodayTasks.filter(task => parseFloat(task.time_spent) > 0 || 1) || []" border
                  style="width: 100%" :row-class-name="taskClassName">
                  <el-table-column prop="id" label="序号" width="90" align="center" header-align="center" />
                  <el-table-column prop="date" label="日期" width="100" align="center" header-align="center" />

                  <el-table-column prop="day_goal" label="目标" header-align="center" />
                  <el-table-column prop="task_content" label="拆解任务" header-align="center" />
                  <el-table-column prop="time_spent" label="预估耗时(小时)" width="120" align="center" header-align="center" />
                  <el-table-column prop="progress" label="进度" width="90" align="center" header-align="center" />

                </el-table>
              </div>


              <div class="content-container">
                <div style="margin-bottom: 5px;margin-left: 1px;">日计划总结</div>

                <el-table :key="tableKey" :data="dailyTasks.filter(task => parseFloat(task.time_spent) > 0) || []"
                  border style="width: 100%" :row-class-name="taskClassName">
                  <el-table-column prop="id" label="序号" width="90" align="center" header-align="center" />
                  <el-table-column prop="date" label="日期" width="100" align="center" header-align="center" />

                  <el-table-column prop="day_goal" label="目标" header-align="center" />
                  <el-table-column prop="task_content" label="拆解任务" header-align="center" />
                  <el-table-column prop="time_spent" label="实际耗时(小时)" width="120" align="center" header-align="center" />
                  <el-table-column prop="progress" label="进度" width="90" align="center" header-align="center" />
                  <!-- <el-table-column label="操作" width="100" header-align="center" align="center">
                    <template #default="{ row }">
                      <div style="display: flex; justify-content: center; align-items: center; gap: 8px">
                        <el-button size="small" @click="showDialog('edit', row)">修改</el-button>
                       <el-button size="small" type="danger" @click="deleteTask(row)">删除</el-button>
                      </div>
                    </template>
                  </el-table-column> -->
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
    </div>

    <el-drawer v-model="drawerVisible" title="任务详情" :size="'55%'" :with-header="true" direction="rtl"
      class="custom-drawer" :style="{ height: '80%' }">
      <div class="goal-container">
        <h3>当日主要目标</h3>
        <el-form label-width="80px">
          <el-date-picker v-model="currentDay" type="date" value-format="YYYYMMDD" placeholder="选择日期"
            @change="getDailyGoal" style="margin-bottom: 8px;width: 130px;" />
          <el-input v-model="goalContent" type="textarea" :rows="35" placeholder="请输入当日主要目标" />
          <el-button type="primary" @click="saveGoal" style="margin-top: 8px;">保存目标</el-button>
          <el-button @click="fullscreenDialogVisible = true" style="margin-top: 8px;margin-left: 8px;">全屏查看</el-button>
        </el-form>
      </div>
    </el-drawer>
    <!-- 全屏弹窗 -->
    <el-dialog v-model="fullscreenDialogVisible" title="目标全屏查看" fullscreen>
      <pre class="fullscreen-content">{{ goalContent }}</pre>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import { parseExcelFile } from '@/utils/excelParser'
import { getDailyPlanWithExecutorId } from '@/utils/dailyPlanAsync'
import { getMondayDate } from '@/utils/dateUtils'

const fullscreenDialogVisible = ref(false)

const currentDate = ref({});
const tasks = ref({});
const obj = ref({})
const userinfo = ref({})
const dialogVisible = ref(false);
const dialogTitle = ref('');
const executorId = ref('')
const currentDay = ref(new Date().toISOString().slice(0, 10).replace(/-/g, ''))
const drawerVisible = ref(false);
const goalContent = ref('')

const mondayOptions = ref([])
const mondayDate = ref(getCurrentMonday())

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
const dailyTodayTasks = ref([]);

const tableKey = ref(0);
const today = new Date().toISOString().slice(0, 10).replace(/-/g, '')
const jinRi = ref(new Date().toISOString().slice(0, 10).replace(/-/g, ''))
const userList = ref([])



const handleUserChange = (newId) => {
  loadTaskData(newId, getMondayDate(currentDay.value), false)
}

const emit = defineEmits(['update:visible'])

const activeTab = ref('')
const taskData = ref([])
const index = ref(0)



const rowClassName = ({ row }) => {
  let style = ''
  if (row.status === '3') {
    style = 'green-row'
  } else {
    if (row.is_new_goal === '1') {
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

  try {
    obj.monday_date = monday_date
    obj.executor_id = executor_id
    let weeeks = generateWeekDates(monday_date)
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

    console.log(tempTask);
    // for( let i = 0; i < tempTask.data.length; i++) {
    //   let item = tempTask.data[i]
    //   if(item.date == today && item.dailyTasks.length <= 0){
    //     let newTasks  = item.dailyTasks_today//await getDailyPlanWithExecutorId(true,executor_id)
    //     for(let j = 0; j < newTasks.length; j++){
    //       let o = newTasks[j]
    //       let t = {}
    //       t.executor = ''
    //       t.executor_id = executor_id
    //       t.date = o.createdAt.replace(/^(\d{4})-(\d{2})-(\d{2}).*/, '$1$2$3')
    //       t.progress = o.complete != null && o.complete != 'null' ? o.complete+'%' : '0%'
    //       t.time_spent = o.e_time == null ? '-1' : o.e_time
    //       t.day_goal = o.d_describe
    //       t.task_content = o.p_describe
    //       t.id = o.id
    //       console.log(t)
    //       item.dailyTasks.push(t)
    //     }

    //   }
    // }

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
  const dailyTaskToday = Array.isArray(tasks.value?.data?.[obj.tabIndex]?.dailyTasks_today) ? tasks.value.data[obj.tabIndex].dailyTasks_today : [];

  dailyGoals.value = dailyGoal;
  dailyTasks.value = [...dailyTask];
  dailyTodayTasks.value = [...dailyTaskToday];
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
  { label: '星期一', name: '1' },
  { label: '星期二', name: '2' },
  { label: '星期三', name: '3' },
  { label: '星期四', name: '4' },
  { label: '星期五', name: '5' },
  { label: '星期六', name: '6' },
  { label: '星期日', name: '7' }
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

}
// 获取当日目标
const getDailyGoal = async () => {
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2

    const res = await http.get('/DayGoalAPI.php', {
      params: {
        action: 'get_target',
        report_date: currentDay.value,
        department_id: departmentId
      }
    })

    let data = res?.content || ''
    console.log(data)

    goalContent.value = data
    // fenxTodayTarget()
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
    formData.append('report_date', currentDay.value);
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


const changeMonday = () => {
  
  loadTaskData(executorId.value, mondayDate.value, true)
}

const initView = () =>{
  initActiveTab()
  mondayOptions.value = generateMondayOptions()
  executorId.value = userList.value[0].id
  loadTaskData(executorId.value, mondayDate.value, true)
  getDailyGoal()
}


onMounted(() => {
  initView()
})


// 生成完整周日期
const generateWeekDates = (mondayStr) => {
  const date = new Date(
    mondayStr.slice(0,4),
    mondayStr.slice(4,6) - 1,
    mondayStr.slice(6,8)
  );
  
  return Array.from({length:7}).map((_,i) => {
    const d = new Date(date);
    d.setDate(date.getDate() + i);
    return [
      d.getFullYear(),
      String(d.getMonth()+1).padStart(2,'0'),
      String(d.getDate()).padStart(2,'0')
    ].join('');
  });
};

// 获取当前周一日期
function getCurrentMonday() {
  const date = new Date();
  const day = date.getDay();
  const diff = date.getDate() - day + (day === 0 ? -6 : 1);
  date.setDate(diff);
  date.setHours(0, 0, 0, 0);
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}${mm}${dd}`;
}

function generateMondayOptions() {
  const currentMonday = new Date();
  currentMonday.setHours(0,0,0,0);
  const day = currentMonday.getDay();
  const diff = currentMonday.getDate() - day + (day === 0 ? -6 : 1);
  currentMonday.setDate(diff);
  
  return [-14, -7, 0, 7].map(offset => {
    const date = new Date(currentMonday);
    date.setDate(date.getDate() + offset);
    if (date.getDay() !== 1) {
      const diff = date.getDay() === 0 ? -6 : 1 - date.getDay();
      date.setDate(date.getDate() + diff);
    }
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const value = `${yyyy}${mm}${dd}`;
    
    let label = '';
    switch(offset) {
      case -14: label = '前两周周一'; break;
      case -7: label = '上一周周一'; break;
      case 0: label = '当前周周一'; break;
      case 7: label = '下一周周一'; break;
      default: label = '未知周期'; break;
    }
    return {
      value: value,
      label: `${label}(${value})`
    };
  });
}

</script>

<style scoped>
.goal-container {
  padding: 20px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.user-tabs-container {
  width: 80px;
  float: left;
  margin-right: 20px;
  height: 500px;
  /* overflow-y: auto; */
}

.content-area {
  /* overflow: hidden; */
  padding-left: 10px;
  overflow-y: none;


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

.content_area {
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
}
</style>

