<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">

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


</template>



<script setup>
import { ref, onMounted } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { computed } from 'vue'

// 生成三个周一选项
const mondayOptions = ref([])
const startDate = ref('');
const endDate = ref('');
const selectedDepartmentId = ref(null)
const departments = ref([])
const users = ref([])
const goals = ref([])


const fetchDepartments = async () => {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_departments')
    let obj = [{ 'department_name': '全部', 'id': 0, 'group_id': 0 }]
    departments.value = [...obj, ...res.data]
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


const loadData = async () => {
   try {
    const departmentId = selectedDepartmentId.value
    const res = await http.get('WeekGoalAPI.php', {
      params: {
        action: 'list',
        startDate: startDate.value,
        endDate: endDate.value,
        department_id: departmentId,
      }
    })
    goals.value = res
  } catch (error) {
    console.error('获取数据失败:', error)
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

  loadData()
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
</script>


<style>



</style>