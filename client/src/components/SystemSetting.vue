<template>
  <div class="page-container" :style="{ width: isMobile ? '90%' : 'auto' }">
    <h2>部门设置</h2>
    
    <!-- Mobile Toolbar -->
    <div v-if="isMobile" class="mobile-toolbar">
      <div class="toolbar-row">
        <el-select v-model="selectedDepartmentId" :placeholder="selectedDepartmentName" @change="handleDepartmentChange" style="width: 100%">
          <el-option
            v-for="dept in departments"
            :key="dept.id"
            :label="dept.department_name"
            :value="dept.id"
          />
        </el-select>
      </div>
      <div class="toolbar-row">
        <el-button type="primary" @click="showAddDialog" style="width: 100%">新增用户</el-button>
      </div>
    </div>

    <!-- Desktop Toolbar -->
    <template v-else>
      <el-form :model="form" label-width="80px" class="center-form">
        <el-form-item label="部门选择" class="form-item-left">
          <el-select v-model="selectedDepartmentId" :placeholder="selectedDepartmentName" @change="handleDepartmentChange" style="max-width: 100px">
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.department_name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>
      </el-form>

      <div class="button-wrapper" style="margin-bottom: 10px">
        <el-button type="primary" @click="showAddDialog" class="add-button">新增用户</el-button>
      </div>
    </template>
    
    <!-- Desktop Table -->
    <el-table v-if="!isMobile" :data="users" border class="custom-table" stripe>
      <el-table-column prop="partner_name" label="姓名" width="180" align="center" header-align="center"/>
      <el-table-column prop="mode" label="模式" width="180" align="center" header-align="center"/>

      <el-table-column prop="department_name" label="部门" width="120" align="center" header-align="center"/>
      <el-table-column prop="position" label="职位" width="180" align="center" header-align="center"/>
      <el-table-column label="操作" width="180" height="55"  align="center" header-align="center">
        <template #default="{ row }">
          <div style="display: flex; justify-content: center; align-items: center; gap: 8px; height: 100%">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <!-- Mobile User List -->
    <div v-else class="mobile-user-list" >
      <el-card v-for="user in users" :key="user.id" class="mobile-user-card" shadow="never" >
        <div class="user-card-header">
          <span class="user-name">{{ user.partner_name }}</span>
          <el-button size="small" type="primary" plain @click="handleEdit(user)">编辑</el-button>
        </div>
        <div class="user-card-content">
          <div class="info-row">
            <span class="label">部门:</span>
            <span class="value">{{ user.department_name }}</span>
          </div>
          <div class="info-row">
            <span class="label">职位:</span>
            <span class="value">{{ user.position || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">模式:</span>
            <span class="value">
              <el-tag size="small" :type="user.mode === '拼搏模式' ? 'danger' : 'success'">{{ user.mode }}</el-tag>
            </span>
          </div>
        </div>
      </el-card>
    </div>

    <el-dialog v-model="editDialogVisible" title="用户信息" :width="isMobile ? '90%' : '50%'" class="dialog-center">
      <el-form :model="editForm" :label-width="isMobile ? '60px' : '80px'" class="dialog-form">
        <el-form-item label="姓名">
          <el-input v-model="editForm.partner_name" />
        </el-form-item>
        <el-form-item label="模式">
          <el-radio-group v-model="editForm.mode">
            <el-radio value="拼搏模式">拼搏模式</el-radio>
            <el-radio value="正常模式">正常模式</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="部门">
          <el-select v-model="editForm.department_id" placeholder="请选择部门" style="width: 100%">
          <el-option
            v-for="dept in departments"
            :key="dept.id"
            :label="dept.department_name"
            :value="dept.id"
          />
        </el-select>
        </el-form-item>
        <el-form-item label="职位">
          <el-input v-model="editForm.position" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="editForm.is_active" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitEdit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import http from '@/utils/http'
import { ElMessage } from 'element-plus'
import {megerOAUserIDS} from '@/utils/dailyPlanAsync'
import { useResponsive } from '@/composables/useResponsive'

const { isMobile } = useResponsive()

const form = ref({
  apiDomain: '',
  systemName: '周日任务校对系统'
})
const departments = ref([])
const selectedDepartmentId = ref(null)
const selectedDepartmentName = ref('请选择部门')
const users = ref([])
const editDialogVisible = ref(false)
const editForm = ref({
  id: null,
  partner_name: '',
  mode: '拼搏模式',
  department_id:2,
  position: '',
  is_active: 1
})

const fetchDepartments = async () => {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_departments')
    let obj = [{'department_name':'全部', 'id': 0,'group_id': 0}]
    departments.value = [...obj,...res.data]
    localStorage.setItem('departments_cache', JSON.stringify( departments.value))
  } catch (error) {
    console.error('获取部门列表失败:', error)
    // 失败时尝试使用本地缓存并补充默认数据
    const cache = localStorage.getItem('departments_cache')
    if(cache) {
      departments.value = JSON.parse(cache)
      selectedDepartmentName.value = departments.value.length > 0 
        ? departments.value.find(d => d.id == 2)?.department_name || '默认部门'
        : '请选择部门'
    } else {
      departments.value = [{id: 2, department_name: '默认部门'}]
      selectedDepartmentName.value = '默认部门'
    }
  }
}

const handleDepartmentChange = (val) => {
  localStorage.setItem('department_id_cache', val)

  if(val == 0) {
    http.get(`UserInfoAPI.php?action=get_all_users&department_id=${val}`)
    .then(res => {
      users.value = res.data
      localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
      megerOAUserIDS(val)
    })
  }else{
    http.get(`UserInfoAPI.php?action=get_users&department_id=${val}`)
    .then(res => {
      users.value = res.data
      localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
      megerOAUserIDS(val)
    })
  }

 
}

const handleEdit = (row) => {
  row.is_active = parseInt(row.is_active)
  editForm.value = { ...row }
  editDialogVisible.value = true
}

const submitEdit = async () => {
  try {
    const apiAction = editForm.value.id ? 'update_user' : 'add_user'
    const params = new URLSearchParams({
      ...editForm.value,
      department_id: editForm.value.department_id,
      mode: editForm.value.mode
    }).toString();
    await http.get(`UserInfoAPI.php?action=${apiAction}&${params}`)
    editDialogVisible.value = false
    handleDepartmentChange(selectedDepartmentId.value)
    ElMessage.success(editForm.value.id ? '修改成功' : '新增成功')
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const showAddDialog = () => {
  editForm.value = {
    partner_name: '',
    position: '',
    is_active: 1,
    department_id: selectedDepartmentId.value
  }
  editDialogVisible.value = true
}

const loadSettings = async () => {
  let departments_cache = localStorage.getItem('departments_cache')
  try {
    if(departments_cache) {
      departments.value = JSON.parse(departments_cache)
    } else {
      await fetchDepartments()
    }
    
    const cachedDeptId = localStorage.getItem('department_id_cache') || 2;
    const cachedDept = departments.value.find(d => d.id == cachedDeptId)
    if (cachedDept) {
      selectedDepartmentId.value = cachedDeptId
      selectedDepartmentName.value = cachedDept.department_name
    }
    
    // selectedDepartmentName.value = departments.value.length > 0 ? departments.value[0].department_name : '请选择部门';
    // handleDepartmentChange()
    handleDepartmentChange(cachedDeptId)
  } catch (error) {
    console.error('缓存解析失败，重新获取部门数据:', error)
    await fetchDepartments()
  }
}
loadSettings()
</script>

<style scoped>
.mobile-toolbar {
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.toolbar-row {
  width: 100%;
}

.mobile-user-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.mobile-user-card {
  border-radius: 8px;
}

.user-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--el-border-color-lighter);
  padding-bottom: 8px;
}

.user-name {
  font-weight: bold;
  font-size: 16px;
  color: var(--el-text-color-primary);
}

.user-card-content {
  margin-bottom: 15px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
}

.info-row .label {
  color: var(--el-text-color-secondary);
}

.info-row .value {
  color: var(--el-text-color-primary);
}

.page-container {
  width: auto;
  margin: 0 auto;
  padding: 20px;
}

@media screen and (max-width: 768px) {
  .page-container {
    padding: 10px;
  }
}

.custom-table {
  box-shadow: 0 2px 12px 0 rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
  align-items: center;
  justify-content: center;
}

:deep(.el-form-item__label) {
  display:flex;
}

:deep(.el-table th) {
  background-color: var(--el-fill-color-light);
  color: var(--el-text-color-regular);
  font-weight: 600;
}

:deep(.el-table__row--striped) {
  background-color: var(--el-fill-color-lighter);
}

:deep(.el-table__row) {
  height: 60px !important;
  vertical-align: middle !important;
}

:deep(.el-table td) {
  vertical-align: middle !important;
}

:deep(.el-table__cell) {
  padding: 12px 0 !important;
}

.el-button {
  margin-top: 0px;
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
</style>