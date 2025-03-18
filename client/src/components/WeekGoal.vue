<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <h2>项目组周目标</h2>
    <el-button type="primary" @click="showDialog('add')">新增周目标</el-button>
    
    <el-select
      v-model="mondayDate"
      @change="loadData"
      placeholder="选择周范围"
      style="margin-left: 1px;max-width: 200px"
    >
      <el-option
        width="200"
        v-for="option in mondayOptions"
        :key="option.value"
        :label="option.label"
        :value="option.value"
      />
    </el-select>

    <el-table :data="goals" style="width: 100%" :row-class-name="rowClassName">
      <el-table-column prop="weekly_goal" label="周目标" min-width="220"  header-align="center"/>
      <el-table-column prop="executor" label="姓名" width="120" align="center" header-align="center" />
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
          {{ row.is_new_goal ? '是' : '否' }}
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
          <el-select v-model="form.executor_id" placeholder="请选择执行人" filterable>
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
            <el-option label="是" :value="1" />
            <el-option label="否" :value="0" />
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/utils/http'

const goals = ref([])
const mondayDate = ref(getCurrentMonday())
const dialogVisible = ref(false)
const dialogType = ref('add')
const form = ref({
  id: null,
  executor: '',
  weekly_goal: '',
  is_new_goal: 0,
  priority: 5,
  status: 1,
  mondayDate: mondayDate.value
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
    form.value = { ...row }
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

// 提交表单
const submitForm = async () => {
  try {
    await http.get('WeekGoalAPI.php', {
      params: {
        action: dialogType.value === 'add' ? 'create' : 'update',
        ...form.value
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
    }
  } catch (e) {
    console.error('用户数据解析失败:', e)
    users.value = []
  }
}
</script>

<style>
.highlight-row {
  background-color: #FFF3CE !important;
}

.green-row {
  background-color: #A9D08D !important;
}
</style>
