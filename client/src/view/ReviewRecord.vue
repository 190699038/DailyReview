<template>
  <div class="review-record">
    <!-- 顶部筛选/操作栏 -->
    <div class="toolbar">
      <div class="toolbar-left">
        <el-button type="primary" @click="openDialog('create')">新增记录</el-button>
        <el-date-picker
          v-model="searchParams.start_date"
          type="date"
          placeholder="开始日期"
          format="YYYYMMDD"
          value-format="YYYYMMDD"
          style="width: 160px"
        />
        <el-date-picker
          v-model="searchParams.end_date"
          type="date"
          placeholder="结束日期"
          format="YYYYMMDD"
          value-format="YYYYMMDD"
          style="width: 160px"
        />
        <el-button type="primary" @click="fetchRecords">查询</el-button>
      </div>
      <div class="toolbar-right">
        <el-button @click="copyScreenshot">复制截图</el-button>
        <el-button @click="exportToExcel">导出 Excel</el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <el-table :data="tableData" border stripe style="width: 100%" ref="tableRef">
      <el-table-column label="序号" width="90" align="center">
        <template #default="scope">{{ scope.$index + 1 }}</template>
      </el-table-column>
      <el-table-column label="日期" prop="date" width="150" />
      <el-table-column label="发起人" prop="initiator" width="120" show-overflow-tooltip />
      <el-table-column label="参与人" prop="participants" width="120" show-overflow-tooltip />
      <el-table-column label="目的" prop="purpose" width="300" show-overflow-tooltip />
      <el-table-column label="结论" prop="content" min-width="200">
        <template #default="scope">
          <span style="white-space: pre-line">{{ scope.row.content }}</span>
        </template>
      </el-table-column>
      <el-table-column label="下一步" prop="next_step" width="200" />
      <el-table-column label="有价值" prop="valuable" width="90" align="center">
        <template #default="scope">
          <el-tag
            :type="scope.row.valuable == 1 ? 'success' : scope.row.valuable == 0 ? 'danger' : 'info'"
            size="small"
          >
            {{ scope.row.valuable == 1 ? '有价值' : scope.row.valuable == 0 ? '无价值' : '常规会议' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="价值体现" prop="value_content" width="200" />
      <el-table-column label="操作" width="150" fixed="right">
        <template #default="scope">
          <el-button link type="primary" size="small" @click="handleEdit(scope.row)">编辑</el-button>
          <el-button link type="danger" size="small" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'create' ? '新增记录' : '编辑记录'"
      width="90%"
      destroy-on-close
      style="max-height: 90vh"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="80px">
        <el-form-item label="日期" prop="date">
          <el-date-picker
            v-model="formData.date"
            type="date"
            placeholder="请选择日期"
            format="YYYYMMDD"
            value-format="YYYYMMDD"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结论">
          <QuillEditor
            theme="snow"
            v-model:content="conclusion"
            content-type="html"
            :toolbar="quillToolbar"
            style="min-height: 35vh; width: 100%"
            @ready="handleEditorReady"
            @textChange="handleTextChange"
            @selectionChange="handleSelectionChange"
          />
        </el-form-item>
        <el-form-item label="价值">
          <el-radio-group v-model="formData.valuable">
            <el-radio :value="1">有价值</el-radio>
            <el-radio :value="0">无价值</el-radio>
            <el-radio :value="2">常规会议</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="下一步">
          <el-input v-model="formData.next_step" type="textarea" :rows="3" placeholder="请输入下一步计划" />
        </el-form-item>
        <el-form-item label="价值体现">
          <el-input v-model="formData.value_content" type="textarea" :rows="3" placeholder="请输入价值体现" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import http from '@/utils/http'
import html2canvas from 'html2canvas'
import * as XLSX from 'xlsx'
import { saveAs } from 'file-saver'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

// ========== 常量 ==========
const customColors = ['#000000', '#ff0000', '#ffffff']

const quillToolbar = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote', 'code-block'],
  [{ color: customColors }, { background: customColors }],
  ['clean']
]

const conclusionTemplate = `<p><strong>目的：</strong></p>
<p><strong>发起人：</strong></p>
<p><strong>参会人：</strong></p>
<p><strong>结论：</strong></p>`

// ========== 数据属性 ==========
const tableData = ref([])
const conclusion = ref(conclusionTemplate)
const users = ref([])
const dialogVisible = ref(false)
const dialogType = ref('create')
const tableRef = ref(null)
const formRef = ref(null)
const myQuillEditor = ref(null)

const searchParams = ref({
  start_date: '',
  end_date: '',
})

const formData = ref({
  date: '',
  valuable: 2,
  next_step: '',
  value_content: '',
})

const formRules = {
  date: [{ required: true, message: '请选择日期', trigger: 'change' }],
}

const editorOptions = {
  theme: 'snow',
  modules: {
    toolbar: quillToolbar,
  },
}

// ========== 方法 ==========

// 数据查询
async function fetchRecords() {
  try {
    const params = new URLSearchParams()
    params.append('action', 'list')
    if (searchParams.value.start_date) params.append('start_date', searchParams.value.start_date)
    if (searchParams.value.end_date) params.append('end_date', searchParams.value.end_date)
    const res = await http.get(`ChenYaopuReview.php?${params.toString()}`)
    tableData.value = Array.isArray(res) ? res : []
  } catch (e) {
    console.error('查询失败:', e)
  }
}

async function getUsers() {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_all_users')
    users.value = res.data || []
  } catch (e) {
    console.error('获取用户列表失败:', e)
  }
}

// 表单操作
function openDialog(type) {
  dialogType.value = type
  formData.value = {
    date: formatDate(new Date()),
    valuable: 2,
    next_step: '',
    value_content: '',
  }
  conclusion.value = conclusionTemplate
  dialogVisible.value = true
}

function handleEdit(row) {
  dialogType.value = 'edit'
  formData.value = {
    id: row.id,
    date: row.date,
    valuable: Number(row.valuable),
    next_step: row.next_step || '',
    value_content: row.value_content || '',
  }
  conclusion.value = row.conclusion || ''
  dialogVisible.value = true
}

async function submitForm() {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  // 从富文本HTML中解析字段
  const html = conclusion.value || ''
  const purpose = extractField(html, '目的')
  const initiator = extractField(html, '发起人')
  const participants = extractField(html, '参会人')
  const content = extractField(html, '结论')

  const postData = {
    date: formData.value.date,
    purpose,
    initiator,
    participants,
    conclusion: html,
    content,
    next_step: formData.value.next_step,
    valuable: formData.value.valuable,
    value_content: formData.value.value_content,
  }

  try {
    if (dialogType.value === 'edit') {
      postData.id = formData.value.id
      await http.post('ChenYaopuReview.php?action=update', postData)
      ElMessage.success('更新成功')
    } else {
      await http.post('ChenYaopuReview.php?action=create', postData)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchRecords()
  } catch (e) {
    console.error('提交失败:', e)
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确认删除该记录？', '提示', { type: 'warning' })
    await http.get(`ChenYaopuReview.php?action=delete&id=${row.id}`)
    ElMessage.success('删除成功')
    fetchRecords()
  } catch (e) {
    if (e !== 'cancel') console.error('删除失败:', e)
  }
}

// 富文本编辑器
function handleTextChange() {
  // 尝试从内容中解析日期
  const html = conclusion.value || ''
  const dateMatch = html.match(/(\d{4})[\.\-\/年](\d{1,2})[\.\-\/月](\d{1,2})/)
  if (dateMatch) {
    const parsed = `${dateMatch[1]}${String(dateMatch[2]).padStart(2, '0')}${String(dateMatch[3]).padStart(2, '0')}`
    if (parsed !== formData.value.date) {
      formData.value.date = parsed
    }
  }
}

function handleSelectionChange() {
  // 预留选择变化回调
}

function handleEditorReady(quill) {
  myQuillEditor.value = quill

  // 图片粘贴处理：base64 → 上传
  quill.root.addEventListener('paste', async (e) => {
    const items = e.clipboardData?.items
    if (!items) return

    for (const item of items) {
      if (item.type.startsWith('image/')) {
        e.preventDefault()
        const file = item.getAsFile()
        if (!file) return

        const fd = new FormData()
        fd.append('file', file)
        try {
          const res = await http.post('ChenYaopuReview.php?action=upload_image', fd)
          if (res.success && res.url) {
            insertImageToEditor(res.url)
          }
        } catch (err) {
          console.error('图片上传失败:', err)
          ElMessage.error('图片上传失败')
        }
        return
      }
    }

    // 富文本粘贴：去除格式，只保留纯文本
    const htmlData = e.clipboardData?.getData('text/html')
    if (htmlData) {
      e.preventDefault()
      const text = e.clipboardData.getData('text/plain')
      const range = quill.getSelection(true)
      quill.insertText(range.index, text)
      quill.setSelection(range.index + text.length)
    }
  })
}

function insertImageToEditor(url) {
  const quill = myQuillEditor.value
  if (!quill) return
  const range = quill.getSelection(true)
  quill.insertEmbed(range.index, 'image', url)
  quill.setSelection(range.index + 1)
}

// 导出功能
function exportToExcel() {
  const exportData = tableData.value.map((row, idx) => ({
    '序号': idx + 1,
    '日期': row.date,
    '发起人': row.initiator,
    '参与人': row.participants,
    '目的': row.purpose,
    '结论': row.content,
    '下一步': row.next_step,
    '有价值': row.valuable == 1 ? '有价值' : row.valuable == 0 ? '无价值' : '常规会议',
    '价值体现': row.value_content,
  }))

  const ws = XLSX.utils.json_to_sheet(exportData)

  // 表头样式：蓝底白字加粗
  const headerStyle = {
    font: { bold: true, color: { rgb: 'FFFFFF' } },
    fill: { fgColor: { rgb: '4472C4' } },
    alignment: { wrapText: true, vertical: 'center' },
    border: {
      top: { style: 'thin' },
      bottom: { style: 'thin' },
      left: { style: 'thin' },
      right: { style: 'thin' },
    },
  }

  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let c = range.s.c; c <= range.e.c; c++) {
    const addr = XLSX.utils.encode_cell({ r: 0, c })
    if (ws[addr]) ws[addr].s = headerStyle
  }

  // 交替行色 + 边框
  for (let r = 1; r <= range.e.r; r++) {
    for (let c = range.s.c; c <= range.e.c; c++) {
      const addr = XLSX.utils.encode_cell({ r, c })
      if (ws[addr]) {
        ws[addr].s = {
          fill: r % 2 === 0 ? { fgColor: { rgb: 'D9E2F3' } } : {},
          alignment: { wrapText: true, vertical: 'center' },
          border: {
            top: { style: 'thin' },
            bottom: { style: 'thin' },
            left: { style: 'thin' },
            right: { style: 'thin' },
          },
        }
      }
    }
  }

  // 列宽
  ws['!cols'] = [
    { wch: 6 },   // 序号
    { wch: 12 },  // 日期
    { wch: 10 },  // 发起人
    { wch: 10 },  // 参与人
    { wch: 30 },  // 目的
    { wch: 40 },  // 结论
    { wch: 20 },  // 下一步
    { wch: 10 },  // 有价值
    { wch: 20 },  // 价值体现
  ]

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, '评审记录')
  const now = new Date()
  const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
  const buf = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  saveAs(new Blob([buf], { type: 'application/octet-stream' }), `评审记录_${dateStr}.xlsx`)
  ElMessage.success('导出成功')
}

async function copyScreenshot() {
  try {
    const tableEl = tableRef.value?.$el
    if (!tableEl) return

    // 克隆表格，只保留前5列
    const cloned = tableEl.cloneNode(true)
    const rows = cloned.querySelectorAll('tr')
    rows.forEach(row => {
      const cells = row.querySelectorAll('th, td')
      for (let i = cells.length - 1; i >= 5; i--) {
        cells[i].remove()
      }
    })

    document.body.appendChild(cloned)
    cloned.style.position = 'absolute'
    cloned.style.left = '-9999px'

    const canvas = await html2canvas(cloned, { useCORS: true, scale: 2 })
    document.body.removeChild(cloned)

    canvas.toBlob(async (blob) => {
      if (!blob) {
        ElMessage.error('截图生成失败')
        return
      }
      try {
        await navigator.clipboard.write([
          new ClipboardItem({ 'image/png': blob })
        ])
        ElMessage.success('截图已复制到剪贴板')
      } catch {
        ElMessage.error('复制截图失败')
      }
    }, 'image/png')
  } catch (e) {
    console.error('截图失败:', e)
    ElMessage.error('截图失败')
  }
}

// 辅助函数
function extractField(html, label) {
  // 从富文本HTML中用正则提取 "标签：内容" 格式的字段
  const regex = new RegExp(label + '[：:](.*?)(?=<\\/p>|<p>|<br>|$)', 'is')
  const match = html.match(regex)
  if (match) {
    return match[1].replace(/<[^>]+>/g, '').trim()
  }
  return ''
}

function formatDate(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}${m}${d}`
}

function pad(n) {
  return String(n).padStart(2, '0')
}

// ========== 生命周期 ==========
onMounted(() => {
  getUsers()

  // 设置默认日期范围：过去7天至当天
  const now = new Date()
  const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
  searchParams.value.start_date = formatDate(sevenDaysAgo)
  searchParams.value.end_date = formatDate(now)

  // 初始化表单日期为当天
  formData.value.date = formatDate(now)

  fetchRecords()
})
</script>

<style scoped>
.review-record {
  padding: 16px;
}
.toolbar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 8px;
}
.toolbar-left,
.toolbar-right {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}
:deep(.ql-editor) {
  min-height: 35vh;
}
:deep(.ql-container) {
  font-size: 14px;
}
</style>
