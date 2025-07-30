<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <!-- 查询条件区域 -->
    <div class="query-section">
      <el-date-picker
        v-model="startDate"
        type="date"
        placeholder="开始日期"
        format="YYYYMMDD"
        value-format="YYYYMMDD"
        style="margin-left: 8px; max-width: 200px;"
      />

      <el-date-picker
        v-model="endDate"
        type="date"
        placeholder="结束日期"
        format="YYYYMMDD"
        value-format="YYYYMMDD"
        style="margin-left: 8px; max-width: 200px;"
      />

      <el-select v-model="selectedDepartmentId" placeholder="请选择部门" @change="handleDepartmentChange"
        style="max-width: 120px;margin-left:8px">
        <el-option v-for="dept in departments" :key="dept.id" :label="dept.department_name" :value="dept.id" />
      </el-select>

      <el-button type="primary" style="margin-left: 8px;" @click="loadData()">查询</el-button>
      <el-button type="info" style="margin-left: 8px;" @click="setDateRange(3)">近三天</el-button>
      <el-button type="info" style="margin-left: 8px;" @click="setDateRange(7)">近一周</el-button>
      <el-button type="info" style="margin-left: 8px;" @click="setDateRange(30)">近一个月</el-button>
    </div>

    <!-- 统计数据展示区域 -->
    <div class="statistics-section" style="margin-top: 20px;">
      <el-card>
        <div slot="header" class="card-header">
          <h2>周目标任务统计</h2>
          <!-- S、A、B、C类未完成数量统计 -->
          <div class="priority-stats">
            <el-descriptions :column="4" border>
              <el-descriptions-item label="S类">
                <span class="clickable-number" @click="showTaskDialog('S', 'incomplete')" style="color: #f56c6c; cursor: pointer; font-weight: bold;">
                  {{ priorityStats.S.complete }} / {{ priorityStats.S.total }}
                </span>
              </el-descriptions-item>
              <el-descriptions-item label="A类">
                <span class="clickable-number" @click="showTaskDialog('A', 'incomplete')" style="color: #e6a23c; cursor: pointer; font-weight: bold;">
                  {{ priorityStats.A.complete }} / {{ priorityStats.A.total }}
                </span>
              </el-descriptions-item>
              <el-descriptions-item label="B类">
                <span class="clickable-number" @click="showTaskDialog('B', 'incomplete')" style="color: #409eff; cursor: pointer; font-weight: bold;">
                  {{ priorityStats.B.complete }} / {{ priorityStats.B.total }}
                </span>
              </el-descriptions-item>
              <el-descriptions-item label="C类">
                <span class="clickable-number" @click="showTaskDialog('C', 'incomplete')" style="color: #67c23a; cursor: pointer; font-weight: bold;">
                  {{ priorityStats.C.complete }} / {{ priorityStats.C.total }}
                </span>
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </div>

        <!-- 图表展示区域 -->
        <div class="charts-container" style="margin: 20px 0;">
          <el-row :gutter="20">
            <el-col :span="12">
              <el-card>
                <div slot="header">
                  <h3>优先级任务状态分布</h3>
                </div>
                <div ref="priorityPieChartRef" style="width: 100%; height: 300px;"></div>
              </el-card>
            </el-col>
            <el-col :span="12">
              <el-card>
                <div slot="header">
                  <h3>任务状态分布</h3>
                </div>
                <div ref="statusPieChartRef" style="width: 100%; height: 300px;"></div>
              </el-card>
            </el-col>
          </el-row>
          
          <!-- 折线图区域 -->
          <div v-if="showLineChart" style="margin-top: 20px;">
            <el-card>
              <div slot="header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>任务完成趋势</h3>
                <el-radio-group v-model="chartType" @change="updateLineChart">
                   <el-radio-button value="week">按周</el-radio-button>
                   <el-radio-button value="month">按月</el-radio-button>
                 </el-radio-group>
              </div>
              <div ref="lineChartRef" style="width: 100%; height: 400px;"></div>
            </el-card>
          </div>
        </div>
      </el-card>
    </div>

    <!-- 任务详情弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="80%"
      max-height="70vh"
    >
      <el-table
        :data="dialogTasks"
        border
        style="width: 100%"
        max-height="400"
      >
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="weekly_goal" label="任务内容" min-width="200" />
        <el-table-column label="优先级" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getPriorityTagType(row.priority)" size="small">
              {{ getPriorityText(row.priority) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="executor" label="执行人" width="120" align="center" />
        <el-table-column prop="createdate" label="创建日期" width="120" align="center" />
      </el-table>
    </el-dialog>
  </div>
</template>



<script setup>
import { ref, onMounted, onActivated, computed, nextTick } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import * as echarts from 'echarts'

// 生成三个周一选项
const mondayOptions = ref([])
const startDate = ref('');
const endDate = ref('');
const selectedDepartmentId = ref(null)
const departments = ref([])
const users = ref([])
const goals = ref([])

// 图表相关
const priorityPieChartRef = ref()
const statusPieChartRef = ref()
const lineChartRef = ref()
let priorityPieChart = null
let statusPieChart = null
let lineChart = null

// 折线图相关
const chartType = ref('week') // 'week' 或 'month'
const showLineChart = ref(false) // 是否显示折线图

// 弹窗相关
const dialogVisible = ref(false)
const dialogTitle = ref('')
const dialogTasks = ref([])

// 统计数据
const taskStats = ref([])


const fetchDepartments = async () => {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_departments')
    // let obj = [{ 'department_name': '全部', 'id': 0, 'group_id': 0 }]
    departments.value = res.data//[...obj, ...res.data]
    localStorage.setItem('departments_cache', JSON.stringify(departments.value))
  } catch (error) {
    console.error('获取部门列表失败:', error)
    const cache = localStorage.getItem('departments_cache')
    if (cache) {
      departments.value = JSON.parse(cache)
    } else {
      departments.value = [{ id: 2, department_name: '默认部门' }]
    }
  }
}

const handleDepartmentChange = (val) => {
  localStorage.setItem('department_id_cache', val)

  if (val == 0) {
    http.get(`UserInfoAPI.php?action=get_all_users`)
      .then(res => {
        users.value = res.data
        localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
        // megerOAUserIDS(val)
      })
  } else {
    http.get(`UserInfoAPI.php?action=get_users&department_id=${val}`)
      .then(res => {
        users.value = res.data
        localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
        // megerOAUserIDS(val)
      })
  }

  loadData()
}


// 计算日期区间内的所有周一日期
const getMondaysInRange = (startDateStr, endDateStr) => {
  const mondays = []
  
  // 解析日期字符串 (格式: YYYYMMDD)
  const parseDate = (dateStr) => {
    const year = parseInt(dateStr.substring(0, 4))
    const month = parseInt(dateStr.substring(4, 6)) - 1 // 月份从0开始
    const day = parseInt(dateStr.substring(6, 8))
    return new Date(year, month, day)
  }
  
  // 格式化日期为字符串 (格式: YYYYMMDD)
  const formatDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}${month}${day}`
  }
  
  const startDate = parseDate(startDateStr)
  const endDate = parseDate(endDateStr)
  
  // 找到开始日期所在周的周一
  let currentDate = new Date(startDate)
  const dayOfWeek = currentDate.getDay()
  const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek // 周日为0，需要特殊处理
  currentDate.setDate(currentDate.getDate() + daysToMonday)
  
  // 收集所有周一日期
  while (currentDate <= endDate) {
    mondays.push(formatDate(currentDate))
    currentDate.setDate(currentDate.getDate() + 7) // 下一个周一
  }
  return mondays
}

const loadData = async () => {
  try {
    // 开始日期 startDate、结束日期 endDate  计算这个日期区间的周一日期
    const mondayDates = getMondaysInRange(startDate.value, endDate.value)
    console.log('日期区间内的周一日期:', mondayDates)
    
    // 判断是否跨周（超过一周）
    const isMultiWeek = mondayDates.length > 1
    showLineChart.value = isMultiWeek
    
    const departmentId = selectedDepartmentId.value
    const res = await http.get('WeekGoalAPI.php', {
      params: {
        action: 'list',
        startDate: startDate.value,
        endDate: endDate.value,
        mondayDates: mondayDates.join(','), // 将周一日期数组转为逗号分隔的字符串
        department_id: departmentId,
      }
    })
    goals.value = res
    taskStats.value = Array.isArray(res) ? res : []
    
    // 更新图表
    await nextTick()
    updateCharts()
    
    // 如果跨周，初始化并更新折线图
    if (isMultiWeek) {
      initLineChart()
      updateLineChart()
    }
  } catch (error) {
    console.error('获取数据失败:', error)
    ElMessage.error('获取数据失败')
  }
}

// 设置日期范围并查询数据
const setDateRange = async (days) => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  endDate.value = `${year}${month}${day}`;
  
  // 计算开始日期
  const startDateObj = new Date();
  startDateObj.setDate(today.getDate() - days + 1); // +1是因为包含今天
  const startYear = startDateObj.getFullYear();
  const startMonth = String(startDateObj.getMonth() + 1).padStart(2, '0');
  const startDay = String(startDateObj.getDate()).padStart(2, '0');
  startDate.value = `${startYear}${startMonth}${startDay}`;
  
  // 自动调用查询
  await loadData();
};

onMounted(async () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  endDate.value = `${year}${month}${day}`;
  
  // 设置startDate为七天前日期
  const sevenDaysAgo = new Date();
  sevenDaysAgo.setDate(today.getDate() - 7);
  const startYear = sevenDaysAgo.getFullYear();
  const startMonth = String(sevenDaysAgo.getMonth() + 1).padStart(2, '0');
  const startDay = String(sevenDaysAgo.getDate()).padStart(2, '0');
  startDate.value = `${startYear}${startMonth}${startDay}`;

  await fetchDepartments()
  const cachedId = localStorage.getItem('department_id_cache') || 2
  const dept = departments.value.find(d => d.id == cachedId)
  selectedDepartmentId.value = dept ? dept.id : departments.value[0]?.id || 2

  await loadData()
  
  // 初始化图表
  await nextTick()
  initCharts()
  updateCharts()
})

// 组件激活时重新调整图表大小
onActivated(async () => {
  await nextTick()
  resizeCharts()
  // 如果折线图存在，也需要resize
  if (lineChart && showLineChart.value) {
    lineChart.resize()
  }
})


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

// 计算优先级统计
const priorityStats = computed(() => {
  const stats = {
    S: { total: 0, incomplete: 0, complete: 0 },
    A: { total: 0, incomplete: 0, complete: 0 },
    B: { total: 0, incomplete: 0, complete: 0 },
    C: { total: 0, incomplete: 0, complete: 0 }
  }
  
  taskStats.value.forEach(task => {
    const priority = getPriorityText(task.priority)
    if (stats[priority]) {
      stats[priority].total++


      const status = parseInt(task.status)

      if (status === 3 || status === 5) { // 已上线
        stats[priority].complete++
      }  else {
        stats[priority].incomplete++
      }
    }
  })
  console.log(stats)
  return stats
})

// 计算状态统计
const statusStats = computed(() => {
  const stats = {
    inProgress: 0,    // 进行中
    online: 0,        // 已上线
    paused: 0,        // 已暂停
    completed: 0      // 已完成
  }
  
  taskStats.value.forEach(task => {
    switch (parseInt(task.status)) {
      case 1:
        stats.inProgress++
        break
      case 2:
        stats.inProgress++ // 测试中也算进行中
        break
      case 3:
        stats.online++
        break
      case 5:
        stats.completed++   
        break
      case 4:
        stats.paused++
        break
      default:
        break
    }
  })
  //                console.log('id = '+task.id+'  status = '+task.status+'  priority = '+priority)

  console.log(stats)
  return stats
})

// 初始化图表
const initCharts = () => {
  if (priorityPieChartRef.value && !priorityPieChart) {
    priorityPieChart = echarts.init(priorityPieChartRef.value)
    // 添加点击事件
    priorityPieChart.on('click', (params) => {
      const priority = params.name.split('类')[0]
      const tasks = taskStats.value.filter(task => {
        const taskPriority = getPriorityText(task.priority)
        return taskPriority === priority
      })
      
      dialogTasks.value = tasks
      dialogTitle.value = `${priority}类任务列表`
      dialogVisible.value = true
    })
  }
  if (statusPieChartRef.value && !statusPieChart) {
    statusPieChart = echarts.init(statusPieChartRef.value)
    // 添加点击事件
    statusPieChart.on('click', (params) => {
      showTaskDialogByStatus(params.name)
    })
  }
}

// 更新图表
const updateCharts = () => {
  updatePriorityPieChart()
  updateStatusPieChart()
}

// 重新调整图表大小
const resizeCharts = () => {
  if (priorityPieChart) {
    priorityPieChart.resize()
  }
  if (statusPieChart) {
    statusPieChart.resize()
  }
  if (lineChart) {
    lineChart.resize()
  }
}

// 初始化折线图
const initLineChart = () => {
  if (lineChartRef.value && !lineChart) {
    lineChart = echarts.init(lineChartRef.value)
  }
}

// 更新折线图
const updateLineChart = () => {
  if (!lineChart || !taskStats.value.length) return
  
  const lineData = processLineChartData()
  
  const option = {
    title: {
      text: chartType.value === 'week' ? '周任务完成趋势' : '月任务完成趋势',
      left: 'center'
    },
    tooltip: {
      trigger: 'axis',
      formatter: function(params) {
        let result = params[0].name + '<br/>'
        params.forEach(param => {
          result += param.marker + param.seriesName + ': ' + param.value + '个<br/>'
        })
        return result
      }
    },
    legend: {
      data: ['已完成', '未完成'],
      bottom: '5%'
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '15%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: lineData.categories
    },
    yAxis: {
      type: 'value',
      minInterval: 1
    },
    series: [
      {
        name: '已完成',
        type: 'line',
        data: lineData.completed,
        itemStyle: { color: '#67C23A' },
        lineStyle: { color: '#67C23A' },
        symbol: 'circle',
        symbolSize: 6,
        label: {
          show: true,
          position: 'top'
        }
      },
      {
        name: '未完成',
        type: 'line',
        data: lineData.uncompleted,
        itemStyle: { color: '#F56C6C' },
        lineStyle: { color: '#F56C6C' },
        symbol: 'circle',
        symbolSize: 6,
        label: {
          show: true,
          position: 'top'
        }
      }
    ]
  }
  
  lineChart.setOption(option)
}

// 处理折线图数据
const processLineChartData = () => {
  const categories = []
  const completed = []
  const uncompleted = []
  
  if (chartType.value === 'week') {
    // 按周统计
    const weekData = {}
    
    taskStats.value.forEach(task => {
      if (!task.mondayDate) return // 跳过没有week_start_date的任务
      const weekKey = task.mondayDate
      if (!weekData[weekKey]) {
        weekData[weekKey] = { completed: 0, uncompleted: 0 }
      }
      
      if (parseInt(task.status) === 5 || parseInt(task.status) === 3) {
        weekData[weekKey].completed++
      } else {
        weekData[weekKey].uncompleted++
      }
    })
    
    // 按日期排序
    const sortedWeeks = Object.keys(weekData).sort()
    sortedWeeks.forEach((week, index) => {
      const weekLabel = formatWeekLabel(week)
      categories.push(weekLabel)
      completed.push(weekData[week].completed)
      uncompleted.push(weekData[week].uncompleted)
    })
  } else {
    // 按月统计
    const monthData = {}
    
    // 找到最小和最大日期
    let minDate = null
    let maxDate = null
    
    taskStats.value.forEach(task => {
      if (!task.createdate) return
      const createDate = task.createdate
      if (!minDate || createDate < minDate) {
        minDate = createDate
      }
      if (!maxDate || createDate > maxDate) {
        maxDate = createDate
      }
    })
    
    // 如果有数据，生成月份范围
    if (minDate && maxDate) {
      const minYear = parseInt(minDate.substring(0, 4))
      const minMonth = parseInt(minDate.substring(4, 6))
      const maxYear = parseInt(maxDate.substring(0, 4))
      const maxMonth = parseInt(maxDate.substring(4, 6))
      
      // 生成所有月份标签
      for (let year = minYear; year <= maxYear; year++) {
        const startMonth = year === minYear ? minMonth : 1
        const endMonth = year === maxYear ? maxMonth : 12
        
        for (let month = startMonth; month <= endMonth; month++) {
          const monthKey = `${year}${month.toString().padStart(2, '0')}`
          monthData[monthKey] = { completed: 0, uncompleted: 0 }
        }
      }
    }
    
    // 统计任务数据
    taskStats.value.forEach(task => {
      if (!task.createdate) return
      const monthKey = task.createdate.substring(0, 6) // YYYYMM
      if (monthData[monthKey]) {
        if (parseInt(task.status) === 5 || parseInt(task.status) === 3) {
          monthData[monthKey].completed++
        } else {
          monthData[monthKey].uncompleted++
        }
      }
    })
    
    // 按日期排序并生成图表数据
    const sortedMonths = Object.keys(monthData).sort()
    sortedMonths.forEach(month => {
      categories.push(month)
      completed.push(monthData[month].completed)
      uncompleted.push(monthData[month].uncompleted)
    })
  }
  
  return { categories, completed, uncompleted }
}

// 格式化周标签
const formatWeekLabel = (weekStartDate) => {
  // 处理 "20250708" 格式的日期字符串
  let dateStr = weekStartDate
  if (weekStartDate.length === 8 && /^\d{8}$/.test(weekStartDate)) {
    // 将 "20250708" 转换为 "2025-07-08" 格式
    dateStr = `${weekStartDate.substring(0, 4)}-${weekStartDate.substring(4, 6)}-${weekStartDate.substring(6, 8)}`
  }
  
  const date = new Date(dateStr)
  const month = date.getMonth() + 1
  const day = date.getDate()
  
  // 计算周结束日期
  const endDate = new Date(date)
  endDate.setDate(date.getDate() + 6)
  const endMonth = endDate.getMonth() + 1
  const endDay = endDate.getDate()
  
  if (month === endMonth) {
    return `${month}月${day}-${endDay}日`
  } else {
    return `${month}月${day}日-${endMonth}月${endDay}日`
  }
}

// 格式化月标签
const formatMonthLabel = (mondayDate) => {
  // 处理 "20250708" 格式的日期字符串
  let dateStr = mondayDate
  if (mondayDate.length === 8 && /^\d{8}$/.test(mondayDate)) {
    // 将 "20250708" 转换为 "2025-07-08" 格式
    dateStr = `${mondayDate.substring(0, 4)}-${mondayDate.substring(4, 6)}-${mondayDate.substring(6, 8)}`
  }
  
  const startDate = new Date(dateStr)
  const year = startDate.getFullYear()
  const month = startDate.getMonth() + 1
  
  // 计算该月的开始和结束日期
  const monthStart = new Date(year, startDate.getMonth(), 1)
  const monthEnd = new Date(year, startDate.getMonth() + 1, 0)
  
  const startDay = monthStart.getDate()
  const endDay = monthEnd.getDate()
  
  return `${year}年${month}月(${startDay}-${endDay}日)`
}

// 更新优先级饼图
const updatePriorityPieChart = () => {
  if (!priorityPieChart) return
  
  const data = []
  Object.keys(priorityStats.value).forEach(priority => {
    const stat = priorityStats.value[priority]
    if (stat.total > 0) {
      data.push({
        value: stat.total,
        name: `${priority}类`,
        itemStyle: {
          color: getPriorityColor(priority)
        }
      })
    }
  })
  
  const option = {
    title: {
      text: '',
      left: 'center'
    },
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      left: 'left'
    },
    series: [
      {
        name: '优先级分布',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['50%', '50%'],
        data: data,
        label: {
          show: true,
          formatter: '{b}: {c}\n({d}%)'
        },
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowOffsetX: 0,
            shadowColor: 'rgba(0, 0, 0, 0.5)'
          }
        }
      }
    ]
  }
  
  priorityPieChart.setOption(option)
}

// 更新状态饼图
const updateStatusPieChart = () => {
  if (!statusPieChart) return
  
  const data = [
    { value: statusStats.value.inProgress, name: '进行中', itemStyle: { color: '#e6a23c' } },
    { value: statusStats.value.online, name: '已上线', itemStyle: { color: '#67c23a' } },
    { value: statusStats.value.completed, name: '已完成', itemStyle: { color: '#67c23a' } },
    { value: statusStats.value.paused, name: '已暂停', itemStyle: { color: '#909399' } }
  ].filter(item => item.value > 0)
  
  const option = {
    title: {
      text: '',
      left: 'center'
    },
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      left: 'left'
    },
    series: [
      {
        name: '状态分布',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['50%', '50%'],
        data: data,
        label: {
          show: true,
          formatter: '{b}: {c}\n({d}%)'
        },
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowOffsetX: 0,
            shadowColor: 'rgba(0, 0, 0, 0.5)'
          }
        }
      }
    ]
  }
  
  statusPieChart.setOption(option)
}

// 显示任务详情弹窗（按优先级和完成状态）
const showTaskDialog = (priority, type) => {
  const priorityValue = getPriorityValue(priority)
  const isIncomplete = type === 'incomplete'
  
  dialogTasks.value = taskStats.value.filter(task => {
    const taskPriority = getPriorityText(task.priority)
    const isTaskIncomplete = task.status !== 3 // 不是已上线状态
    return taskPriority === priority && (isIncomplete ? isTaskIncomplete : !isTaskIncomplete)
  })
  
  dialogTitle.value = `${priority}类${isIncomplete ? '未完成' : '已完成'}任务列表`
  dialogVisible.value = true
}

// 显示任务详情弹窗（按状态）
const showTaskDialogByStatus = (statusName) => {
  let statusValue
  switch (statusName) {
    case '进行中':
      statusValue = [1, 2] // 进行中和测试中
      break
    case '已上线':
      statusValue = [3]
      break
    case '已完成':
      statusValue = [5] // 已完成状态
      break
    case '已暂停':
      statusValue = [4]
      break
    default:
      return
  }
  
  dialogTasks.value = taskStats.value.filter(task => statusValue.includes(parseInt(task.status)))
  dialogTitle.value = `${statusName}任务列表`
  dialogVisible.value = true
}

// 获取优先级文本
const getPriorityText = (priority) => {
  const priorityValue = parseInt(priority)
  if (priorityValue >= 10) return 'S'
  if (priorityValue === 9) return 'A'
  if (priorityValue === 8) return 'B'
  // 所有其他值(包括7、6、5、4、3、2、1、0及其他)都归为C类
  return 'C'
}

// 获取优先级数值
const getPriorityValue = (priorityText) => {
  const valueMap = { 'S': 10, 'A': 9, 'B': 8, 'C': 7 }
  return valueMap[priorityText] || 7
}

// 获取优先级颜色
const getPriorityColor = (priority, opacity = 1) => {
  const colors = {
    'S': `rgba(245, 108, 108, ${opacity})`,
    'A': `rgba(230, 162, 60, ${opacity})`,
    'B': `rgba(64, 158, 255, ${opacity})`,
    'C': `rgba(103, 194, 58, ${opacity})`
  }
  return colors[priority] || colors['C']
}

// 获取优先级标签类型
const getPriorityTagType = (priority) => {
  const priorityText = getPriorityText(priority)
  const typeMap = { 'S': 'danger', 'A': 'warning', 'B': 'primary', 'C': 'success' }
  return typeMap[priorityText] || 'success'
}

// 获取状态文本
const getStatusText = (status) => {
  const statusMap = { 1: '进行中', 2: '测试中', 3: '已上线', 4: '已暂停', 0: '未开始' }
  return statusMap[status] || '未知状态'
}

// 获取状态标签类型
const getStatusTagType = (status) => {
  const typeMap = { 1: 'warning', 2: 'primary', 3: 'success', 4: 'info', 0: 'info' }
  return typeMap[status] || 'info'
}
</script>


<style scoped>
.page-container {
  padding: 20px;
}

.query-section {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 20px;
}

.statistics-section {
  margin-top: 20px;
}

.card-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
}

.priority-stats {
  width: 100%;
}

.clickable-number {
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
}

.clickable-number:hover {
  transform: scale(1.1);
  text-shadow: 0 0 5px currentColor;
}

.charts-container {
  margin: 20px 0;
}

:deep(.el-descriptions__label) {
  font-weight: bold;
}

:deep(.el-card__header) {
  padding: 18px 20px;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-dialog__body) {
  padding: 20px;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-tag) {
  font-weight: bold;
}
</style>