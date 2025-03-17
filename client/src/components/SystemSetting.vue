<template>
  <div class="page-container">
    <h2>部门设置</h2>
    <el-form :model="form" label-width="120px" class="center-form">
      <el-form-item label="部门选择" class="form-item-left">
        <el-select v-model="selectedDepartmentId" placeholder="请选择部门" @change="handleDepartmentChange" style="max-width: 300px">
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
    
    <el-table :data="users" border class="custom-table" stripe>
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

    <el-dialog v-model="editDialogVisible" title="用户信息" class="dialog-center">
      <el-form :model="editForm" label-width="80px" class="dialog-form">
        <el-form-item label="姓名" label-width="80px">
          <el-input v-model="editForm.partner_name" />
        </el-form-item>
        <el-form-item label="模式" label-width="80px">
          <el-radio-group v-model="editForm.mode">
            <el-radio value="拼搏模式">拼搏模式</el-radio>
            <el-radio value="正常模式">正常模式</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="部门" label-width="80px">
          <el-select v-model="editForm.department_id" placeholder="请选择部门">
          <el-option
            v-for="dept in departments"
            :key="dept.id"
            :label="dept.department_name"
            :value="dept.id"
          />
        </el-select>
        </el-form-item>
        <el-form-item label="职位" label-width="80px">
          <el-input v-model="editForm.position" />
        </el-form-item>
        <el-form-item label="状态" label-width="80px">
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

const form = ref({
  apiDomain: '',
  systemName: '周日任务校对系统'
})
const departments = ref([])
const selectedDepartmentId = ref(localStorage.getItem('department_id_cache') || 2)
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
    departments.value = res.data
    localStorage.setItem('departments_cache', JSON.stringify(res.data))
  } catch (error) {
    console.error('获取部门列表失败:', error)
    // 失败时尝试使用本地缓存并补充默认数据
    const cache = localStorage.getItem('departments_cache')
    if(cache) {
      departments.value = JSON.parse(cache)
    } else {
      departments.value = [{id: 2, department_name: '默认部门'}]
    }
  }
}

const handleDepartmentChange = (val) => {
  localStorage.setItem('department_id_cache', val)
  http.get(`UserInfoAPI.php?action=get_users&department_id=${val}`)
    .then(res => {
      users.value = res.data
      localStorage.setItem('departments_user_cache', JSON.stringify(res.data))
    })
}

const handleEdit = (row) => {
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
  } catch (error) {
    console.error('缓存解析失败，重新获取部门数据:', error)
    await fetchDepartments()
  }
  const dpid = localStorage.getItem('department_id_cache') || 2;
  handleDepartmentChange(dpid)

}
loadSettings()
</script>

<style scoped>
.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
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
  width: 70px !important;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  color: #606266;
  font-weight: 600;
}

:deep(.el-table__row--striped) {
  background-color: #fafafa;
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
</style>