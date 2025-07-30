<template>
 <div class="container">
    <div class="test-task-statics-base">
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


    <el-button type="primary" style="margin-left: 8px;" @click="loadData()">查询</el-button>
    <el-button type="info" style="margin-left: 8px;" @click="setDateRange(3)">近三天</el-button>
    <el-button type="info" style="margin-left: 8px;" @click="setDateRange(7)">近一周</el-button>
    <el-button type="info" style="margin-left: 8px;" @click="setDateRange(30)">近一个月</el-button>


    </div>
 <div class="test-task-info">
    <el-card>
      <div slot="header" class="card-header">
        <h2>测试任务信息统计</h2>
        <div class="total-stats">
          <el-descriptions :column="4" border>
            <el-descriptions-item label="总任务数">{{ totalTasks }}</el-descriptions-item>
            <el-descriptions-item label="总耗时(小时)">{{ totalTimeSpent.toFixed(2) }}</el-descriptions-item>
            <el-descriptions-item label="已完成任务">{{ totalCompletedTasks }}</el-descriptions-item>
            <el-descriptions-item label="进行中任务">{{ totalInProgressTasks }}</el-descriptions-item>
          </el-descriptions>
        </div>
      </div>

      <!-- 图表展示区域 -->
      <div class="charts-container" style="margin: 20px 0;">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-card>
              <div slot="header">
                <h3>总体任务分布饼图</h3>
              </div>
              <div ref="pieChartRef" style="width: 100%; height: 250px;"></div>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card>
              <div slot="header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>任务完成趋势</h3>
                <el-radio-group v-model="trendViewType" size="small" @change="updateLineChart">
                  <el-radio-button value="day">按日</el-radio-button>
                  <el-radio-button value="week">按周</el-radio-button>
                  <el-radio-button value="month">按月</el-radio-button>
                </el-radio-group>
              </div>
              <div ref="lineChartRef" style="width: 100%; height: 250px;"></div>
            </el-card>
          </el-col>
        </el-row>
        
        <!-- 按个人分析的图表 -->
        <el-row :gutter="20" style="margin-top: 20px;">
          <el-col :span="12">
            <el-card>
              <div slot="header">
                <h3>个人任务分布对比</h3>
              </div>
              <div ref="personBarChartRef" style="width: 100%; height: 250px;"></div>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card>
              <div slot="header">
                <h3>个人工作量分布</h3>
              </div>
              <div ref="personPieChartRef" style="width: 100%; height: 250px;"></div>
            </el-card>
          </el-col>
        </el-row>
      </div>

      <!-- 负责人列表 -->
      <el-collapse v-model="activeNames">
        <el-collapse-item 
          v-for="(personData, personName) in groupedData" 
          :key="personName" 
          :title="`负责人: ${personName}`"
          :name="personName"
        >
        <div class="person-stats">
          <el-descriptions :column="4" border>
            <el-descriptions-item label="总耗时(小时)">{{ getPersonInfo(personData,1) }}</el-descriptions-item>
            <el-descriptions-item label="已完成任务">{{ getPersonInfo(personData,2) }}</el-descriptions-item>
            <el-descriptions-item label="测试中任务">{{ getPersonInfo(personData,3) }}</el-descriptions-item>
            <el-descriptions-item label="未提测任务">{{ getPersonInfo(personData,4) }}</el-descriptions-item>
          </el-descriptions>
        </div>  
          <!-- 任务详情表格 -->
          <el-table 
            :data="personData.tasks" 
            border 
            style="width: 100%;"
            :default-sort="{ prop: 'test_status', order: 'ascending' }"
          >
            <el-table-column prop="task_id" label="任务ID" width="80" header-align="center" align="center"></el-table-column>
            <el-table-column prop="product" label="产品" width="120" header-align="center" align="center"></el-table-column>
            <el-table-column prop="test_content" label="测试内容"></el-table-column>
            <el-table-column prop="priority" label="优先级" width="80" header-align="center" align="center">
              <template #default="scope">
                <el-tag 
                  :type="priorityTypeMap[scope.row.priority]"
                  size="small"
                >
                  {{ scope.row.priority }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="test_status" label="状态" width="100" header-align="center" align="center">
              <template #default="scope">
                <el-tag 
                  :type="statusTypeMap[scope.row.test_status]"
                  size="small"
                >
                  {{ scope.row.test_status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="test_progress" label="进度" width="100" header-align="center" align="center">
              <template #default="scope">
                <el-progress 
                  :percentage="getProgressPercentage(scope.row.test_progress)" 
                  :stroke-width="6"
                  :status="getProgressStatus(scope.row.test_progress)"
                ></el-progress>
              </template>
            </el-table-column>
            <el-table-column prop="totalTime" label="耗时(小时)" header-align="center" align="center" width="100"></el-table-column>
          
          <el-table-column prop="submission_time" label="提测日期" width="110" header-align="center" align="center"></el-table-column>
          <el-table-column prop="planned_online_time" label="预计上线日期" width="110" header-align="center" align="center"></el-table-column>
          <el-table-column prop="actual_online_time" label="实际上线日期" width="110" header-align="center" align="center"></el-table-column>
          <el-table-column prop="creation_date" label="创建日期" width="100" header-align="center" align="center"></el-table-column>

          </el-table>
        </el-collapse-item>
      </el-collapse>
    </el-card>
  </div>

  <!-- 任务详情弹窗 -->
  <el-dialog
    v-model="dialogVisible"
    title="任务详情"
    width="80%"
    :before-close="handleClose"
  >
    <el-table
      :data="dialogTaskData"
      border
      style="width: 100%;"
      max-height="400"
    >
      <el-table-column prop="task_id" label="任务ID" width="80" header-align="center" align="center"></el-table-column>
      <el-table-column prop="product" label="产品" width="120" header-align="center" align="center"></el-table-column>
      <el-table-column prop="test_content" label="测试内容" min-width="200"></el-table-column>
      <el-table-column prop="test_status" label="状态" width="100" header-align="center" align="center">
        <template #default="scope">
          <el-tag 
            :type="statusTypeMap[scope.row.test_status]"
            size="small"
          >
            {{ scope.row.test_status }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="totalTime" label="耗时(小时)" header-align="center" align="center" width="100"></el-table-column>
            <el-table-column prop="creation_date" label="创建日期" header-align="center" align="center" width="100"></el-table-column>

    </el-table>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="dialogVisible = false">关闭</el-button>
      </span>
    </template>
  </el-dialog>
</div>
</template>

<script setup>
import { defineComponent, ref, onMounted, computed, nextTick} from 'vue';
import { ElMessage,ElCard, ElDescriptions, ElDescriptionsItem, ElCollapse, ElCollapseItem, ElRow, ElCol, ElStatistic, ElTable, ElTableColumn, ElTag, ElProgress, ElDialog, ElButton, ElRadioGroup, ElRadioButton } from 'element-plus';
import * as XLSX from 'xlsx';
import * as echarts from 'echarts';
import http from '@/utils/http'

const containerRef = ref();
const loading = ref(null);
const startDate = ref('');
const endDate = ref('');
const activeNames = ref(['王雪斌']); // 默认展开第一个负责人
const taskData = ref([]);
const groupedData = ref({});

// 弹窗相关
const dialogVisible = ref(false);
const dialogTaskData = ref([]);

// 趋势图视图类型
const trendViewType = ref('day');

// 图表引用
const pieChartRef = ref();
const lineChartRef = ref();
const personBarChartRef = ref();
const personPieChartRef = ref();
let pieChart = null;
let lineChart = null;
let personBarChart = null;
let personPieChart = null;

    // 优先级类型映射
    const priorityTypeMap = {
      'S': 'danger',
      'A': 'warning',
      'B': 'info',
      'C': 'success'
    };
    
    // 状态类型映射
    const statusTypeMap = {
      '已上线': 'success',
      '已完成': 'success',
      '测试中': 'warning',
      '未提测': 'info'
    };
const loadData = async () => {
  try{
    const res = await http.get('/TestTask.php', {
      params: {
        action: 'query',
        startDate: startDate.value,
        endDate: endDate.value
      }
    })

    if(res.error != null){
        ElMessage.error(`修改失败: ${res.error}`);
    }else{

      taskData.value = res.data;
      processData();
      // 如果图表已初始化，则更新图表数据
      if (pieChart && lineChart && personBarChart && personPieChart) {
        await nextTick();
        updateCharts();
      }
    }
  } catch (error) {
    console.error('查询失败:', error);
    ElMessage.error(`查询失败: ${error.response?.data?.message || '服务器异常'}`);
  }finally{
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

// 弹窗关闭处理
const handleClose = () => {
  dialogVisible.value = false;
  dialogTaskData.value = [];
};

// 显示任务详情弹窗
const showTaskDetails = (tasks, title) => {
  dialogTaskData.value = tasks;
  dialogVisible.value = true;
};

// 初始化图表
const initCharts = () => {
  if (pieChartRef.value && !pieChart) {
    pieChart = echarts.init(pieChartRef.value);
    // 添加点击事件
    pieChart.on('click', handlePieChartClick);
  }
  if (lineChartRef.value && !lineChart) {
    lineChart = echarts.init(lineChartRef.value);
    // 添加点击事件
    lineChart.on('click', handleLineChartClick);
  }
  if (personBarChartRef.value && !personBarChart) {
    personBarChart = echarts.init(personBarChartRef.value);
    // 添加点击事件
    personBarChart.on('click', handlePersonBarChartClick);
  }
  if (personPieChartRef.value && !personPieChart) {
    personPieChart = echarts.init(personPieChartRef.value);
    // 添加点击事件
    personPieChart.on('click', handlePersonPieChartClick);
  }
};

// 饼图点击事件处理
const handlePieChartClick = (params) => {
  const status = params.name;
  let filteredTasks = [];
  
  if (status === '已完成任务') {
    filteredTasks = taskData.value.filter(task => 
      task.test_status === '已上线' || task.test_status === '已完成'
    );
  } else if (status === '进行中任务') {
    filteredTasks = taskData.value.filter(task => task.test_status === '测试中');
  } else if (status === '未提测任务') {
    filteredTasks = taskData.value.filter(task => task.test_status === '未提测');
  }else if (status === '暂停任务') {
    filteredTasks = taskData.value.filter(task => task.test_status === '暂停');
  }
  
  showTaskDetails(filteredTasks, status);
};

// 折线图点击事件处理
const handleLineChartClick = (params) => {
  const date = params.name;
  const seriesName = params.seriesName;
  
  let filteredTasks = taskData.value.filter(task => task.creation_date === date);
  
  if (seriesName === '已完成') {
    filteredTasks = filteredTasks.filter(task => 
      task.test_status === '已上线' || task.test_status === '已完成'
    );
  } else if (seriesName === '进行中') {
    filteredTasks = filteredTasks.filter(task => task.test_status === '测试中');
  } else if (seriesName === '未提测') {
    filteredTasks = filteredTasks.filter(task => task.test_status === '未提测');
  }
  
  showTaskDetails(filteredTasks, `${date} - ${seriesName}`);
};

// 个人柱状图点击事件处理
const handlePersonBarChartClick = (params) => {
  const person = params.name;
  const seriesName = params.seriesName;
  
  let filteredTasks = taskData.value.filter(task => task.responsible_person === person);
  
  if (seriesName === '已完成') {
    filteredTasks = filteredTasks.filter(task => 
      task.test_status === '已上线' || task.test_status === '已完成'
    );
  } else if (seriesName === '进行中') {
    filteredTasks = filteredTasks.filter(task => task.test_status === '测试中');
  } else if (seriesName === '未提测') {
    filteredTasks = filteredTasks.filter(task => task.test_status === '未提测');
  }
  
  showTaskDetails(filteredTasks, `${person} - ${seriesName}`);
};

// 个人饼图点击事件处理
const handlePersonPieChartClick = (params) => {
  const person = params.name;
  const filteredTasks = taskData.value.filter(task => task.responsible_person === person);
  
  showTaskDetails(filteredTasks, `${person} - 所有任务`);
};

// 更新图表数据
const updateCharts = () => {
  updatePieChart();
  updateLineChart();
  updatePersonBarChart();
  updatePersonPieChart();
};

// 更新饼图
const updatePieChart = () => {
  if (!pieChart) return;
  
  const data = [
    { value: totalCompletedTasks.value, name: '已完成任务' },
    { value: totalInProgressTasks.value, name: '进行中任务' },
    { value: totalNotStartedTasks.value, name: '未提测任务' },
    { value: totalPausedTasks.value, name: '暂停任务' }
  ];
  
  const option = {
    title: {
      text: '任务状态分布',
      left: 'center'
    },
    tooltip: {
      trigger: 'item'
    },
    legend: {
      orient: 'vertical',
      left: 'left'
    },
    series: [
      {
        name: '任务状态',
        type: 'pie',
        radius: '50%',
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
  };
  
  pieChart.setOption(option);
};

// 更新折线图
const updateLineChart = () => {
  if (!lineChart) return;
  
  let dateStats = {};
  let dates = [];
  let completedData = [];
  let inProgressData = [];
  let notStartedData = [];
  let pausedData = [];
  
  if (trendViewType.value === 'day') {
    // 按日统计
    taskData.value.forEach(task => {
      const date = task.creation_date;
      if (!dateStats[date]) {
        dateStats[date] = { completed: 0, inProgress: 0, notStarted: 0 ,paused: 0};
      }
      
      if (task.test_status === '已上线' || task.test_status === '已完成') {
        dateStats[date].completed++;
      } else if (task.test_status === '测试中') {
        dateStats[date].inProgress++;
      } else if (task.test_status === '未提测') {
        dateStats[date].notStarted++;
      }else if (task.test_status === '暂停') {
        dateStats[date].paused++;
      }
    });
    
    dates = Object.keys(dateStats).sort();
    completedData = dates.map(date => dateStats[date].completed);
    inProgressData = dates.map(date => dateStats[date].inProgress);
    notStartedData = dates.map(date => dateStats[date].notStarted);
    pausedData = dates.map(date => dateStats[date].paused);
  } else if (trendViewType.value === 'week') {
    // 按周统计
    taskData.value.forEach(task => {
      const date = new Date(task.creation_date.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3'));
      const weekStart = getWeekStart(date);
      const weekKey = formatDate(weekStart);
      
      if (!dateStats[weekKey]) {
        dateStats[weekKey] = { completed: 0, inProgress: 0, notStarted: 0,paused: 0 };
      }
      
      if (task.test_status === '已上线' || task.test_status === '已完成') {
        dateStats[weekKey].completed++;
      } else if (task.test_status === '测试中') {
        dateStats[weekKey].inProgress++;
      } else if (task.test_status === '未提测') {
        dateStats[weekKey].notStarted++;
      } else if (task.test_status === '暂停') {
        dateStats[weekKey].paused++;
      }
    });
    
    dates = Object.keys(dateStats).sort();
    completedData = dates.map(date => dateStats[date].completed);
    inProgressData = dates.map(date => dateStats[date].inProgress);
    notStartedData = dates.map(date => dateStats[date].notStarted);
    pausedData = dates.map(date => dateStats[date].paused);
  } else if (trendViewType.value === 'month') {
    // 按月统计
    taskData.value.forEach(task => {
      const monthKey = task.creation_date.substring(0, 6); // YYYYMM
      
      if (!dateStats[monthKey]) {
        dateStats[monthKey] = { completed: 0, inProgress: 0, notStarted: 0,paused: 0 };
      }
      
      if (task.test_status === '已上线' || task.test_status === '已完成') {
        dateStats[monthKey].completed++;
      } else if (task.test_status === '测试中') {
        dateStats[monthKey].inProgress++;
      } else if (task.test_status === '未提测') {
        dateStats[monthKey].notStarted++;
      } else if (task.test_status === '暂停') {
        dateStats[monthKey].paused++;
      }
    });
    
    dates = Object.keys(dateStats).sort();
    completedData = dates.map(date => dateStats[date].completed);
    inProgressData = dates.map(date => dateStats[date].inProgress);
    notStartedData = dates.map(date => dateStats[date].notStarted);
    pausedData = dates.map(date => dateStats[date].paused);
  }
  
  const option = {
    title: {
      text: '',
      left: 'center'
    },
    tooltip: {
      trigger: 'axis'
    },
    legend: {
      data: ['已完成', '进行中', '未提测', '暂停']
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: dates
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: '已完成',
        type: 'line',
        data: completedData,
        label: {
          show: true,
          position: 'top'
        }
      },
      {
        name: '进行中',
        type: 'line',
        data: inProgressData,
        label: {
          show: true,
          position: 'top'
        }
      },
      {
        name: '未提测',
        type: 'line',
        data: notStartedData,
        label: {
          show: true,
          position: 'top'
        }
      },
      {
        name: '暂停',
        type: 'line',
        data: pausedData,
        label: {
          show: true,
          position: 'top'
        }
      }
    ]
  };
  
  lineChart.setOption(option);
};

// 获取周的开始日期（周一）
const getWeekStart = (date) => {
  const day = date.getDay();
  const diff = date.getDate() - day + (day === 0 ? -6 : 1); // 调整为周一开始
  return new Date(date.setDate(diff));
};

// 格式化日期为YYYYMMDD
const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}${month}${day}`;
};

// 更新个人任务分布对比柱状图
const updatePersonBarChart = () => {
  if (!personBarChart) return;
  
  const persons = Object.keys(groupedData.value);
  const completedData = persons.map(person => groupedData.value[person].completedTasks);
  const inProgressData = persons.map(person => groupedData.value[person].inProgressTasks);
  const notStartedData = persons.map(person => groupedData.value[person].notStartedTasks);
  const pausedData = persons.map(person => groupedData.value[person].pausedTasks);
  
  const option = {
    title: {
      text: '',
      left: 'center'
    },
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    legend: {
      data: ['已完成', '进行中', '未提测', '暂停']
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: persons
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: '已完成',
        type: 'bar',
        stack: 'total',
        data: completedData,
        itemStyle: {
          color: '#67C23A'
        },
        label: {
          show: true,
          position: 'inside',
          formatter: function(params) {
            return params.value > 0 ? params.value : '';
          }
        }
      },
      {
        name: '进行中',
        type: 'bar',
        stack: 'total',
        data: inProgressData,
        itemStyle: {
          color: '#E6A23C'
        },
        label: {
          show: true,
          position: 'inside',
          formatter: function(params) {
            return params.value > 0 ? params.value : '';
          }
        }
      },
      {
        name: '未提测',
        type: 'bar',
        stack: 'total',
        data: notStartedData,
        itemStyle: {
          color: '#909399'
        },
        label: {
          show: true,
          position: 'inside',
          formatter: function(params) {
            return params.value > 0 ? params.value : '';
          }
        }
      },
      {
        name: '暂停',
        type: 'bar',
        stack: 'total',
        data: pausedData,
        itemStyle: {
          color: '#F56C6C'
        },
        label: {
          show: true,
          position: 'inside',
          formatter: function(params) {
            return params.value > 0 ? params.value : '';
          }
        }
      }
    ]
  };
  
  personBarChart.setOption(option);
};

// 更新个人工作量分布饼图
const updatePersonPieChart = () => {
  if (!personPieChart) return;
  
  const data = Object.keys(groupedData.value).map(person => ({
    value: groupedData.value[person].totalTimeSpent.toFixed(2),
    name: person
  }));
  
  const option = {
    title: {
      text: '',
      left: 'center'
    },
    tooltip: {
      trigger: 'item',
      formatter: '{a} <br/>{b}: {c}小时 ({d}%)'
    },
    legend: {
      orient: 'vertical',
      left: 'left'
    },
    series: [
      {
        name: '工作量',
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        label: {
          show: true,
          formatter: '{b}\n{c}h\n({d}%)'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: '18',
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: true
        },
        data: data
      }
    ]
  };
  
  personPieChart.setOption(option);
};

const getProgressPercentage = (test_progress) => {
  if(test_progress == null){
    return 0
  }
//   console.log(typeof(test_progress)+'  process = '+test_progress)
  return test_progress 
}

const getPersonInfo = (personData,type) => {
    if(type == 1){
        return personData.totalTimeSpent
    }else if(type == 2){
        return personData.completedTasks
    }else if(type == 3){
        return personData.inProgressTasks
    }else if(type == 4){
        return personData.notStartedTasks
    }else if(type == 5){
        return personData.pausedTasks
    }
}

// 组件挂载时设置默认值
onMounted(async () => {
  // 设置endDate为今天日期
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

  await loadData();
  
  // 初始化图表
  await nextTick();
  initCharts();
  updateCharts();
});


    // 处理数据 - 按负责人分组并统计
    const processData = () => {
      const result = {};
      taskData.value.forEach(task => {
        const person = task.responsible_person;
        let filter = {}
        // 初始化负责人数据
        if (!result[person]) {
          result[person] = {
            totalTimeSpent: 0,
            completedTasks: 0,
            inProgressTasks: 0,
            notStartedTasks: 0,
            pausedTasks: 0,
            tasks: [],
            completedTasksDic:{},
            inProgressTasksDic:{},
            notStartedTasksDic:{},
            pausedTasksDic:{}
          };
        }
        
        // 累加耗时
        result[person].totalTimeSpent += parseFloat(task.totalTime) || 0;
        task.test_progress = parseInt(task.test_progress == null ? 0 : task.test_progress)
    
        
        // 统计任务状态
        if (task.test_status === '已上线' || task.test_status === '已完成') {
         result[person].completedTasksDic[task.task_id] = task
          result[person].completedTasks++;
          if(task.test_progress == null || task.test_progress == 0){
            task.test_progress = 100;
          }
        } else if (task.test_status === '测试中') {
          result[person].inProgressTasksDic[task.task_id] = task
          result[person].inProgressTasks++;
        } else if (task.test_status === '未提测') {
          result[person].notStartedTasksDic[task.task_id] = task
          result[person].notStartedTasks++;
        } else if (task.test_status === '暂停') {
          result[person].pausedTasksDic[task.task_id] = task
          result[person].pausedTasks++;
        }

                result[person].tasks.push(task);

      });
      
      //未提测任务处理
      // 1.由于时间统计不一致，需要检查是否有已上线、已完成或测试中的的任务
      // 2.检查未提测的任务是否重复
      // 3.task数组中也需要去重重复的 优先级按 a. 已完成、已上线 b. 测试中 c. 未提测,同时当个任务的多个子任务的耗时需要累加
      Object.keys(result).forEach(person => {
        const personData = result[person];
        
        // 重新计算任务数，排除已有其他状态的任务
        const notStartedTaskIds = Object.keys(personData.notStartedTasksDic);
        const completedTaskIds = Object.keys(personData.completedTasksDic);
        const inProgressTaskIds = Object.keys(personData.inProgressTasksDic);
        const pausedTaskIds = Object.keys(personData.pausedTasksDic);
        
        // 过滤掉已完成或进行中的任务ID
        const filteredNotStartedTaskIds = notStartedTaskIds.filter(taskId => 
          !completedTaskIds.includes(taskId) && !inProgressTaskIds.includes(taskId)
        );
        
        // 更新未提测任务数（去重后的数量）
        personData.notStartedTasks = filteredNotStartedTaskIds.length;
        
        // 同样处理进行中任务，排除已完成的
        const filteredInProgressTaskIds = inProgressTaskIds.filter(taskId => 
          !completedTaskIds.includes(taskId)
        );
        personData.inProgressTasks = filteredInProgressTaskIds.length;
        
        // 处理暂停任务，排除已完成的
        const filteredPausedTaskIds = pausedTaskIds.filter(taskId => 
          !completedTaskIds.includes(taskId)
        );
        personData.pausedTasks = filteredPausedTaskIds.length;
        
        // 已完成任务保持不变（去重）
        personData.completedTasks = completedTaskIds.length;
        
        // 对tasks数组进行去重，按优先级保留：已完成/已上线 > 测试中 > 暂停 > 未提测，同时累加耗时
        const taskMap = {};
        const statusPriority = {
          '已完成': 1,
          '已上线': 1,
          '测试中': 2,
          '暂停': 3,
          '未提测': 4
        };
        
        // 遍历所有任务，保留每个task_id的最高优先级任务并累加耗时
        personData.tasks.forEach(task => {
          const taskId = task.task_id;
          const currentPriority = statusPriority[task.test_status] || 999;
          const currentTimeSpent = parseFloat(task.totalTime) || 0;
          
          if (!taskMap[taskId]) {
            // 首次遇到该task_id，直接添加
            taskMap[taskId] = { ...task };
          } else {
            // 已存在该task_id，累加耗时
            const existingTimeSpent = parseFloat(taskMap[taskId].totalTime) || 0;
            taskMap[taskId].totalTime = (existingTimeSpent + currentTimeSpent).toString();
            
            // 如果当前任务优先级更高，更新任务信息（但保留累加的耗时）
            if (statusPriority[taskMap[taskId].test_status] > currentPriority) {
              const accumulatedTime = taskMap[taskId].totalTime;
              taskMap[taskId] = { ...task };
              taskMap[taskId].totalTime = accumulatedTime;
            }
          }
        });
        
        // 更新去重后的tasks数组
        personData.tasks = Object.values(taskMap);
      });
      


      groupedData.value = result;
    };
    
    // 计算总任务数
    const totalTasks = computed(() => {
      // 使用Set去重task_id，然后返回去重后的个数
      const uniqueTaskIds = new Set(taskData.value.map(task => task.task_id));
      return uniqueTaskIds.size;
    });
    
    // 计算总耗时
    const totalTimeSpent = computed(() => {
      return taskData.value.reduce((sum, task) => {
        return sum + (parseFloat(task.totalTime) || 0);
      }, 0);
    });
    
    // 计算总完成任务数
    const totalCompletedTasks = computed(() => {
      return taskData.value.filter(task => 
        task.test_status === '已上线' || task.test_status === '已完成'
      ).length;
    });
    
    // 计算总进行中任务数
    const totalInProgressTasks = computed(() => {
      // 按task_id分组，检查每个task_id的所有状态
      const taskStatusMap = {};
      
      // 收集每个task_id的所有状态
      taskData.value.forEach(task => {
        const taskId = task.task_id;
        if (!taskStatusMap[taskId]) {
          taskStatusMap[taskId] = [];
        }
        taskStatusMap[taskId].push(task.test_status);
      });
      
      // 统计进行中的任务：如果task_id在任何一天出现已完成或已上线，就不算进行中
      let inProgressCount = 0;
      Object.keys(taskStatusMap).forEach(taskId => {
        const statuses = taskStatusMap[taskId];
        const hasCompleted = statuses.some(status => status === '已完成' || status === '已上线');
        const hasInProgress = statuses.some(status => status === '测试中');
        
        // 只有当没有完成状态且有测试中状态时，才算作进行中任务
        if (!hasCompleted && hasInProgress) {
          inProgressCount++;
        }
      });
      
      return inProgressCount;
    });
    
    // 计算总未提测任务数
    const totalNotStartedTasks = computed(() => {
      // 按task_id分组，检查每个task_id的所有状态
      const taskStatusMap = {};
      
      // 收集每个task_id的所有状态
      taskData.value.forEach(task => {
        const taskId = task.task_id;
        if (!taskStatusMap[taskId]) {
          taskStatusMap[taskId] = [];
        }
        taskStatusMap[taskId].push(task.test_status);
      });
      
      // 统计未提测的任务：如果task_id在任何一天出现已完成、已上线或测试中，就不算未提测
      let notStartedCount = 0;
      Object.keys(taskStatusMap).forEach(taskId => {
        const statuses = taskStatusMap[taskId];
        const hasCompleted = statuses.some(status => status === '已完成' || status === '已上线');
        const hasInProgress = statuses.some(status => status === '测试中');
        const hasNotStarted = statuses.some(status => status === '未提测');
        
        // 只有当没有完成状态、没有进行中状态且有未提测状态时，才算作未提测任务
        if (!hasCompleted && !hasInProgress && hasNotStarted) {
          notStartedCount++;
        }
      });
      
      return notStartedCount;
    });
    
    // 计算总暂停任务数
    const totalPausedTasks = computed(() => {
      // 按task_id分组，检查每个task_id的所有状态
      const taskStatusMap = {};
      
      // 收集每个task_id的所有状态
      taskData.value.forEach(task => {
        const taskId = task.task_id;
        if (!taskStatusMap[taskId]) {
          taskStatusMap[taskId] = [];
        }
        taskStatusMap[taskId].push(task.test_status);
      });
      
      // 统计暂停的任务：如果task_id在任何一天出现已完成或已上线，就不算暂停
      let pausedCount = 0;
      Object.keys(taskStatusMap).forEach(taskId => {
        const statuses = taskStatusMap[taskId];
        const hasCompleted = statuses.some(status => status === '已完成' || status === '已上线');
        const hasPaused = statuses.some(status => status === '暂停');
        
        // 只有当没有完成状态且有暂停状态时，才算作暂停任务
        if (!hasCompleted && hasPaused) {
          pausedCount++;
        }
      });
      
      return pausedCount;
    });
    
    // 辅助方法：判断是否有提测时间
    const hasOnlineTime = (task) => {
      return task.planned_online_time || task.actual_online_time;
    };
    
    // 辅助方法：获取提测时间状态
    const getOnlineTimeStatus = (task) => {
      if (!task.planned_online_time || !task.actual_online_time) return 'info';
      
      const planned = new Date(task.planned_online_time.substring(0, 10));
      const actual = new Date(task.actual_online_time.substring(0, 10));
      
      return actual <= planned ? 'success' : 'warning';
    };
    
    // 辅助方法：获取提测时间状态文本
    const getOnlineTimeStatusText = (task) => {
      if (!task.planned_online_time) return '无计划时间';
      if (!task.actual_online_time) return '未提测';
      
      const planned = new Date(task.planned_online_time.substring(0, 10));
      const actual = new Date(task.actual_online_time.substring(0, 10));
      
      return actual <= planned ? '按时上线' : '延期上线';
    };
    
    // 辅助方法：获取进度状态
    const getProgressStatus = (progress) => {
      if (progress >= 100) return 'success';
      if (progress > 0) return '';
      return 'warning';
    };
    



</script>

<style scoped>
.task-form {
  padding: 20px;
  max-width: 600px;
}
.el-form-item {
  margin-bottom: 22px;
}
.container {
  padding: 20px;
}
.upload-demo {
  margin-bottom: 20px;
}
.test-task-statics-base{
    display: flex;
    flex-direction: row;
}

.test-task-info {
  padding: 20px;
}

.card-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
}

.total-stats {
  width: 100%;
}

.person-stats {
  padding: 10px 0;
}

::v-deep .el-collapse-item__content {
  padding-top: 0 !important;
}

::v-deep .el-progress {
  margin-top: 8px;
}
</style>