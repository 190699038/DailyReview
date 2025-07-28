<template>
  <div class="page-container" style="width: 86%;margin-left: 7%;">
    <h2>项目组周目标</h2>
    <!-- <el-button type="success" @click="handleImport">导入Excel</el-button> -->

    <el-select v-model="mondayDate" @change="loadData" placeholder="选择周范围"
      style="max-width: 200px;margin-right: 10px;">
      <el-option width="200" v-for="option in mondayOptions" :key="option.value" :label="option.label"
        :value="option.value" />
    </el-select>

    <el-input v-model="searchText" placeholder="搜索目标或执行人" clearable style="max-width: 300px" />
    <!-- 新增负责人下拉框 -->
 
    <el-select v-model="selectedDepartmentId" placeholder="请选择部门" @change="handleDepartmentChange"
      style="max-width: 120px;margin-left:8px">
      <el-option v-for="dept in departments" :key="dept.id" :label="dept.department_name" :value="dept.id" />
    </el-select>

    <el-select v-model="selectedExecutor" placeholder="请选择负责人" 
      style="max-width: 150px;margin-left:8px">
      <el-option label="全部" value="全部" />
      <el-option v-for="dept in users" :key="dept.partner_name" :label="dept.partner_name" :value="dept.partner_name" />
    </el-select>

    <el-select v-model="selectedStatus" placeholder="状态" style="max-width: 100px;margin-left:8px">
      <el-option label="全部状态" :value="0" />
      <el-option label="进行中" :value="1" />
      <el-option label="测试中" :value="2" />
      <el-option label="已上线" :value="3" />
      <el-option label="已暂停" :value="4" />
    </el-select>

    <el-select v-model="selectedPriority" placeholder="优先级" style="max-width: 125px;margin-left:8px">
      <el-option label="优先级-ALL" value="ALL" />
      <el-option v-for="option in statusPriorityOptions" :key="option.value" :label="option.label"
        :value="option.value" />
    </el-select>


    <!-- 新增地区选择器 -->
    <el-select v-model="selectedCountry" placeholder="地区" style="max-width: 100px;margin-left:8px">
      <el-option v-for="option in countryOptions" :key="option.value" :label="option.label"
        :value="option.value" />
    </el-select>


    <!-- 新增截止日期选择器 -->
       <el-date-picker v-model="selectedCreateDate" type="date" placeholder="选择创建日期" format="YYYYMMDD"
      value-format="YYYYMMDD" style="margin-left: 8px; max-width: 200px;" />

    <el-date-picker
        v-model="selectedDate"
        type="date"
        placeholder="选择截止日期"
        format="YYYYMMDD"
        value-format="YYYYMMDD"
        style="margin-left: 8px; max-width: 200px;"
      />
    <el-date-picker v-model="selectedFinishDate" type="date" placeholder="选择完成日期" format="YYYYMMDD"
      value-format="YYYYMMDD" style="margin-left: 8px; max-width: 200px;" />

    <div style="margin-top: 15px;">
      <el-button type="primary" @click="showDialog('add')">新增周目标</el-button>
    <el-button type="primary" style="margin-left: 8px;" @click="loadData()">查询</el-button>
    <el-button type="primary" style="margin-left: 8px;" @click="copytaskSimple()">简单复制</el-button>
    <el-button type="primary" style="margin-left: 8px;" @click="copytask()">复制全部</el-button>


    </div>
    <el-table :data="filteredGoals" border :row-class-name="rowClassName">
      <!-- <el-table-column prop="id" label="序号" width="100"  header-align="center" align="center" border/> -->
      <el-table-column label="序号" width="90" align="center" header-align="center" border>
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>
      <el-table-column prop="weekly_goal" label="周目标" header-align="center" border>
        <template #default="{ row }">
          <div style="white-space: pre-line;"> <!-- 添加换行样式 -->
            【{{
              countryOptions.find(opt => opt.value === row.country)?.label || row.country
            }}】- {{ row.weekly_goal }}
          </div>
        </template>

      </el-table-column>
      <!-- <el-table-column label="地区" width="100" align="center" header-align="center" border>
        <template #default="{ row }">
          {{ 
            countryOptions.find(opt => opt.value === row.country)?.label || row.country 
          }}
        </template>
      </el-table-column> -->
      <!-- <el-table-column prop="department_name" label="部门" width="100" align="center" header-align="center" border/> -->
      <el-table-column prop="version" label="版本" width="100" align="center" header-align="center" border />
      <el-table-column prop="executor" label="负责人" width="150" align="center" header-align="center" border />
      <el-table-column label="优先级" width="80" align="center" header-align="center" border>
        <template #default="{ row }">
          {{
            { 10: 'S', 9: 'A', 8: 'B', 7: 'C' }[row.priority]
          }}
        </template>
      </el-table-column>
      <el-table-column label="完成进度" width="120" align="center" header-align="center" border>
        <template #default="{ row }">
          {{
            { 1: '进行中', 2: '测试中', 3: '已上线', 4: '已暂停', 5: '已完成', 0: '未开始' }[row.status] || '未知状态'
          }}
        </template>
      </el-table-column>

      <el-table-column prop="createdate" label="创建日期" width="120" align="center" header-align="center" border />
      <el-table-column prop="pre_finish_date" label="预计时间" width="100" align="center" header-align="center" border />
      <el-table-column prop="real_finish_date" label="上线时间" width="100" align="center" header-align="center" border />

      <el-table-column prop="remark" label="备注" width="250" align="center" header-align="center"
        class-name="custom-column" border />
      <el-table-column label="操作" header-align="center" align="center" border width="160">
        <template #default="{ row }">
          <div style="display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <div>
              <el-button size="small" type="warning" @click="showDialog('edit', row)">修改</el-button>
              <el-button size="small" type="danger" @click="deleteGoal(row)">删除</el-button>
            </div>
            <div v-if="row.status != 3 && row.status != 5" style="margin-top: 5px;">
              <el-button size="small" type="primary" @click="submitFormSimple(row, 1)">移动</el-button>
              <el-button size="small" type="success" @click="submitFormSimple(row, 0)">完成</el-button>
            </div>

          </div>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="40%">
      <el-form :model="form" label-width="100px">
        <el-form-item label="执行人" required>
          <el-select v-model="form.executor_id" multiple placeholder="请选择执行人" filterable>
            <el-option v-for="user in users" :key="user.id" :label="user.partner_name" :value="user.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级" required>
          <el-select v-model="form.priority" placeholder="请选择优先级">
            <el-option v-for="option in priorityOptions" :key="option.value" :label="option.label"
              :value="option.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="地区" required>
          <el-select v-model="form.country" placeholder="请选择地区">
            <el-option v-for="option in countryOptions" :key="option.value" :label="option.label"
              :value="option.value" />
          </el-select>
        </el-form-item>

        <el-form-item label="周目标" required>
          <el-input v-model="form.weekly_goal" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="版本">
          <el-input v-model="form.version" />
        </el-form-item>
        <el-form-item label="新增需求">
          <el-select v-model="form.is_new_goal">
            <el-option label="新增" :value="1" />
            <el-option label="默认" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="选择周范围" required>
          <el-select v-model="form.mondayDate" placeholder="请选择周范围">
            <el-option v-for="option in mondayOptions" :key="option.value" :label="option.label"
              :value="option.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="完成进度" required>
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="进行中" :value="1" />
            <el-option label="测试中" :value="2" />
            <el-option label="已上线" :value="3" />
            <el-option label="已暂停" :value="4" />
            <el-option label="已完成" :value="5" />
          </el-select>
        </el-form-item>

        <el-form-item label="截止日期">
          <el-date-picker v-model="form.pre_finish_date" type="date" placeholder="选择日期" format="YYYYMMDD"
            value-format="YYYYMMDD" />
        </el-form-item>
        <el-form-item label="实际完成时间">
          <el-date-picker v-model="form.real_finish_date" type="date" placeholder="选择日期" format="YYYYMMDD"
            value-format="YYYYMMDD" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="3" />
        </el-form-item>

      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="preFinshData">一键完成</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="importDialogVisible" title="Excel导入" width="70%">
      <div class="dialog-toolbar">
        <input type="file" ref="fileInput" @change="selectFile" accept=".xlsx" hidden width="100">
        <el-button @click="$refs.fileInput.click()">选择文件</el-button>
        <el-select v-model="importForm.selectedWeek" placeholder="选择周范围">
          <el-option v-for="option in mondayOptions" :key="option.value" :label="option.label" :value="option.value" />
        </el-select>
        <el-select v-model="importForm.status" placeholder="完成进度">
          <el-option label="进行中" :value="1" />
          <el-option label="测试中" :value="2" />
          <el-option label="已上线" :value="3" />
          <el-option label="已暂停" :value="4" />
          <el-option label="已完成" :value="5" />
        </el-select>
        <el-button type="primary" @click="uploadAll">全部上传</el-button>
      </div>

      <el-table :data="importData" height="400">
        <el-table-column prop="id" label="序号" width="100px" />
        <el-table-column prop="priority" label="优先级" width="100px">
          <template #default="{ row }">{{ { 10: 'S', 9: 'A', 8: 'B', 7: 'C' }[row.priority]
            }}</template>
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
import { getTodayDate } from '@/utils/dateUtils'
const searchText = ref('')
const departments = ref([])
const selectedDepartmentId = ref(null)
const selectedExecutor = ref('全部')

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


const copytask = async () => {
  try {
    const text = filteredGoals.value
      .map((goal, index) => `${index + 1}、${goal.priority == 10 ? '【S】' : (goal.priority == 9 ? '【A】' : (goal.priority == 8 ? '【B】' : '【C】'))} ${goal.weekly_goal} - ${countryOptions.value.find(opt => opt.value === goal.country)?.label} - ${goal.executor} - ${goal.status == 1 ? '进行中' : (goal.status == 2 ? '测试中' : (goal.status == 3 ? '已上线' : (goal.status == 4 ? '已暂停' : (goal.status == 5 ? '已完成' : '未知状态'))))}`)
      .join('\n');

    await navigator.clipboard.writeText(text);
    ElMessage.success('已复制' + filteredGoals.value.length + '条任务');
  } catch (error) {
    console.error('复制失败:', error);
    ElMessage.error('复制失败，请手动选择文本');
  }
};

const copytaskSimple = async () => {
  try {
    const text = filteredGoals.value
      .map((goal, index) => `${index + 1}、${goal.priority == 10 ? '【S】' : (goal.priority == 9 ? '【A】' : (goal.priority == 8 ? '【B】' : '【C】'))} ${countryOptions.value.find(opt => opt.value === goal.country)?.label}-${goal.weekly_goal} `)
      .join('\n');

    await navigator.clipboard.writeText(text);
    ElMessage.success('已复制' + filteredGoals.value.length + '条任务');
  } catch (error) {
    console.error('复制失败:', error);
    ElMessage.error('复制失败，请手动选择文本');
  }
};


onMounted(async () => {
  initUsers()
  generateMondayOptions()
  await fetchDepartments()
  const cachedId = localStorage.getItem('department_id_cache') || 2
  const dept = departments.value.find(d => d.id == cachedId)
  selectedDepartmentId.value = dept ? dept.id : departments.value[0]?.id || 2
  loadData()
})
const goals = ref([])
const mondayDate = ref(getCurrentMonday())
const dialogVisible = ref(false)

// 修改状态变量初始值
const selectedStatus = ref(0)  // 从null改为0，对应'全部'选项
const selectedDate = ref('')
const selectedPriority = ref('优先级-ALL')
const selectedCountry = ref('ALL')
// // 修正过滤条件逻辑
let filteredGoals = computed(() => {
  return filterData()
})

const filterData = () =>{
const searchLower = searchText.value.toLowerCase()
  let objs =  goals.value.filter(item => 
    (selectedStatus.value === 0 || Number(item.status) === selectedStatus.value) &&
    (selectedDate.value === null || selectedDate.value === '' || item.pre_finish_date === selectedDate.value) &&
    (item.weekly_goal?.toLowerCase().includes(searchLower) ||
    item.executor?.toLowerCase().includes(searchLower))
  )

  return objs
}

const selectedFinishDate = ref('')
const selectedCreateDate = ref('')

const dialogType = ref('add')
const importDialogVisible = ref(false);
const importData = ref([]);
const selectedWeek = ref('');
const executorList = ref([]);

const countryOptions = ref([
  { value: 'OA', label: 'OA系统' },
  { value: 'US1', label: '美国1' },
  { value: 'US2', label: '美国2' },
  { value: 'BR1', label: '巴西1' },
  { value: 'BR2', label: '巴西2' },
  { value: 'MX', label: '墨西哥' },
  { value: 'PE', label: '秘鲁' },
  { value: 'CL', label: '智利' },
  { value: 'AU', label: '澳大利亚' },
  { value: 'CA', label: '加拿大' },
  { value: 'PH', label: '菲律宾' },
  { value: 'ALL', label: '所有地区' },
  { value: 'YW', label: '运维' },
  { value: 'CW', label: '财务' },
  { value: 'TF', label: '投放' },
  { value: 'DF', label: '支付' },
  { value: 'QSDY', label: '奇胜-调研' },
  { value: 'QSLL', label: '奇胜-流量' },
  { value: 'QT', label: '其它' },

])
//{ 10: 'S', 9: 'A', 8: 'B', 7: 'C'}
const statusPriorityOptions = ref([
  { label: '优先级【S】', value: '10' },
  { label: '优先级【A】', value: '9' },
  { label: '优先级【B】', value: '8' },
  { label: '优先级【C】', value: '7' },
])

const form = ref({
  id: null,
  executor_id: [],
  weekly_goal: '',
  is_new_goal: 0,
  priority: 5,
  status: 1,
  mondayDate: mondayDate.value,
  pre_finish_date: '',
  real_finish_date: '',
  remark: '',
  country: 'OA',
  version: ''
})

const importForm = ref({
  status: 1,
  selectedWeek: ''
})

const priorityOptions = ref([
  { label: 'S (10)', value: 10 },
  { label: 'A (9)', value: 9 },
  { label: 'B (8)', value: 8 },
  { label: 'C (7)', value: 7 },

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
  currentMonday.setHours(0, 0, 0, 0);
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
    switch (offset) {
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
    const departmentId = selectedDepartmentId.value
    const res = await http.get('WeekGoalAPI.php', {
      params: {
        action: 'get',
        mondayDate: mondayDate.value,
        department_id: departmentId,
        executor:selectedExecutor.value == '全部' ? '':selectedExecutor.value,
        real_finish_date:selectedFinishDate.value,
        pre_finish_date:selectedDate.value,
        status:selectedStatus.value,
        country:selectedCountry.value == 'ALL' ? '':selectedCountry.value,
        priority:selectedPriority.value.indexOf('ALL') >= 0 ? '':selectedPriority.value,
        createdate:selectedCreateDate.value,
      }
    })
    goals.value = res
    // filteredGoals.value = filterData()
    console.log(filteredGoals.value)
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
    form.value.priority = parseInt(row.priority)
    form.value.status = parseInt(row.status)
  } else {
    form.value = {
      id: null,
      executor: '',
      weekly_goal: '',
      priority: 9,
      is_new_goal: 0,
      mondayDate: mondayDate.value,
      status: 1
    }
  }
  dialogVisible.value = true
}

// 导入Excel处理
const handleImport = async () => {
  importDialogVisible.value = true;
};

// 提交表单
const submitFormSimple = async (row, type) => {
  if (type == 0) {
    if (selectedDepartmentId.value == 2 || selectedDepartmentId.value == 3) {
      row.status = 3
    } else {
      row.status = 5
    }

    row.real_finish_date = getTodayDate()

  } else if (type == 1) {
    row.mondayDate = mondayOptions.value[3].value
    row.status = 1
  } else {
    ElMessage.error('请选择操作类型')
    return
  }
  try {
    const submitData = {
      ...row
    };
    await http.get('WeekGoalAPI.php', {
      params: {
        action: 'update',
        ...submitData
      }
    })
    dialogVisible.value = false
    loadData()
  } catch (error) {
    console.error('保存失败:', error)
  }
}


const preFinshData = () =>{
   //状态 
    if (selectedDepartmentId.value == 2 || selectedDepartmentId.value == 3) {
      form.value.status = 3
    } else {
      form.value.status = 5
    }

    //截止日期
    if (form.value.pre_finish_date == null || form.value.real_finish_date == '') {
      form.value.pre_finish_date = getTodayDate()
    } 

    form.value.real_finish_date = getTodayDate()

}

// 提交表单
const submitForm = async () => {
  const submitData = {
        ...form.value,
        executor_id: form.value.executor_id.join('/'),
        executor: form.value.executor_id.map(id => users.value.find(u => u.id === id)?.partner_name).join('/'),
        department_id: localStorage.getItem('department_id_cache') || 2
      };
    if (submitData.remark === 'undefined' || submitData.remark === null || submitData.remark == '') {
      submitData.remark = '无';
    }
  if(dialogType.value === 'edit'){
    const p = {
      action:'update',
      ... submitData
    }
    createAndUpdate(p)
    dialogVisible.value = false
    setTimeout(() => {
      loadData()
    }, 300);
  }else{

    const contents = submitData.weekly_goal.split('\n');
    if(contents.length > 0){
      for(let i =0 ;i < contents.length;i++){
        if(contents[i].trim() != ''){
          submitData.weekly_goal = contents[i];
          const p = {
                action:'create', 
            ... submitData
          }
          await createAndUpdate(p)
        }
      }


    }

    dialogVisible.value = false
    loadData()

  }

}

const createAndUpdate = async(params) =>{
  try {
      await http.get('WeekGoalAPI.php', {
        params
      })
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

  if (row.pre_finish_date != null && parseInt(row.status) != 3 && parseInt(row.status) != 5 && parseInt(row.status) != 4) {
    const curDate = parseInt(getTodayDate());
    if (parseInt(row.pre_finish_date) === curDate) {
      return 'pre-finish-today';
    } else if (parseInt(row.pre_finish_date) < curDate) {
      return 'pre-finish-overdue';
    }
  }


  switch (parseInt(row.status)) {
    case 1:
      return 'status-in-progress';
    case 2:
      return 'status-testing';
    case 3:
      return 'status-online';
    case 4:
      return 'status-paused';
    case 5:
      return 'status-online';
    case 0:
      return 'status-not-started';
    default:
      return '';
  }
};

const users = ref([])
const allUser = ref([])

const initUsers = () => {
  try {
    const cache = localStorage.getItem('departments_user_cache')
    users.value = cache ? JSON.parse(cache) : []

    if (users.value.length === 0) {
      // ElMessage.error('用户数据未加载，请刷新页面')
      // setTimeout(() => location.reload(), 2000) // 2秒后自动刷新
    }

    allUser.value = JSON.parse(localStorage.getItem('all_users'))

  } catch (e) {
    console.error('用户数据解析失败:', e)
    users.value = []
    ElMessage.error('用户数据异常，请刷新页面')
  }
}

const getPriorityLabel = (value) => {
  if (value === 10) return 'S'
  if (value === 9) return 'A'
  if (value === 8) return 'B'
  if (value === 7) return 'C'
  if (value === 6) return 'C'
  if (value === 5) return 'C'
  if (value === 4) return 'C'
  if (value === 3) return 'C'
  if (value === 2) return 'C'
  return value
};

const uploadAll = async () => {
  try {
    const departmentId = localStorage.getItem('department_id_cache') || 2;

    const transformedData = importData.value.map((item, index) => {
      // 精确匹配执行人
      const executorNames = item.executor.split('/').map(name => name.trim());
      const matchedUsers = executorNames.map(name => {
        const user = allUser.value.find(u => u.partner_name === name);
        if (!user) {
          throw new Error(`第${index + 1}行执行人'${name}'不存在`);
        }
        return user;
      });

      let selectedWeek = importForm.value.selectedWeek;
      if (selectedWeek === '') {
        throw new Error(`必须选择正确的周期范围`);
      }


      return {
        weekly_goal: item.weekly_goal,
        executor_id: matchedUsers.map(u => u.id).join('/'),
        executor: item.executor,
        priority: getPriorityLabel(item.priority),
        is_new_goal: 0,
        status: importForm.value.status,
        mondayDate: importForm.value.selectedWeek,
        department_id: matchedUsers.map(u => u.department_id).join('/'),
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

    const parsedData = await parseExcelFile(file, 'weekly');
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
        const workbook = XLSX.read(data, { type: 'array' });
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

.text-line-through {
  text-decoration: line-through;
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

.dialog-toolbar {
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.el-table) {
  border: 1px solid #d0d7e5;
  font-family: '微软雅黑';
}

:deep(.el-table th) {
  background: #004bff;
  /* color: white; */
  font-weight: bold;
}

:deep(.el-table__row--striped) {
  background: #f8f9fa;
}

:deep(.el-table__cell) {
  border-right: 1px solid #d0d7e5 !important;
  border-bottom: 1px solid #d0d7e5 !important;
}

.status-in-progress {
  background-color: #fff !important;
}

.status-testing {
  background-color: #e8f5e9 !important;
}

.status-online {
  background-color: #A9D08D !important;
}

.status-paused {
  background-color: #b0abab !important;
}

.status-not-started {
  background-color: #f3e5f5 !important;
}

.pre-finish-today {
  background-color: #fff3ce !important;
}

.pre-finish-overdue {
  background-color: #e2adad !important;
}

/* 设置该列的字体大小 */
.custom-column .cell {
  font-size: 12px;
}
</style>
