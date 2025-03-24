<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <h2>周报历史查看</h2>
    
    <el-select
      v-model="selectedPeriod"
      @change="loadData"
      placeholder="选择周周期"
      style="margin-right: 20px;width: 200px"
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

    <el-table
      :data="tableData"
      style="width: 100%"
      :span-method="cellMerge"
      border
    >
      <el-table-column prop="period" label="周期" width="120" />
      <el-table-column prop="goal" label="个人周目标" min-width="300" />
      <el-table-column prop="plan" label="每日方案" min-width="300" />
      <el-table-column prop="date" label="日期" width="120" />
    </el-table>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'

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
    value: p,
    // label: p
    label: `${String(p).slice(0,4)}年${String(p).slice(4,6)}月第${Math.ceil(String(p).slice(6)/7)}周`
  }))
  weekPeriodOptions.value = periods

  loadData()
})

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

    const params = {
      action: 'get_history',
      week_period: selectedPeriod.value,
      executor_id: selectedExecutor.value,
      department_id: departmentId
    }
    
    const res = await http.get('WeekGoalAPI.php', { params })
    tableData.value = processTableData(res)
  } catch (error) {
    ElMessage.error('数据加载失败')
  }
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