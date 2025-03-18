<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <h2>项目组周目标</h2>
    <el-button type="primary" @click="showDialog('add')">新增周目标</el-button>
    <el-button type="success" @click="handleImport">导入Excel</el-button>
    
    <el-select
        v-model="mondayDate"
        @change="loadData"
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
      <el-input
        v-model="searchText"
        placeholder="搜索目标或执行人"
        clearable
        style="max-width: 300px"
      />

    <el-table :data="filteredGoals" style="width: 100%" :row-class-name="rowClassName">
      <el-table-column prop="weekly_goal" label="周目标" min-width="220"  header-align="center"/>
      <el-table-column prop="executor" label="姓名" width="200" align="center" header-align="center" />
      <el-table-column label="优先级" width="120" align="center" header-align="center">
        <template #default="{ row }">
          {{ 
            {10:'A+',9:'A',8:'A-',7:'B+',6:'B',5:'B-',4:'C+',3:'C',2:'C-'}[row.priority] 
          }}
        </template>
      </el-table-column>
      <el-table-column label="完成进度" width="120" align="center" header-align="center">
        <template #default="{ row }">
          {{ 
            {1:'进行中',2:'测试中',3:'已上线',4:'已暂停',0:'未开始'}[row.status] || '未知状态'
          }}
        </template>
      </el-table-column>
      <el-table-column prop="department_name" label="部门" width="150" align="center" header-align="center" />
      <el-table-column label="新增需求" width="120" align="center" header-align="center">
        <template #default="{ row }">
          {{ {1:'新增需求',0:'计划需求'}[row.is_new_goal] || '未知状态' }}
        </template>
      </el-table-column>
      <el-table-column prop="createdate" label="创建日期" width="120" align="center" header-align="center" />
      <el-table-column label="操作" width="200"  header-align="center" align="center">
        <template #default="{ row }">
          <div style="display: flex; justify-content: center; align-items: center; gap: 8px">
            <el-button size="small" @click="showDialog('edit', row)">修改</el-button>
            <el-button size="small" type="danger" @click="deleteGoal(row)">删除</el-button>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="40%">
      <el-form :model="form" label-width="100px">
        <el-form-item label="执行人" required>
          <el-select v-model="form.executor_id" multiple placeholder="请选择执行人" filterable>
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.partner_name"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级" required>
          <el-select v-model="form.priority" placeholder="请选择优先级">
            <el-option
              v-for="option in priorityOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="周目标" required>
          <el-input v-model="form.weekly_goal" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="新增需求">
          <el-select v-model="form.is_new_goal">
            <el-option label="新增需求" :value="1" />
            <el-option label="正常取消" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="选择周范围" required>
          <el-select v-model="form.mondayDate" placeholder="请选择周范围">
            <el-option
              v-for="option in mondayOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="完成进度" required>
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="进行中" :value="1" />
            <el-option label="测试中" :value="2" />
            <el-option label="已上线" :value="3" />
            <el-option label="已暂停" :value="4" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="importDialogVisible" title="Excel导入" width="70%">
      <div class="dialog-toolbar">
        <input type="file" ref="fileInput" @change="selectFile" accept=".xlsx" hidden width="100">
        <el-button @click="$refs.fileInput.click()">选择文件</el-button>
        <el-select v-model="importForm.selectedWeek" placeholder="选择周范围">
          <el-option
            v-for="option in mondayOptions"
            :key="option.value"
            :label="option.label"
            :value="option.value"
          />
        </el-select>
        <el-select v-model="importForm.status" placeholder="完成进度">
          <el-option label="进行中" :value="1" />
          <el-option label="测试中" :value="2" />
          <el-option label="已上线" :value="3" />
          <el-option label="已暂停" :value="4" />
        </el-select>
        <el-button type="primary" @click="uploadAll">全部上传</el-button>
      </div>
      
      <el-table :data="importData" height="400">
        <el-table-column prop="id" label="序号" width="100px" />
        <el-table-column prop="priority" label="优先级" width="100px">
          <template #default="{row}">{{ {10:'A+',9:'A',8:'A-',7:'B+',6:'B',5:'B-',4:'C+',3:'C',2:'C-'}[row.priority] }}</template>
        </el-table-column>
        <el-table-column prop="weekly_goal" label="任务内容" />
        <el-table-column prop="executor" label="执行人" width="150" />
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import { computed } from 'vue'
import * as XLSX from 'xlsx'
import { parseExcelFile } from '@/utils/excelParser'
const searchText = ref('')
const goals = ref([])
const mondayDate = ref(getCurrentMonday())
const dialogVisible = ref(false)

const filteredGoals = computed(() => {
  if (!searchText.value) return goals.value
  const searchLower = searchText.value.toLowerCase()
  return goals.value.filter(item => 
    (item.weekly_goal?.toLowerCase().includes(searchLower) ||
    item.executor?.toLowerCase().includes(searchLower))
  )
})
const dialogType = ref('add')
const importDialogVisible = ref(false);
const importData = ref([]);
const selectedWeek = ref('');
const executorList = ref([]);

const form = ref({
  id: null,
  executor_id: [],
  weekly_goal: '',
  is_new_goal: 0,
  priority: 5,
  status: 1,
  mondayDate: mondayDate.value
})

const importForm = ref({
  status: 1,
  selectedWeek: ''
})

const priorityOptions = ref([
  { label: 'A+ (10)', value: 10 },
  { label: 'A (9)', value: 9 },
  { label: 'A- (8)', value: 8 },
  { label: 'B+ (7)', value: 7 },
  { label: 'B (6)', value: 6 },
  { label: 'B- (5)', value: 5 },
  { label: 'C+ (4)', value: 4 },
  { label: 'C (3)', value: 3 },
  { label: 'C- (2)', value: 2 }
])

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

// 生成三个周一选项
const mondayOptions = ref([])

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

// 加载数据
const loadData = async () => {
  mondayOptions.value = generateMondayOptions()
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2
    const res = await http.get('WeekGoalAPI.php', {
      params: {
        action: 'get',
        mondayDate: mondayDate.value,
        department_id: departmentId
      }
    })
    goals.value = res
  } catch (error) {
    console.error('获取数据失败:', error)
  }
}

// 显示弹窗
const dialogTitle = ref('')

const showDialog = (mode, row) => {
  dialogType.value = mode
  dialogTitle.value = mode === 'add' ? '新增周目标' : '修改周目标'
  if (dialogType.value === 'edit') {
    const executorNames = row.executor.split('/');
    const executorIds = executorNames
      .map(name => users.value.find(u => u.partner_name === name)?.id)
      .filter(id => id !== undefined);
    
    form.value = { 
      ...row,
      executor_id: executorIds.length > 0 ? executorIds : [row.executor_id]
    }
  } else {
    form.value = {
      id: null,
      executor: '',
      weekly_goal: '',
      priority:5,
      is_new_goal: 0,
      mondayDate: mondayDate.value
    }
  }
  dialogVisible.value = true
}

// 导入Excel处理
const handleImport = async () => {
  importDialogVisible.value = true;
};

// 提交表单
const submitForm = async () => {
  try {
    const submitData = {
      ...form.value,
      executor_id: form.value.executor_id.join('/'),
      executor: form.value.executor_id.map(id => users.value.find(u => u.id === id)?.partner_name).join('/'),
      department_id: localStorage.getItem('department_id_cache') || 2
    };
    await http.get('WeekGoalAPI.php', {
      params: {
        action: dialogType.value === 'add' ? 'create' : 'update',
        ...submitData
      }
    })
    dialogVisible.value = false
    loadData()
  } catch (error) {
    console.error('保存失败:', error)
  }
}

// 删除目标
const deleteGoal = async (row) => {
  try {
    await http.get('WeekGoalAPI.php', {
      params: {
        action: 'delete',
        id: row.id
      }
    })
    loadData()
  } catch (error) {
    console.error('删除失败:', error)
  }
}

// 行样式处理
const rowClassName = ({ row }) => {
  let style = ''
  if (row.status === 3) {
    style = 'green-row'
  }else{
    if(row.is_new_goal === 1){
      style = 'highlight-row'
    }
  }
  return style
}

const users = ref([])

onMounted(() => {
  initUsers()
  loadData()
  generateMondayOptions()
})

const initUsers = () => {
  try {
    const cache = localStorage.getItem('departments_user_cache')
    users.value = cache ? JSON.parse(cache) : []
    
    if(users.value.length === 0) {
      ElMessage.error('用户数据未加载，请刷新页面')
      setTimeout(() => location.reload(), 2000) // 2秒后自动刷新
    }
  } catch (e) {
    console.error('用户数据解析失败:', e)
    users.value = []
    ElMessage.error('用户数据异常，请刷新页面')
  }
}

const uploadAll = async () => {
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2;
    
    const transformedData = importData.value.map((item, index) => {
      // // 精确匹配执行人
      const matchedUsers = users.value.filter(u => 
        item.executor.split('/').some(name => name.trim() === u.partner_name)
      );
      
      // if (!matchedUsers.length) {
      //   throw new Error(`第${index + 1}行：未找到匹配的执行人【${item.executor}】`);
      // }

      let selectedWeek = importForm.value.selectedWeek;
      if (selectedWeek === '') {
        throw new Error(`必须选择正确的周期范围`);      
      }


      return {
        weekly_goal: item.weekly_goal,
        executor_id: matchedUsers.map(u => u.id).join('/'),
        executor:item.executor,
        priority: item.priority,
        is_new_goal: 0,
        status: importForm.value.status,
        mondayDate: importForm.value.selectedWeek,
        department_id: departmentId,
        _row_index: index + 1  // 添加行号标识
      };
    });

    // 添加超时和错误处理
await http.post('WeekGoalAPI.php', transformedData, {
  params: { action: 'batch_create' },
  timeout: 30000,
  headers: {
    'Content-Type': 'multipart/form-data'
  }
});
    
    ElMessage.success('批量导入成功');
    importDialogVisible.value = false;
    loadData();
  } catch (error) {
    console.error('导入失败:', error);
    ElMessage.error('导入失败：' + error.message);
  }
};


const selectFile = async (e) => {
  try {
    const file = e.target.files[0];
    
    const parsedData = await parseExcelFile(file);
    importData.value = parsedData.map(item => ({
      id: item.id,
      priority: item.priority,
      weekly_goal: item.content,  // 映射content字段
      executor: item.executor
    }));
    console.log(importData.value);
  } catch (error) {
    ElMessage.error({
      message: `文件解析失败: ${error.message}`,
      duration: 5000
    });
    importData.value = [];
    console.error(error);
  }
};

const readExcel = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        resolve(XLSX.utils.sheet_to_json(sheet));
      } catch (e) {
        reject(e);
      }
    };
    reader.onerror = reject;
    reader.readAsArrayBuffer(file);
  });
};

</script>

<style>
.highlight-row {
  background-color: #FFF3CE !important;
}

.green-row {
  background-color: #A9D08D !important;
}

:deep(.el-dialog) {
  border-radius: 10px;
  overflow: hidden;
}

:deep(.el-dialog__header) {
  padding: 20px 20px 10px;
  border-radius: 10px 10px 0 0;
}

:deep(.el-dialog__body) {
  padding: 15px 20px;
}

:deep(.el-dialog__footer) {
  padding: 10px 20px 20px;
  border-radius: 0 0 10px 10px;
}

.dialog-toolbar{
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
