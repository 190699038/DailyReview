<template>
  <div class="week-task-container">
    <el-upload
      v-if="!isFileUploaded"
      action=""
      accept=".xlsx,.xls"
      :show-file-list="false"
      :auto-upload="false"
      :on-change="handleFileUpload"
      class="upload-demo"
    >
      <template #trigger>
        <el-button type="primary" icon="upload">导入Excel文件</el-button>
      </template>
      <template #tip>
        <div class="tip-text">
          <div>仅支持.xlsx/.xls格式文件</div>
          <div>格式要求：A列优先级 | C列需求内容 | D列研发人员 | F-K列研发、提测、发布时间 | L列备注</div>
          <div>第一行需包含时间范围（如：08-18~08-23），系统将自动计算日期星期对应关系</div>
          <div>备注列包含"暂停"关键字的数据将被过滤</div>
        </div>
      </template>
    </el-upload>

    <!-- 批量上传按钮 -->
    <div v-if="isFileUploaded && taskList.length" class="batch-upload-container">
      <el-button type="success" @click="handleBatchUpload" :loading="isUploading">批量上传</el-button>
    </div>

    <!-- 日期选择弹窗 -->
    <el-dialog v-model="showDateDialog" title="选择任务完成周" width="400px" :close-on-click-modal="false">
      <div style="text-align: center; margin-bottom: 20px;">
        <p>请输入当前任务完成周的周一的日期</p>
        <el-date-picker
          v-model="selectedDate"
          type="date"
          placeholder="选择日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          style="width: 100%;"
        />
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDateDialog = false">取消</el-button>
          <el-button type="primary" @click="confirmDate">确定</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 筛选输入框 -->
    <div v-if="taskList.length" class="filter-container">
      <el-input
        v-model="nameFilter"
        placeholder="请输入研发人员姓名进行筛选"
        prefix-icon="Search"
        clearable
        style="width: 300px;"
      />
    </div>

    <el-table 
      :data="filteredTaskList" 
      border 
      stripe
      v-if="taskList.length"
      class="task-table"
    >
      <el-table-column prop="id" label="序号" width="60" align="center" />
      <el-table-column prop="priority" label="优先级" width="80" align="center">
        <template #default="{ row }">
          <el-tag :type="priorityTagMap[row.priority]">{{ row.priority }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="projectName" label="项目名称" min-width="150" />
      <el-table-column prop="content" label="需求内容" min-width="200" />
      <el-table-column prop="developer" label="产品经理" width="120" align="center" />
      <el-table-column prop="confirmTime" label="需求确认时间" min-width="120" />
      <el-table-column prop="crossWeek" label="跨周状态" width="100" align="center">
        <template #default="{ row }">
          <el-tag :type="row.crossWeek === 1 ? 'warning' : 'success'">{{ row.crossWeek === 1 ? '跨周' : '本周' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="remark" label="备注" min-width="100" />
    </el-table>

    <div v-if="noDataText" class="empty-tip">{{ noDataText }}</div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import * as XLSX from 'xlsx';
import { ElMessage } from 'element-plus';
import http from '@/utils/http';

export default {
  name: 'WeekTaskBatch',
  
  setup() {
    const taskList = ref([]);
    const nameFilter = ref('');
    const noDataText = ref('请上传Excel文件');
    const isFileUploaded = ref(false);
    const isUploading = ref(false);
    const mondayDate = ref('');
    const showDateDialog = ref(false);
    const selectedDate = ref('');
    const priorityTagMap = {
      'S': 'danger',
      'A': 'warning',
      'B': 'primary',
      'C': 'success'
    };

    // 计算属性：根据名字筛选任务列表
    const filteredTaskList = computed(() => {
      if (!nameFilter.value) {
        return taskList.value;
      }
      return taskList.value.filter(task => 
        task.developer.includes(nameFilter.value)
      );
    });

    const handleFileUpload = (file) => {
      if (!mondayDate.value || mondayDate.value === '') {
        showDateDialog.value = true;
        return
      } 


      if (!file) return;
      
      const fileExt = file.name.split('.').pop().toLowerCase();
      if (!['xlsx', 'xls'].includes(fileExt)) {
        ElMessage.error('仅支持.xlsx/.xls格式文件');
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const worksheet = workbook.Sheets[workbook.SheetNames[0]];
          parseExcelData(worksheet);
          isFileUploaded.value = true;
          ElMessage.success('文件解析成功');
        } catch (error) {
          console.error('解析错误:', error);
          ElMessage.error('文件解析失败，请检查格式');
          noDataText.value = '文件解析错误，请重试';
        }
      };
      
      noDataText.value = '';
      reader.readAsArrayBuffer(file.raw);
    };

    const normalizePriority = (value) => {
      if (!value) return '';
      
      // 转换为大写并取第一个字符
      const firstChar = value.toString().toUpperCase().charAt(0);
      
      // 检查是否是合法优先级
      if (['S', 'A', 'B', 'C'].includes(firstChar)) {
        return firstChar;
      }
      
      return '';
    };

    // 从时间字符串中提取日期范围并生成日期星期对应关系
    const extractDateRange = () => {
      const dateMap = new Map();
    
      // 如果无法从Excel解析时间范围，使用mondayDate.value作为默认周一起始日期
      if (mondayDate.value) {
        const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        const year = parseInt(mondayDate.value.substring(0, 4));
        const month = parseInt(mondayDate.value.substring(4, 6)) - 1;
        const day = parseInt(mondayDate.value.substring(6, 8));
        const mondayDateObj = new Date(year, month, day);
        
        // 生成一周的日期（周一到周日）
        for (let i = 0; i < 7; i++) {
          const currentDate = new Date(mondayDateObj);
          currentDate.setDate(mondayDateObj.getDate() + i);
          const monthDay = `${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(currentDate.getDate()).padStart(2, '0')}`;
          const weekday = weekdays[currentDate.getDay()];
          dateMap.set(monthDay, weekday);
        }
      }
      
      return dateMap;
    };

    const parseExcelData = (worksheet) => {
      taskList.value = [];
      let currentContent = '';
      let currentProjectName = '';
      let passDict = {};
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      
      // 从第一行提取时间范围，计算周一日期
      let dateMap = new Map();
      let columnDateMap = new Map(); // 列索引到日期的映射
        dateMap = extractDateRange();
        console.log('日期星期对应关系:', Object.fromEntries(dateMap));
        
        // 创建列索引到日期的映射 (I-N列对应索引8-13，周一到周六)
        const dateEntries = Array.from(dateMap.entries());
        for (let i = 0; i < dateEntries.length && i < 6; i++) {
          const [monthDay, weekday] = dateEntries[i];
          const [month, day] = monthDay.split('-');
          const currentYear = new Date().getFullYear();
          const fullDate = `${currentYear}${month}${day}`;
          columnDateMap.set(8 + i, fullDate); // I列=8, J列=9, ..., N列=13
          
          // 设置周一日期
          if (weekday === '一') {
            mondayDate.value = fullDate;
          }
        }
        console.log('列索引到日期映射:', Object.fromEntries(columnDateMap));
        console.log('周一日期:', mondayDate.value);
      
      // 从第2行开始解析数据
      for (let i = 2; i < jsonData.length; i++) {
        const row = jsonData[i];
        
        // 检查O列备注是否包含"暂停"关键字
        const remark = row[14]?.toString().trim() || '';
        if (remark.includes('暂停')) {
          continue;
        }
        
        // 处理B列项目名称的合并单元格
        // const projectName = row[1]?.toString().trim() || '';
        // if (projectName && projectName !== currentProjectName) {
        //   currentProjectName = projectName;
        // }

                // 处理项目组合并单元格
        if (row.length == 1 && row[0] && typeof row[0] === 'string') {
          const projectName = getCountryName(row[0]);
          console.log('currentProject', projectName)
          if (projectName && projectName !== currentProjectName) {
            currentProjectName = projectName;
          }
        }

        
        // 处理C列需求内容的合并单元格
        const content = row[2]?.toString().trim() || '';
        if (content && content !== currentContent) {
          currentContent = content;
        }
        
        // 读取H列产品经理
        const productManager = row[7]?.toString().trim() || '';
        
        // 检查I-N列是否有任何字符
        let hasAnyContentInTimeColumns = false;
        for (let col = 8; col <= 13; col++) { // I-N列对应索引8-13
          const cellValue = row[col]?.toString().trim() || '';
          if (cellValue) {
            hasAnyContentInTimeColumns = true;
            break;
          }
        }
        
        // 如果I-N列没有任何字符，跳过该行任务
        if (!hasAnyContentInTimeColumns) {
          continue;
        }
        
        // 检查I-N列是否有需求确认信息（处理合并单元格情况）
        let hasTimeInfo = false;
        let confirmTime = '';
        let crossWeek = 0 // 默认本周完成
        let hasGuoKeyword = false; // 标记是否已找到"过"关键字
        
        for (let col = 8; col <= 13; col++) { // I-N列对应索引8-13
          const cellValue = row[col]?.toString().trim() || '';
          if (cellValue.includes('过')) {
            passDict[currentContent] = true
            // 当天完成需求确认，cross_week为0
            hasTimeInfo = true;
            const columnDate = columnDateMap.get(col);
            confirmTime = columnDate || '';
            crossWeek = 0; // 有'过'字符，cross_week为0
            hasGuoKeyword = true;
            break; // 找到"过"关键字后立即跳出循环，忽略其他关键字
          } else if (passDict[currentContent] == null && cellValue && (cellValue.includes('需求') || cellValue.includes('设计'))) {
            // 只有在没有找到"过"关键字的情况下，才处理其他关键字
            hasTimeInfo = true;
            crossWeek = 1; // 本周完成不了
          }
        }
        
        // 处理任务：当有项目名称、需求内容或有时间信息时才处理这一行
        if ((currentProjectName && currentContent) || hasTimeInfo) {
          const priority = normalizePriority(row[0]); // A列优先级
          
          // 检查是否已存在相同内容和项目的任务
          const existingTaskIndex = taskList.value.findIndex(t => 
            t.content === currentContent && t.projectName === currentProjectName
          );
          
          if (existingTaskIndex >= 0) {
            // 更新现有任务
            const existingTask = taskList.value[existingTaskIndex];
            
            // 更新时间信息（合并单元格情况下的时间信息）
            if (confirmTime && !existingTask.confirmTime) {
              existingTask.confirmTime = confirmTime;
            }
            if (crossWeek !== undefined) {
              existingTask.crossWeek = crossWeek;
            }
            if (productManager && !existingTask.developer) {
              existingTask.developer = productManager;
            }
            if (remark && !existingTask.remark) {
              existingTask.remark = remark;
            }
          } else {
            // 创建新任务项
            const task = {
              id: taskList.value.length + 1,
              priority: priority,
              projectName: currentProjectName,
              content: currentContent,
              developer: productManager,
              confirmTime: confirmTime,
              crossWeek: crossWeek,
              remark: remark
            };
            taskList.value.push(task);
          }
        }
      }

      console.log('解析后的任务列表:', taskList.value);
      
      if (taskList.value.length === 0) {
        noDataText.value = '未解析到有效数据';
      }
    };


    

    const getCountry = (project) => {
      if(project.includes('美国1')){
      return 'US1'
      }else if(project.includes('美国2')){
        return 'US2'
      }else if(project.includes('美国3')){
        return 'US3'
      }else if(project.includes('巴西1')){
        return 'BR1'
      }else if(project.includes('巴西2')){
        return 'BR2'
      }else if(project.includes('墨西哥')){
        return 'MX'
      }else if(project.includes('智利')){
        return 'CL'
      }else if(project.includes('加拿大')){
        return 'CA'
      }else if(project.includes('澳大利亚') || project.includes('澳洲')){
        return 'AU'
      }else if(project.includes('秘鲁')){
        return 'PE'
      } else if(project.includes('投放')){
        return 'TF'
      } else if(project.includes('OA') || project.includes('客服')){
        return 'OA'
      } else if(project.includes('通用')){
        return 'QT'
      }
      else {
        return 'QT'
      }
    };


  const getCountryName = (project) => {
    if(project.includes('美国1')){
      return '美国1'
    }else if(project.includes('美国2')){
      return '美国2'
    }else if(project.includes('美国3')){
      return '美国3'
    }else if(project.includes('巴西1')){
      return '巴西1'
    }else if(project.includes('巴西2')){
      return '巴西2'
    }else if(project.includes('墨西哥')){
      return '墨西哥'
    }else if(project.includes('智利')){
      return '智利'
    }else if(project.includes('加拿大')){
      return '加拿大'
    }else if(project.includes('澳大利亚') || project.includes('澳洲')){
      return '澳大利亚'
    }else if(project.includes('秘鲁')){
      return '秘鲁'
    } else if(project.includes('投放')){
      return '投放'
    } else if(project.includes('OA') || project.includes('客服')){
      return 'OA'
    } else if(project.includes('通用')){
      return '所有地区'
    }
    else {
      return '其它'
    }
  }


  const getExecutorId = (name) => {
    //通过name 通过 ’/‘ 分割
    let names = name.split('/') 
    // 读取缓存数据 departments_user_cache   partner_name 对应names , executor_id 值为 id 返回 内容为: id1/id2
    let departments_user_cache = localStorage.getItem('departments_user_cache')
    if (departments_user_cache) {
      let userList = JSON.parse(departments_user_cache)
      let executorId = ''
      names.forEach((item,index) => {
        let user = userList.find((user) => user.partner_name === item)
        if (user) {
          //判断是不是最后一个数据，最后一个不需要加/
          if(index == names.length - 1){
            executorId += user.id 
          }else{
            executorId += user.id + '/'
          }
        }
      })
       if(executorId.charAt(executorId.length - 1) == '/'){
        executorId = executorId.substring(0,executorId.length - 1)
      }
      return executorId
    }
  }

const getDeveloper = (developers) => {
    //通过name 通过 ’/‘ 分割
    let names = developers.split('/') 
    // 读取缓存数据 departments_user_cache   partner_name 对应names , executor_id 值为 id 返回 内容为: id1/id2
    let departments_user_cache = localStorage.getItem('departments_user_cache')
    if (departments_user_cache) {
      let userList = JSON.parse(departments_user_cache)
      let executor = ''
      names.forEach((item,index) => {
        let user = userList.find((user) => user.partner_name.includes(item))
        if (user) {
          //判断是不是最后一个数据，最后一个不需要加/
          if(index == names.length - 1){
            executor += user.partner_name 
          }else{
            executor += user.partner_name + '/'
          }
        }
      })
      //如果executor最后一个字符是/ ，则去掉
      if(executor.charAt(executor.length - 1) == '/'){
        executor = executor.substring(0,executor.length - 1)
      }
      return executor
    }
}


    const createTask = async() => {
      isUploading.value = true;
      let successCount = 0;
      let errorCount = 0;
      
      try {
        for (const item of taskList.value)
         {
        // const item = taskList.value[0]
          let developers = getDeveloper(item.developer)
          let params = {
            id: null,
            executor: developers,
            weekly_goal: item.content,
            priority: getPriority(item.priority),
            is_new_goal: 0,
            mondayDate: mondayDate.value,
            status: 1,
            country:getCountry(item.projectName),
            executor_id: getExecutorId(developers),
            cross_week:item.crossWeek,
            remark:item.remark,
            // pre_finish_date:'',
            department_id:localStorage.getItem('department_id_cache') || 2
          }

          if(item.confirmTime){
            params.pre_finish_date = item.confirmTime
          }


          console.log(params)
          try {
              await http.get('WeekGoalAPI.php', {
                params: {
                  action: 'create',
                  ...params
                }
              })    
              successCount++
          } catch (error) {
            console.error('保存失败:', error)
            errorCount++
          }
        }
        
        if (errorCount === 0) {
          ElMessage.success(`批量上传成功！共上传${successCount}条任务`);
        } else {
          ElMessage.warning(`上传完成！成功${successCount}条，失败${errorCount}条`);
        }
      } finally {
        isUploading.value = false;
      }
    };

    // 处理批量上传按钮点击
    const handleBatchUpload = () => {
      
      createTask();

    };

    // 确认日期选择
    const confirmDate = () => {
      if (!selectedDate.value) {
        ElMessage.warning('请选择日期');
        return;
      }
      
      // 将日期格式转换为YYYYMMDD
      const dateStr = selectedDate.value.replace(/-/g, '');
      mondayDate.value = dateStr;
      showDateDialog.value = false;
      
      // 执行批量上传
      createTask();
    };

    // 获取当前周一日期
    const getCurrentMonday = () => {
      const date = new Date();
      const day = date.getDay();
      const diff = date.getDate() - day + (day === 0 ? -6 : 1);
      date.setDate(diff);
      date.setHours(0, 0, 0, 0);
      const yyyy = date.getFullYear();
      const mm = String(date.getMonth() + 1).padStart(2, '0');
      const dd = String(date.getDate()).padStart(2, '0');
      return `${yyyy}${mm}${dd}`;
    };

    const getPriority = (priority) => {
      if (priority === 'S') {
        return 10;
      } else if (priority === 'A') {
        return 9;
      } else if (priority === 'B') {
        return 8;
      } else {
        return 7;
      }
    };




    return {
      taskList,
      filteredTaskList,
      nameFilter,
      noDataText,
      priorityTagMap,
      isFileUploaded,
      isUploading,
      mondayDate,
      showDateDialog,
      selectedDate,
      handleFileUpload,
      createTask,
      handleBatchUpload,
      confirmDate
    };
  }
};
</script>

<style scoped>
.week-task-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.upload-demo {
  margin-bottom: 30px;
}

.batch-upload-container {
  margin-bottom: 20px;
  text-align: center;
}

.filter-container {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.tip-text {
  margin-top: 10px;
  font-size: 12px;
  color: #888;
}

.tip-text > div {
  margin-bottom: 5px;
}

.task-table {
  margin-top: 20px;
}

.empty-tip {
  margin-top: 40px;
  text-align: center;
  color: #999;
  font-size: 16px;
}

.el-tag {
  font-weight: bold;
}
</style>