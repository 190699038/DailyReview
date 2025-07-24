<template>
 <div class="container">
  <el-tabs v-model="activeTab" type="card">
    <el-tab-pane label="历史数据" name="history">
      
    </el-tab-pane>

    <el-tab-pane label="新增任务" name="newTask">
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
import { ref, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import * as XLSX from 'xlsx';

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
  '测试状态', '测试进度', '提测时间', '预计上线时间', 
  '实际上线时间', '实际耗时（h）', '备注'
]);

const tableData = ref([]);
const mergeMap = ref({}); // 存储合并单元格信息

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
      
      // 转换为二维数组（跳过标题行）
      // const dataSheet = XLSX.utils.sheet_to_json(firstSheet, { header: 1, raw: true, skip: 1 });
//       const dataSheet =  XLSX.utils.sheet_to_json(firstSheet, {
//   header: 1,
//   raw: false,            // 必须关闭原始值模式
//   skip: 1,
//   dateNF: 'yyyy/mm/dd hh:mm:ss'  // 显式声明格式
// });

            const dataSheet = XLSX.utils.sheet_to_json(firstSheet, { header: 1, skip: 1 ,raw: false,});


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
      processResponsibleColumn(jsonData);

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
  return ['提测时间', '预计上线时间', '实际上线时间'].includes(header);
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