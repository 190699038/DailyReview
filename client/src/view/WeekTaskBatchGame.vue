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
      <el-table-column prop="content" label="需求内容" min-width="200" />
      <el-table-column prop="developer" label="研发人员" width="120" align="center" />
      <el-table-column prop="testTime" label="提测时间" min-width="120" />
      <el-table-column prop="releaseTime" label="发布时间" min-width="120" />
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
    const extractDateRange = (timeStr) => {
      const dateMap = new Map();
      if (!timeStr) return dateMap;
      
      // 匹配 08-18~08-23 格式
      const match = timeStr.match(/(\d{2})-(\d{2})~(\d{2})-(\d{2})/);
      if (match) {
        const [, startMonth, startDay, endMonth, endDay] = match;
        const currentYear = new Date().getFullYear();
        const startDate = new Date(currentYear, parseInt(startMonth) - 1, parseInt(startDay));
        const endDate = new Date(currentYear, parseInt(endMonth) - 1, parseInt(endDay));
        
        const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
          const monthDay = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
          const weekday = weekdays[d.getDay()];
          dateMap.set(monthDay, weekday);
        }
      }
      
      return dateMap;
    };

    const parseExcelData = (worksheet) => {
      taskList.value = [];
      let currentContent = '';
      let currentDevelopers = [];
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      
      // 从第一行提取时间范围
      let dateMap = new Map();
      let columnDateMap = new Map(); // 列索引到日期的映射
      if (jsonData.length > 0 && jsonData[0].length > 0) {
        const firstRowText = jsonData[0].join(' ');
        dateMap = extractDateRange(firstRowText);
        console.log('日期星期对应关系:', Object.fromEntries(dateMap));
        
        // 创建列索引到日期的映射 (F-K列对应索引5-10)
        const dateEntries = Array.from(dateMap.entries());
        for (let i = 0; i < dateEntries.length && i < 6; i++) {
          const [monthDay, weekday] = dateEntries[i];
          const [month, day] = monthDay.split('-');
          const currentYear = new Date().getFullYear();
          const fullDate = `${currentYear}${month}${day}`;
          columnDateMap.set(4 + i, fullDate); // F列=5, G列=6, ..., K列=10
        }
        console.log('列索引到日期映射:', Object.fromEntries(columnDateMap));
      }
      
      // 从第2行开始解析数据
      for (let i = 2; i < jsonData.length; i++) {
        const row = jsonData[i];
        
        // 检查L列备注是否包含"暂停"关键字
        const remark = row[10]?.toString().trim() || '';
        if (remark.includes('暂停')) {
          continue;
        }
        
        // 处理C列需求内容的合并单元格
        const content = row[1]?.toString().trim() || '';
        if (content && content !== currentContent) {
          // 新的需求内容，重置开发人员列表
          currentContent = content;
          currentDevelopers = [];
        }

        let country = getCountryName(currentContent)
      
        // 读取D列研发人员
        const developer = row[2]?.toString().trim() || '';
        if (developer && !currentDevelopers.includes(developer)) {
          currentDevelopers.push(developer);
        }
        
        // 检查F-K列是否有发布或提测信息（处理合并单元格情况）
        let hasTimeInfo = false;
        let testTime = '';
        let releaseTime = '';
        
        for (let col = 4; col <= 9; col++) { // F-K列对应索引5-10
          const cellValue = row[col]?.toString().trim() || '';
          if (cellValue.includes('提测')) {
            hasTimeInfo = true;
            const columnDate = columnDateMap.get(col);
            testTime = columnDate || cellValue;
          } else if (cellValue.includes('发布')) {
            hasTimeInfo = true;
            const columnDate = columnDateMap.get(col);
            releaseTime = columnDate || cellValue;
          }
        }
        
        // 解析备注列的"周N发布"信息进行双重确认
        let remarkReleaseDay = '';
        if (remark.includes('发布')) {
          const weekMatch = remark.match(/周([一二三四五六日])发布/);
          if (weekMatch) {
            const weekDayMap = {'一': 1, '二': 2, '三': 3, '四': 4, '五': 5, '六': 6, '日': 0};
            const targetWeekDay = weekDayMap[weekMatch[1]];
            // 在dateMap中查找对应星期的日期
            for (const [monthDay, weekday] of dateMap.entries()) {
              const weekDayNum = {'日': 0, '一': 1, '二': 2, '三': 3, '四': 4, '五': 5, '六': 6}[weekday];
              if (weekDayNum === targetWeekDay) {
                const [month, day] = monthDay.split('-');
                const currentYear = new Date().getFullYear();
                remarkReleaseDay = `${currentYear}${month}${day}`;
                break;
              }
            }
          }
        }
        
        // 双重确认发布时间：如果备注中有明确的发布日期，优先使用备注信息
        if (remarkReleaseDay && !releaseTime) {
          releaseTime = remarkReleaseDay;
          hasTimeInfo = true;
        }

        // 处理任务：当有需求内容或有时间信息时才处理这一行
        if (currentContent || hasTimeInfo) {
          const priority = normalizePriority(row[0]); // A列优先级
          
          // 检查是否已存在相同内容的任务
          const existingTaskIndex = taskList.value.findIndex(t => t.content === currentContent);
          
          if (existingTaskIndex >= 0) {
            // 更新现有任务
            const existingTask = taskList.value[existingTaskIndex];
            existingTask.developer = currentDevelopers.join('/');
            
            // 更新时间信息（合并单元格情况下的时间信息）
            if (testTime && !existingTask.testTime) {
              existingTask.testTime = testTime;
            }
            if (releaseTime) {
              existingTask.releaseTime = releaseTime;
            }
            
            // 更新备注信息
            if (remark && !existingTask.remark) {
              existingTask.remark = remark;
            }
          } else {
            // 创建新任务项
            const task = {
              id: taskList.value.length + 1,
              priority: priority,
              content: currentContent,
              developer: currentDevelopers.join('/'),
              testTime: testTime,
              releaseTime: releaseTime,
              remark: remark,
              country: country
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
    }else {
      return 'YXJS'
    }
    };


  const getCountryName = (project) => {
    if(project.includes('美国一')){
      return '美国1'
    }else if(project.includes('美国二')){
      return '美国2'
    }else if(project.includes('美国三')){
      return '美国3'
    }else if(project.includes('巴西一')){
      return '巴西1'
    }else if(project.includes('巴西二')){
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
    }else {
      return '技术需求'
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
        for (const item of taskList.value) {
        // const item = taskList.value[40]
          let developers = getDeveloper(item.developer)
          let params = {
            id: null,
            executor: developers,
            weekly_goal: item.content,
            priority: getPriority(item.priority),
            is_new_goal: 0,
            mondayDate: mondayDate.value,
            status: 1,
            country:getCountry(item.country),
            executor_id: getExecutorId(developers),
            cross_week:item.releaseTime ? 0 : 1,
            remark:item.remark,
            // pre_finish_date:'',
            department_id:localStorage.getItem('department_id_cache') || 2
          }

          if(item.releaseTime){
            params.pre_finish_date = item.releaseTime
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
      if (!mondayDate.value || mondayDate.value === '') {
        showDateDialog.value = true;
      } else {
        createTask();
      }
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
</style>ß