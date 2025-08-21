<template>

 <div class="container">
  <el-tabs v-model="activeTab" type="card" @tab-change="handleTabChange">
    <el-tab-pane label="周目标" name="week-task">
      <WeekTask @department-change="handleDepartmentChange"></WeekTask>
    </el-tab-pane>
    <el-tab-pane label="数据统计" name="week-task-statics">
      <WeekTaskStatics ref="weekTaskStaticsRef" @department-change="handleDepartmentChange"></WeekTaskStatics>
    </el-tab-pane>
    <el-tab-pane  v-if="selectedDepartmentId == 2 || selectedDepartmentId == 3  || selectedDepartmentId == 5 " :label="'导入' + (selectedDepartmentName ? '【' + selectedDepartmentName +'】': '')" name="week-task-import">
      <WeekTaskBatch v-if="selectedDepartmentId == 3"  ref="weekTaskBatchRef"></WeekTaskBatch>
      <WeekTaskBatchGame v-if="selectedDepartmentId == 2"  ref="weekTaskBatchRef"></WeekTaskBatchGame>
    </el-tab-pane>
  </el-tabs> 



</div>
</template>
<script>
import { ref, reactive,onMounted } from 'vue';
import { ElMessage,ElLoading } from 'element-plus';
import * as XLSX from 'xlsx';
import http from '@/utils/http'
import WeekTask from '@/view/WeekTask.vue';
import WeekTaskStatics from '@/view/WeekTaskStatics.vue';
import WeekTaskBatch from '@/view/WeekTaskBatch.vue';
import WeekTaskBatchGame from '@/view/WeekTaskBatchGame.vue';



const activeTab = ref('week-task');
const weekTaskStaticsRef = ref(null);
const weekTaskBatchRef = ref(null)
const selectedDepartmentId = ref(2)
const selectedDepartmentName = ref('')
const departments = ref([])

export default {
  components: {
    WeekTask,WeekTaskStatics,WeekTaskBatch,WeekTaskBatchGame
  },
  setup() {
    // 处理TAB切换事件
    const handleTabChange = (tabName) => {
      if (tabName === 'week-task-statics' && weekTaskStaticsRef.value) {
        // 通知WeekTaskStatics组件刷新数据
        if (weekTaskStaticsRef.value.loadData) {
          weekTaskStaticsRef.value.loadData();
        }
      }
    };

    // 处理子组件部门变化事件
    const handleDepartmentChange = (departmentId) => {
      selectedDepartmentId.value = departmentId;
      localStorage.setItem('department_id_cache', departmentId);
      // 更新部门名称
      parseDeparment(departmentId);
    };


    const parseDeparment = (departmentId) => {
        let departments_cache = localStorage.getItem('departments_cache')
        departments.value = JSON.parse(departments_cache)
        const cachedDept = departments.value.find(d => d.id == departmentId)
        if (cachedDept) {
          selectedDepartmentId.value = departmentId
          selectedDepartmentName.value = cachedDept.department_name
        }
    }



    onMounted(async () => {
      const cachedId = localStorage.getItem('department_id_cache') || 2
      parseDeparment(parseInt(cachedId))

}) 
    return {
      activeTab,
      weekTaskStaticsRef,
      selectedDepartmentId,selectedDepartmentName,
      handleTabChange,parseDeparment,
      handleDepartmentChange
    }
  }
}
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
  display: flex;
  flex-direction: column;
}
.upload-demo {
  margin-bottom: 20px;
}
</style>