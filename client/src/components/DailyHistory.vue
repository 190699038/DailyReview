<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <h2>周报历史查看</h2>
    
    <el-select
      v-model="selectedPeriod"
      @change="loadData"
      placeholder="选择周周期"
      style="margin-right: 20px;width: 200px;margin-bottom: 15px;"
    >
      <el-option
        v-for="period in weekPeriodOptions"
        :key="period.value"
        :label="period.label"
        :value="period.value"
      />
    </el-select>

    <el-select
      v-model="selectedExecutor"
      @change="loadData"
      placeholder="选择执行人"
      style="width: 200px"
    >
      <el-option label="全部" :value="0" />
      <el-option
        v-for="user in departmentUsers"
        :key="user.id"
        :label="user.partner_name"
        :value="user.id"
      />
    </el-select>

    <div class="user-cards-container">
  <el-card 
    v-for="(userData, index) in tableData" 
    :key="index"
    class="user-card"
    style="margin-bottom: 15px;"
    shadow="hover"
  >
    <template #header>
      <div class="card-header" @click="userData.isExpanded = !userData.isExpanded">
        <span>{{ userData.partner_name }} - {{ userData.period }}</span>
        <div class="header-right">
          <el-icon :class="['caret-icon', { 'rotate-90': userData.isExpanded }]">
            <component :is="userData.isExpanded ? CaretTop : CaretBottom" />
          </el-icon>
        </div>
      </div>
    </template>

    <el-collapse-transition>
      <div class="card-content" v-show="userData.isExpanded">
      <div class="goals-section">
        <h4>周目标</h4>
        <el-table :data="userData.dailyGoal" border>
          <el-table-column prop="weekly_goal" label="目标内容" />
          <!-- <el-table-column label="状态" width="120">
            <template #default="{ row }">
              {{ {1:'进行中',2:'测试中',3:'已上线',4:'已暂停',0:'未开始'}[row.status] }}
            </template>
          </el-table-column> -->
        </el-table>
      </div>

      <div class="tasks-section">
        <h4>每日任务</h4>
        <el-timeline>
          <el-timeline-item
            v-for="[date, tasks] in Object.entries(userData.dailyTasks).sort((a, b) => a[0].localeCompare(b[0]))"
            :key="date"
            :timestamp="formatDate(date)"
            placement="top"
          >
          <el-table :data="tasks" border :row-class-name="taskClassName" >
            <el-table-column prop="megerData" label="目标方案" />
            <el-table-column prop="progress" label="进度"  width="100" align="center" header-align="center"/>
            <el-table-column prop="time_spent" label="耗时(小时)" width="100" align="center" header-align="center" />
            </el-table>

          </el-timeline-item>
        </el-timeline>
      </div>
    </div>
    </el-collapse-transition>
  </el-card>
</div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { CaretTop, CaretBottom } from '@element-plus/icons-vue'

const selectedPeriod = ref('')
const selectedExecutor = ref(0)
const tableData = ref([])

// 周周期选项处理
const weekPeriods = ref([])

const weekPeriodOptions = ref([])

onMounted(async () => {
  const res = await http.get('WeekGoalAPI.php?action=getWeekPeriod')
  weekPeriods.value = res.data || []
  let periods = res.map(p => ({
    value: String(p),
    label: `${String(p).slice(0,4)}年${String(p).slice(4,6)}月第${Math.ceil(String(p).slice(6)/7)}周`
  }))
  weekPeriodOptions.value = periods

  if (weekPeriodOptions.value.length > 0) {
    selectedPeriod.value = weekPeriodOptions.value[0].value
  }


  loadData()
})


const taskClassName = ({ row }) => {
  let style = ''
  if (row.progress === '100%') {
    style = 'green-row'
  }
  return style
}

// 部门人员数据处理
const departmentUsers = computed(() => {
  const cached = localStorage.getItem('departments_user_cache')
  return JSON.parse(cached || '[]')
})

// 单元格合并方法
const cellMerge = ({ row, column, rowIndex, columnIndex }) => {
  if (columnIndex === 0) {
    return {
      rowspan: row.span || 1,
      colspan: 1
    }
  }
}

// 加载数据
const loadData = async () => {
  try {
    // 参数校验
    if (!/^\d{6}\d{1,2}$/.test(selectedPeriod.value)) {
      ElMessage.error('请选择有效的周周期')
      return
    }
    
    if (!Number.isInteger(selectedExecutor.value) || selectedExecutor.value < 0) {
      ElMessage.error('执行人ID必须为非负整数')
      return
    }

    const departmentId = localStorage.getItem('department_id_cache')
    if (!departmentId || isNaN(departmentId)) {
      ElMessage.error('部门信息不存在，请重新选择部门')
      return
    }

    // 解析周周期为起始日期并计算结束日期
    const year = parseInt(String(selectedPeriod.value).slice(0,4));
    const month = parseInt(String(selectedPeriod.value).slice(4,6)) - 1;
    const day = parseInt(String(selectedPeriod.value).slice(6,8) || '1');
    const startDate = new Date(year, month, day);
    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + 7);
    const endDateStr = endDate.getFullYear().toString() +
                      String(endDate.getMonth() + 1).padStart(2, '0') +
                      String(endDate.getDate()).padStart(2, '0');

    const params = {
      action: 'get_history',
      week_period: selectedPeriod.value,
      executor_id: selectedExecutor.value === 0 
        ? departmentUsers.value.map(u => u.id).join(',') 
        : String(selectedExecutor.value),
      department_id: departmentId,
      end_date: endDateStr
    }
    
    const res = await http.get('DayTaskAPI.php', { params })

    let data = res.data || {}

    // 通过用户ID遍历json数组 

    dataAnlysis(data,selectedPeriod.value)


    // tableData.value = processTableData(res)
  } catch (error) {
    ElMessage.error('数据加载失败')
  }
}


const formatDate = (dateStr) => {
  const year = dateStr.slice(0,4);
  const month = dateStr.slice(4,6);
  const day = dateStr.slice(6,8);
  return `${year}年${month}月${day}日`;
};

const dataAnlysis = (data,startDate) => {
  // 获取当前部门用户映射表
  const usercache = localStorage.getItem('departments_user_cache') ;
  const userList = JSON.parse(usercache) ||[];

  const result = [];

  for (let index = 0; index < userList.length; index++) {

    const rowinfo = {}
    const user = userList[index];
    rowinfo.partner_name = user.partner_name;
    //获取用户的数据
    let obj = data[user.id] 
    if ( obj == null ) {
      continue;
    }
    let dailyGoals = obj.dailyGoal || [];
    let dailyTasks = obj.dailyTasks || [];

    //周目标
    let p = startDate;
    rowinfo.period = `${String(p).slice(0,4)}年${String(p).slice(4,6)}月第${Math.ceil(String(p).slice(6)/7)}周`;
    rowinfo.dailyGoal = dailyGoals
    // 遍历每日任务，按日期分组
    const groups = initGroup(startDate);
    dailyTasks.forEach(task => {
      const date = task.date;
      let day_goal = task.day_goal;
      let task_content = task.task_content; 
      if(day_goal.indexOf('task_content') >= 0 || task_content.indexOf('day_goal') >= 0){
        task.megerData = task_content
      }else{
        task.megerData = `${day_goal} - (${task_content})`
      }


      if (groups[date]) {
        groups[date].push(task);
      }else{
        groups[date] = [task];
      }
    })

    // 转换日期格式为YYYYMMDD并过滤空任务
rowinfo.dailyTasks = Object.keys(groups)
  .filter(date => groups[date].length > 0)
  .reduce((acc, date) => {
    acc[date] = groups[date].map(task => ({
      ...task,
      status: {1:'进行中',2:'完成',3:'延迟'}[task.status] || '未知状态'
    }));
    return acc;
  }, {});
    result.push(rowinfo);
  }

  // 设置第一个用户默认展开
  if (result.length > 0) {
    result[0].isExpanded = true;
  }

  tableData.value = result;
  console.log((result))

}


const initGroup =  (date) => {
  // 解析日期格式
  if (!/^\d{8}$/.test(date)) {
    console.error('日期格式错误');
    return [];
  }
  
  const year = parseInt(date.slice(0,4));
  const month = parseInt(date.slice(4,6)) - 1; // JS月份0-11
  const day = parseInt(date.slice(6,8));
  
  const group = {};
  const currentDate = new Date(year, month, day);
  
  // 生成连续7天日期
  for (let i = 0; i < 7; i++) {
    const d = new Date(currentDate);
    d.setDate(currentDate.getDate() + i);
    
    // 格式化为YYYYMMDD
    const yyyy = d.getFullYear().toString();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    
    group[`${yyyy}${mm}${dd}`] = [];
  }
  
  return group;
}





// 数据处理方法
const processTableData = (rawData) => {
  const groupedData = [];
  let currentGroup = null;

  rawData.forEach((item, index) => {
    const groupKey = `${item.period}-${item.goal}-${item.plan}`;
    
    if (!currentGroup || currentGroup.key !== groupKey) {
      currentGroup = {
        key: groupKey,
        startIndex: index,
        span: 1,
        dates: [item.date]
      };
      groupedData.push({...item, span: currentGroup.span});
    } else {
      currentGroup.span++;
      currentGroup.dates.push(item.date);
      groupedData[currentGroup.startIndex].span = currentGroup.span;
      groupedData.push({...item, span: 0});
    }
  });

  // 展开日期数据
  return groupedData.flatMap(item => {
    if (item.span > 0) {
      return [{
        ...item,
        date: item.dates?.join('、') || ''
      }];
    }
    return {
      ...item,
      date: item.date
    };
  });
}

onMounted(async () => {
  // 初始化加载周周期数据
//   if(!localStorage.getItem('weekPeriods_cache')) {
//     const res = await http.get('WeekGoalAPI.php?action=getWeekPeriod')
//     localStorage.setItem('weekPeriods_cache', JSON.stringify(res.data))
//   }
//   loadData()
})
</script>

<style scoped>
.card-header {
  background: linear-gradient(135deg, #f0f7ff 0%, #e1effe 100%);
  border-radius: 8px 8px 0 0;
  padding: 15px 20px;
  color: #2c3e50;
  font-weight: 600;
}

.user-card {
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.user-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.goals-section {
  background: #f3faf3;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
  border: 1px solid #e8f5e9;
}

.tasks-section {
  background: #f8f9fc;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
  border: 1px solid #f0f1f5;
}

.el-table {
  border-radius: 6px;
  overflow: hidden;
}

.el-timeline-item__timestamp {
  color: #666;
  font-size: 0.9em;
}
.header-right {
  display: flex;
  justify-content: flex-end;
  flex-grow: 1;
}

.caret-icon {
  transition: all 0.3s ease;
  font-size: 1.2em;
  transform: scale(1.2);
  margin-left: 10px;
}

.rotate-180 {
  transform: rotate(180deg) scale(1.2);
}

.green-row {
  background-color: #A9D08D !important;
}
</style>
