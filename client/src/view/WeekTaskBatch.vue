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
          <div>格式要求：A列项目组 | B列优先级 | C列需求内容 | D列研发人员 | M列需求名称</div>
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
      <el-table-column prop="project" label="项目组" min-width="120" />
      <el-table-column prop="priority" label="优先级" width="80" align="center">
        <template #default="{ row }">
          <el-tag :type="priorityTagMap[row.priority]">{{ row.priority }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="content" label="需求内容" min-width="180" />
      <el-table-column prop="name" label="需求名称" min-width="180" />
      <el-table-column prop="developer" label="研发人员" width="100" align="center" />
      <el-table-column prop="target" label="目标" min-width="120" />
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

    const parseExcelData = (worksheet) => {
      taskList.value = [];
      let currentProject = '';
      let stopParsing = false;
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      
      // 创建项目内容映射，用于合并相同项目
      const projectContentMap = new Map();

      // 忽略前3行数据，从第4行开始解析
      for (let i = 3; i < jsonData.length; i++) {
        const row = jsonData[i];
        // 检查停止条件
        if (row.some(cell => cell && cell.toString().includes('本周升级/交付'))) {
          break;
        }
        
        console.log('row', row)
        // 处理项目组合并单元格
        if ((row[2] && typeof row[2] === 'string' && row.length == 3) 
        || (row.length > 4 && row[2] && typeof row[2] === 'string' && row[3] ==null  &&  row[4] ==null )) {
          currentProject = row[2];
          console.log('currentProject', currentProject)
          //客服OA 投放OA 奇胜流量管理系统  奇胜二号 评论引流  分包系统   Google 上架 - 目标8月份在线6个谷歌包  苹果包的上架推进 奇胜一号 app引流 TG 引流 攻坚
          // 字符串包含投放  --  value: 'TF', label: '投放' 
          // 字符串包含OA或是分包  --  value: 'OA', label: 'OA系统' 
          // 字符串包含奇胜二号、奇胜一号、评论、引流、TG  --   value: 'QSLL', label: '奇胜-流量'
          // 其它 -- value: 'QSJS', label: '奇胜-技术'  
          let projectGroupBM = currentProject;
          console.log('projectGroupBM', projectGroupBM)
          if (projectGroupBM.includes('投放')) {
            currentProject = '投放';
          }  else if (projectGroupBM.includes('奇胜流量') || projectGroupBM.includes('奇胜二号') || projectGroupBM.includes('奇胜一号') || projectGroupBM.includes('评论') || projectGroupBM.includes('引流') 
          || projectGroupBM.includes('疯传') || projectGroupBM.includes('TG') || projectGroupBM.includes('小米') || projectGroupBM.includes('私域')) {
            currentProject = '奇胜-流量';
          }else if (projectGroupBM.includes('Google') || projectGroupBM.includes('苹果包') || projectGroupBM.includes('上架')) {
            currentProject = '奇胜-技术';
          }
          else if (projectGroupBM.includes('AI 产品')) {
            currentProject = 'AI-古兰经';
          }else if (projectGroupBM.includes('AI')) {
            currentProject = 'AI赋能';
          }
          else if (projectGroupBM.includes('美国1')) {
            currentProject = '美国1';
          } 
          else if (projectGroupBM.includes('美国2')) {
            currentProject = '美国2';
          } 
          else if (projectGroupBM.includes('美国3')) {
            currentProject = '美国3';
          } 
          else if (projectGroupBM.includes('美国4')) {
            currentProject = '美国4';
          }  else if (projectGroupBM.includes('欧洲')) {
            currentProject = '欧洲';
          } else if (projectGroupBM.includes('中东')) {
            currentProject = '中东';
          } 
          else if (projectGroupBM.includes('其它') || projectGroupBM.includes('其他')) {
            currentProject = '美国1';
          }
           else if (projectGroupBM.includes('客服OA') ) {
            currentProject = '客服';
          } 
          else if (projectGroupBM.includes('OA') || projectGroupBM.includes('分包')) {
            currentProject = 'OA系统';
          } 
          else {
            currentProject = '奇胜-技术';
          }
          continue;
        }

        // 确保是有效数据行 (A,B,C,D,M列)
        if (row[1] || row[2] || row[3] || row[12]) {
          const projectGroup =  currentProject || row[0]?.toString().trim();

          const content = row[2]?.toString().trim() || '';
          const priority = normalizePriority(row[1]);
          const name2 = row[12]?.toString().trim() || '';
          //name中删除@字符串
          const name = name2.replace('@', '');
          const target = row[10]?.toString().trim() || '';
          
          // 如果需求内容为空，则跳过此行
          if (!content) {
            continue;
          }
          
          // 创建项目内容的唯一键
          const contentKey = `${projectGroup}-${content}-${name}`;
          
          // 处理多研发人员情况
          const dev = formatTags(row[3]);

          const developers = dev?.toString().split(/[,\/、]+/).map(d => d.trim()).filter(d => d) || [];
      
          if (projectContentMap.has(contentKey)) {
            // 如果已存在相同项目内容，合并研发人员
            const existingItem = projectContentMap.get(contentKey);
            const allDevelopers = [...new Set([...existingItem.developers, ...developers])];
            existingItem.developers = allDevelopers;
            existingItem.developer = allDevelopers.join('/');
          } else {
            // 新项目内容
            const item = {
              id: projectContentMap.size + 1,
              project: projectGroup,
              priority: priority,
              content: content,
              name: name,
              developers: developers,
              developer: developers.join('/'),
              target: target
            };
            projectContentMap.set(contentKey, item);
          }
        }
      }

      // 将合并后的数据转换为数组
      taskList.value = Array.from(projectContentMap.values()).map((item, index) => ({
        ...item,
        id: index + 1
      }));
      
      if (taskList.value.length === 0) {
        noDataText.value = '未解析到有效数据';
      }
    };

const formatTags = (text) => {
  if (!text) return "";

  // 1. 使用正则匹配所有符合 @xxx 格式的片段
  // 正则解释：/@([^\s@]+)/g
  // @       : 匹配 @ 符号
  // ([^\s@]+): 捕获组，匹配 @ 之后直到遇到 "空白字符" 或 "下一个 @" 之前的所有字符
  const matches = text.match(/@([^\s@]+)/g);

  if (!matches) {
    return text; // 如果没有匹配到 @，返回原文本
  }

  // 2. 去掉 @ 符号并用 / 连接
  return matches
    .map(tag => tag.replace('@', '').trim()) // 移除 @ 并去除可能的前后空格
    .join('/'); // 用 / 连接
  }


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
  }

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
  }

  const getCountry = (name) => {
    if (name.includes('投放')) {
      return 'TF';
    } else if (name.includes('OA系统')) {
      return 'OA';
    } else if (name.includes('奇胜-流量')) {
      return 'QSLL';
    }
    else if (name.includes('AI-古兰经')) {
      return 'AIGLJ';
    }
    else if (name.includes('AI赋能')) {
      return 'AIFN';
    }
    else if (name.includes('美国1')) {
      return 'US1';
    }
    else if (name.includes('美国2')) {
      return 'US2';
    }
    else if (name.includes('美国3')) {
      return 'US3';
    }
    else if (name.includes('美国4')) {
      return 'US4';
    }
    else if (name.includes('客服')) {
      return 'KF';
    }
    else if (name.includes('巴西1')) {
      return 'BR1';
    }
    else if (name.includes('巴西2')) {
      return 'BR2';
    }
    else if (name.includes('墨西哥')) {
      return 'MX';
    }
    else if (name.includes('欧洲')) {
      return 'OZ';
    }else if (name.includes('中东')) {
      return 'ZD';
    }

    else {
      return 'QSJS';
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
      return executorId
    }
  }

    const createTask = async() => {
      isUploading.value = true;
      let successCount = 0;
      let errorCount = 0;
      
      try {
        for (const item of taskList.value) 
        // const item = taskList.value[40]
        {
          let params = {
            id: null,
            executor: item.developer,
            weekly_goal: item.content,
            priority: getPriority(item.priority),
            is_new_goal: 0,
            mondayDate: mondayDate.value,
            status: 1,
            country:getCountry(item.project),
            executor_id: getExecutorId(item.developer),
            cross_week:0,
            remark:'batch',
            // pre_finish_date:'',
            department_id:localStorage.getItem('department_id_cache') || 2
          }
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
    }

    // 处理批量上传按钮点击
    const handleBatchUpload = () => {
      if (!mondayDate.value || mondayDate.value === '') {
        showDateDialog.value = true;
      } else {
        createTask();
      }
    }

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
    }

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