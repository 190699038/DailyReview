<template>

 <div class="container">
  <el-tabs v-model="activeTab" type="card">
    <el-tab-pane label="周目标" name="week-task">
      <WeekTask></WeekTask>
    </el-tab-pane>
    <el-tab-pane label="数据统计" name="week-task-statics">
      <WeekTaskStatics></WeekTaskStatics>
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

const activeTab = ref('week-task');

export default {
  components: {
    WeekTask,WeekTaskStatics
  },
  setup() {
    return {
      activeTab
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
}
.upload-demo {
  margin-bottom: 20px;
}
</style>