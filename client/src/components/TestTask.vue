<template>
 <div class="container">
  <el-tabs v-model="activeTab" type="card">
    <el-tab-pane label="历史数据" name="history">
      <TestTaskStatics></TestTaskStatics>
    </el-tab-pane>
    <el-tab-pane label="新增任务" name="newTask">
      <div style="display: flex;flex-direction: row;">
        <el-upload
          class="upload-demo"
          :auto-upload="false"
          :show-file-list="false"
          :on-change="handleFileChange"
          accept=".xlsx, .xls"
        >
          <template #trigger>
            <el-button type="primary">选择Excel文件</el-button>
          </template>
        </el-upload>

        <el-date-picker
        v-model="selectedDate"
        type="date"
        placeholder="测试任务日期"
        format="YYYYMMDD"
        value-format="YYYYMMDD"
        style="margin-left: 8px; max-width: 200px;"
      />

        <el-button type="primary" style="margin-left: 8px;" @click="updateTestList()">更新列表测试任务</el-button>
      </div>



    <!-- 数据表格 -->
    <el-table 
      :data="tableData" 
      border 
      style="width: 100%; margin-top: 20px"
      :span-method="handleSpanMethod"
    >
      <el-table-column 
        v-for="(header, index) in tableHeaders" 
        :key="index" 
        :prop="header" 
        :label="header"
      >
        <template #default="{ row }">
          <el-progress 
            v-if="header === '测试进度'"
            :percentage="row[header]"
            :stroke-width="15"
            :color="getProgressColor(row[header])"
            :show-text="true"
            :text-inside="true" 
            />
          <span v-else-if="header === '优先级'">{{ row[header] == null ? 'N/A' : row[header] }}</span>
          <span v-else-if="isDateColumn(header)">{{ formatDate(row[header]) }}</span>
          <span v-else>{{ row[header] || '-' }}</span>
        </template>
      </el-table-column>
    </el-table>
    </el-tab-pane>
  </el-tabs>
</div>
</template>

<script setup>
import { ref, reactive,onMounted } from 'vue';
import { ElMessage,ElLoading } from 'element-plus';
import * as XLSX from 'xlsx';
import http from '@/utils/http'
import TestTaskStatics from '@/view/TestTaskStatics.vue';

const containerRef = ref();
const loading = ref(null);

// 响应式数据
const activeTab = ref('history');
const newTaskForm = reactive({
  name: '',
  priority: '',
  content: ''
});

// 表格列配置
const tableHeaders = ref([
  '负责人', '优先级', '产品', '测试内容（需求链接）', 
  '测试状态', '测试进度', '预计提测时间','提测时间', '预计上线时间','困难与障碍', 
  '实际上线时间', '预计耗时（用例+测试）','测试实际耗时（h）', '用例耗时（h）'
]);

const tableData = ref([]);
const mergeMap = ref({}); // 存储合并单元格信息
const selectedDate = ref('');

// 处理文件上传
const handleFileChange = async (file) => {
  if (!/\.(xlsx|xls)$/i.test(file.name)) {
    ElMessage.error('请上传Excel文件');
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
  
      const dataSheet = XLSX.utils.sheet_to_json(firstSheet, { header: 1, skip: 1 ,raw: false,});

     // 2. 获取合并区域
    const mergeInfo = firstSheet['!merges'] || [];

    // 3. 标记合并单元格
    mergeInfo.forEach(merge => {
      const { s, e } = merge; // s: 起始位置, e: 结束位置
      const mainValue = dataSheet[s.r][s.c]; // 主单元格的值

      // 遍历合并区域内的所有单元格
      for (let row = s.r; row <= e.r; row++) {
        for (let col = s.c; col <= e.c; col++) {
          if (row === s.r && col === s.c) continue; // 跳过主单元格
          dataSheet[row][col] = mainValue
        }
      }
    }); 

      const jsonData = dataSheet.slice(1)
      jsonData.forEach(row => {
        row.forEach((cell, index) => {
          if (cell instanceof Date) {
            // 修正 43 秒误差
            const correctedTime = cell.getTime() - importBugHotfixDiff;
            row[index] = new Date(correctedTime);
          }
          // 若需统一格式化，可在此处转换
          // 例如：row[index] = moment(cell).format('YYYY-MM-DD HH:mm:ss');
        });
    });
      // 处理负责人列合并逻辑
      // processResponsibleColumn(jsonData);
      // processProductColumn(jsonData);
      // processPlanOlineColumn(jsonData);
      // processTimetColumn(jsonData)

      // 映射为表格数据
      tableData.value = jsonData.map(row => {
        const item = {};
        tableHeaders.value.forEach((header, index) => {
        // 对日期字段保持原始字符串值
        if (isDateColumn(header)) {
            const cell = row[index] || {};
            // const value = cell.w || String(cell.v || '');
            item[header] = getTimezoneOffsetMS(cell)//isDateColumn(header) ? value : value;
          }else if(header === '测试进度'){
            item[header] = getProgressPercentage(row[index])
          }
          else {
            item[header] = row[index];
          }
        });
        return item;
      });

      console.log(tableData.value)

    } catch (error) {
      ElMessage.error(`解析失败: ${error.message}`);
    }
  };
  reader.readAsArrayBuffer(file.raw);
};

const getTimezoneOffsetMS = (dateStr) => {
  if(!dateStr || typeof(dateStr) !== 'string') return ''
  const date = unifiedDateParser(dateStr)
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // 月份补零
  const day = String(date.getDate()).padStart(2, '0'); // 日期补零

  if (dateStr.length < 11) return `${year}/${month}/${day}`;

  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  return `${year}/${month}/${day} ${hours}:${minutes}:${seconds}`;
};

const unifiedDateParser = (dateStr) => {
  console.log(dateStr)
    // 按分隔符拆分字符串（支持 / 和空格）
    const parts = dateStr.split(/[/ ]/);
    let year, month, day, hours = 0, minutes = 0, seconds = 0;
    // 根据长度判断格式类型
    if (dateStr.length > 10) {  // 完整格式: "2025/7/22 15:00:00"
         const fsm = parts[3].split(':');
        year = parts[0];
        month = parts[1];
        day = parts[2];
        hours = parseInt(fsm[0]) || 0;
        minutes = parseInt(fsm[1]) || 0;
        seconds = parseInt(fsm[2]) || 0;
    } else {  // 简写格式: "7/23/25"
        [month, day, year] = parts;
        year = 2000 + parseInt(year);  // 补全年份 -> 2025
    }

    // 统一构造 Date 对象（月份需 -1）
    const dateObj = new Date(
        parseInt(year),
        parseInt(month) - 1,  // 月份修正为 0-based
        parseInt(day),
        hours,
        minutes,
        seconds
    );

    // 验证有效性
    if (isNaN(dateObj.getTime())) {
        throw new Error(`无效日期: ${dateStr}`);
    }
    return dateObj;
}


// 提取合并单元格信息
const extractMergeData = (worksheet) => {
  const merges = {};
  if (worksheet['!merges']) {
    worksheet['!merges'].forEach(merge => {
      for (let row = merge.s.r; row <= merge.e.r; row++) {
        merges[`${row}:0`] = merge.e.r - merge.s.r + 1; // 负责人列(0)的合并信息
      }
    });
  }
  return merges;
};

// 处理负责人列数据合并
const processResponsibleColumn = (data) => {
  let lastValidValue = '';
  for (let i = 0; i < data.length; i++) {
    if (data[i][0]) {
      lastValidValue = data[i][0];
    } else if (lastValidValue) {
      data[i][0] = lastValidValue; // 填充空负责人
    }
  }
};

const processTimetColumn = (data) => {
  let lastValidValue = '';
  for (let i = 0; i < data.length; i++) {
     let testtime = data[i][9] == null ? 0 : data[i][9]
     let yltime = data[i][10] == null ? 0 : data[i][10]
     data[i][9] = parseFloat(testtime) + parseFloat(yltime)
  }
};






// 合并单元格处理
const handleSpanMethod = ({ row, column, rowIndex }) => {
  if (column.property === '负责人') {
    const spanSize = mergeMap.value[`${rowIndex}:0`];
    if (spanSize) {
      return { rowspan: spanSize, colspan: 1 };
    }
    return { rowspan: 1, colspan: 1 };
  }
};

const getProgressColor = (percentage) =>{
    if (percentage < 30) return "#f56c6c"; // 红色
    else if (percentage < 80) return "#e6a23c"; // 黄色
    else return "#5cb87a"; // 绿色[2,8](@ref)
  }

// 获取进度百分比
const getProgressPercentage = (value) => {
  if (value == null || value === '' || value === undefined) {
    return 0;
  }
  
  // 如果是字符串，尝试解析
  if (typeof value === 'string') {
    // 移除百分号
    const cleanValue = value.replace('%', '');
    const numValue = parseFloat(cleanValue);
    
    if (isNaN(numValue)) {
      return 0;
    }
    
    // 如果原值包含百分号，直接返回数值
    if (value.includes('%')) {
      return Math.min(Math.max(numValue, 0), 100);
    }
    
    // 如果是0-1之间的小数，转换为百分比
    if (numValue >= 0 && numValue <= 1) {
      return Math.round(numValue * 100);
    }
    
    // 如果是大于1的数值，假设已经是百分比
    return Math.min(Math.max(numValue, 0), 100);
  }
  
  // 如果是数字类型
  if (typeof value === 'number') {
    if (isNaN(value)) {
      return 0;
    }
    
    // 如果是0-1之间的小数，转换为百分比
    if (value >= 0 && value <= 1) {
      return Math.round(value * 100);
    }
    
    // 如果是大于1的数值，假设已经是百分比
    return Math.min(Math.max(value, 0), 100);
  }
  
  return 0;
};

// 日期格式化
const formatDate = (value) => {
  return value || '-';
};

// 判断日期列
const isDateColumn = (header) => {
  return ['提测时间', '预计上线时间', '实际上线时间','预计提测时间'].includes(header);
};
// 提交新任务
const submitTask = () => {
  if (!newTaskForm.name || !newTaskForm.priority) {
    ElMessage.warning('请填写必填项');
    return;
  }
  // TODO: 提交到后端
  ElMessage.success('任务提交成功');
  newTaskForm.name = '';
  newTaskForm.priority = '';
};

// 获取昨日日期并格式化为 YYYYMMDD
function getYesterdayFormatted() {
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1); // 减去一天得到昨日日期 [2,4](@ref)
  
  // 格式化年、月、日（补零）
  const year = yesterday.getFullYear();
  const month = String(yesterday.getMonth() + 1).padStart(2, '0'); // 月份从0开始需+1 [8](@ref)
  const day = String(yesterday.getDate()).padStart(2, '0');
  
  return `${year}${month}${day}`; // 组合为 YYYYMMDD
}

const updateTestList = async () => {
loading.value = ElLoading.service({
    lock: true,
    text: '上传任务中...',
    background: 'rgba(0, 0, 0, 0.7)'
  });

  try{
      for(let i = 0; i < tableData.value.length; i++){
        const item = tableData.value[i];
        if(item['测试内容（需求链接）'] == null || item['测试内容（需求链接）'] == '' || item['负责人'] == null || item['负责人'] == ''){
          continue;
        }
        let formData = {}
        formData.responsible_person = item['负责人']
        formData.priority = item['优先级'] == null ? 'C':item['优先级']
        formData.product = item['产品']
        formData.test_content = item['测试内容（需求链接）']
        formData.test_status = item['测试状态']
        formData.test_progress = item['测试进度']
        formData.pre_submission_time = item['预计提测时间']
        formData.submission_time = item['提测时间']
        formData.planned_online_time = item['预计上线时间']
        formData.actual_online_time = item['实际上线时间']
        formData.planned_time_spent = item['预计耗时（用例+测试）']
        formData.actual_time_spent = item['测试实际耗时（h）'] == null ? 0:item['测试实际耗时（h）']
        formData.actual_yl_time = item['用例耗时（h）'] == null ? 0:item['用例耗时（h）']
        formData.remarks = item['困难与障碍']
        formData.creation_date = selectedDate.value

        const response = await http.post('TestTask.php', formData, {
          params: { action: 'create' },
          timeout: 30000,
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        if(response.error != null){
            ElMessage.error(`修改失败: ${response.error}`);
             break
        }
    }
  } catch (error) {
    console.error('修改失败:', error);
    ElMessage.error(`修改失败: ${error.response?.data?.message || '服务器异常'}`);
  }finally{
    loading.value.close();
  }

    
};


// 组件挂载时设置默认值
onMounted(() => {
  selectedDate.value = getYesterdayFormatted();
  
});


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