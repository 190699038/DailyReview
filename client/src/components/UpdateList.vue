<template>
  <div class="update-list">
    <!-- 搜索/操作栏 -->
    <div class="toolbar">
      <div class="toolbar-left">
        <el-button type="primary" @click="openDialog('create')">新增记录</el-button>
        <el-date-picker
          v-model="searchParams.start_time"
          type="datetime"
          placeholder="开始时间"
          format="YYYY-MM-DD HH:mm:ss"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 200px"
        />
        <el-date-picker
          v-model="searchParams.end_time"
          type="datetime"
          placeholder="结束时间"
          format="YYYY-MM-DD HH:mm:ss"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 200px"
        />
        <el-select v-model="searchParams.country" placeholder="国家" style="width: 140px" clearable>
          <el-option label="ALL" value="ALL" />
          <el-option
            v-for="item in countryOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
        <el-button type="primary" @click="fetchRecords">查询</el-button>
      </div>
      <div class="toolbar-right">
        <el-button @click="copyYesterdayContent">复制昨日上线内容</el-button>
        <el-button @click="copyScreenshot">复制截图</el-button>
        <el-button @click="exportToExcel">导出到 Excel</el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <el-table :data="tableData" border stripe style="width: 100%" ref="tableRef">
      <el-table-column label="序号" width="90" align="center">
        <template #default="scope">{{ scope.$index + 1 }}</template>
      </el-table-column>
      <el-table-column label="国家" prop="country" width="100" :formatter="formatCountry" />
      <el-table-column label="升级内容" prop="content" min-width="200">
        <template #default="scope">
          <span v-html="formatContentWithLinks(scope.row.content)"></span>
        </template>
      </el-table-column>
      <el-table-column label="影响范围" prop="impact" width="150" />
      <el-table-column label="研发" prop="updater" width="200" show-overflow-tooltip />
      <el-table-column label="更新时间(北京)" prop="update_time" width="200" />
      <el-table-column label="更新时间(当地)" prop="update_time_out" width="200" />
      <el-table-column label="测试" prop="tester" width="200" show-overflow-tooltip />
      <el-table-column label="操作" width="150" fixed="right">
        <template #default="scope">
          <el-button link type="primary" size="small" @click="openDialog('edit', scope.row)">编辑</el-button>
          <el-button link type="danger" size="small" @click="handleDelete(scope.row)">删除</el-button>
          <el-button v-if="scope.row.is_review == 1" link type="success" size="small" @click="review(scope.row)">查看复盘</el-button>
          <el-button link type="warning" size="small" @click="sendMessageToGroup(scope.row)">发送到群</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="700px" destroy-on-close>
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <span>{{ dialogTitle }}</span>
          <el-button size="small" @click="openTextParser" style="margin-right: 30px">解析文本内容</el-button>
        </div>
      </template>
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-form-item label="国家" prop="country">
          <el-select v-model="formData.country" multiple placeholder="请选择国家" style="width: 100%" @change="handleCountryChange">
            <el-option
              v-for="item in countryOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <!-- 类型（隐藏） -->
        <!-- <el-form-item label="类型" prop="type">
          <el-select v-model="formData.type" placeholder="请选择类型">
            <el-option v-for="item in typeOptions" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item> -->
        <el-form-item label="平台" prop="platform">
          <el-select v-model="formData.platform" placeholder="请选择平台">
            <el-option v-for="item in platformOptions" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-form-item label="升级内容" prop="content">
          <el-input v-model="formData.content" type="textarea" :rows="4" placeholder="请输入升级内容" />
        </el-form-item>
        <el-form-item label="影响范围" prop="impact">
          <el-input v-model="formData.impact" placeholder="请输入影响范围" />
        </el-form-item>
        <!-- 复盘（隐藏） -->
        <!-- <el-form-item label="复盘" prop="is_review">
          <el-select v-model="formData.is_review">
            <el-option label="是" :value="1" />
            <el-option label="否" :value="0" />
          </el-select>
        </el-form-item> -->
        <el-form-item label="测试人员" prop="tester">
          <el-select v-model="formData.tester" multiple filterable placeholder="请选择测试人员" style="width: 100%">
            <el-option
              v-for="item in testers"
              :key="item.id"
              :label="item.partner_name"
              :value="item.partner_name"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="更新人" prop="updater">
          <el-select v-model="formData.updater" multiple filterable placeholder="请选择更新人" style="width: 100%">
            <el-option
              v-for="item in develops"
              :key="item.id"
              :label="item.partner_name"
              :value="item.partner_name"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="时间(北京)" prop="update_time">
          <el-date-picker
            v-model="formData.update_time"
            type="datetime"
            placeholder="请选择北京时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="时间(当地)" prop="update_time_out">
          <el-date-picker
            v-model="formData.update_time_out"
            type="datetime"
            placeholder="请选择当地时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="formData.remark" type="textarea" placeholder="请输入备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 复盘弹窗 -->
    <el-dialog v-model="reviewDialogVisible" title="复盘详情" :width="windowWidth > 768 ? '60%' : '95%'" destroy-on-close>
      <div class="review-content">
        <div class="review-original">
          <h4>升级内容</h4>
          <p>{{ reviewInfo.content }}</p>
        </div>
        <el-form label-width="80px" style="margin-top: 16px">
          <el-form-item label="复盘人员">
            <el-select v-model="reviewInfo.review_person" multiple filterable placeholder="请选择复盘人员" style="width: 100%">
              <el-option
                v-for="item in ProductManagers"
                :key="item.id"
                :label="item.partner_name"
                :value="item.partner_name"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="复盘结论">
            <QuillEditor
              theme="snow"
              v-model:content="conclusion"
              content-type="html"
              :toolbar="quillToolbar"
              style="min-height: 200px; width: 100%"
              @ready="handleEditorReady"
            />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <el-button @click="reviewDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitFormReview">保存复盘</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import http from '@/utils/http'
import html2canvas from 'html2canvas'
import * as XLSX from 'xlsx'
import { saveAs } from 'file-saver'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

// ========== 常量 ==========
const countryOptions = ref([])

async function fetchProjectGroups() {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_project_groups')
    const groups = res.data || []
    countryOptions.value = groups.map(g => ({ value: g.group_code, label: g.group_name }))
  } catch (e) {
    console.error('获取项目组配置失败:', e)
  }
}

const typeOptions = ['新功能', '新游戏', 'bug修复', '功能优化']
const platformOptions = ['Android', 'IOS', 'H5', '前端', '后端', '前后端', '数据库']

const quillToolbar = [
  ['bold', 'italic', 'underline'],
  [{ color: [] }, { background: [] }],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['link', 'image'],
  ['clean']
]

// ========== 数据属性 ==========
const tableData = ref([])
const develops = ref([])
const testers = ref([])
const ProductManagers = ref([])
const currentRow = ref({})
const dialogVisible = ref(false)
const dialogType = ref('create')
const reviewDialogVisible = ref(false)
const reviewInfo = ref({})
const conclusion = ref('')
const tableRef = ref(null)
const formRef = ref(null)
const editorInstance = ref(null)
const windowWidth = ref(window.innerWidth)
const windowHeight = ref(window.innerHeight)

const searchParams = ref({
  start_time: '',
  end_time: '',
  country: 'ALL',
})

const formData = ref({
  country: [],
  type: '',
  platform: '',
  content: '',
  impact: '',
  is_review: 0,
  tester: [],
  updater: [],
  update_time: '',
  update_time_out: '',
  remark: '',
})

// ========== 表单校验 ==========
const formRules = {
  country: [{ required: true, message: '请选择国家', trigger: 'change' }],
  update_time: [{ required: true, message: '请选择北京时间', trigger: 'change' }],
  update_time_out: [{ required: true, message: '请选择当地时间', trigger: 'change' }],
  content: [{ required: true, type: 'string', min: 1, message: '请输入升级内容', trigger: 'blur' }],
  platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
  tester: [{ required: true, type: 'array', min: 1, message: '请选择测试人员', trigger: 'change' }],
  updater: [{ required: true, type: 'array', min: 1, message: '请选择更新人', trigger: 'change' }],
}

// ========== 计算属性 ==========
const dialogTitle = computed(() => dialogType.value === 'create' ? '新增记录' : '编辑记录')

// ========== 方法 ==========

// 5.1 数据查询
async function fetchRecords() {
  try {
    const params = new URLSearchParams()
    params.append('action', 'list')
    if (searchParams.value.start_time) params.append('start_time', searchParams.value.start_time)
    if (searchParams.value.end_time) params.append('end_time', searchParams.value.end_time)
    if (searchParams.value.country) params.append('country', searchParams.value.country)
    const res = await http.get(`UpgradeRecordAPI.php?${params.toString()}`)
    tableData.value = Array.isArray(res) ? res : []
  } catch (e) {
    console.error('查询失败:', e)
  }
}

async function fetchUserList() {
  try {
    const res = await http.get('UserInfoAPI.php?action=get_all_users')
    const users = res.data || []
    testers.value = users.filter(u => u.position === '测试')
    develops.value = users.filter(u => u.position === '研发' || u.position === '开发')
    ProductManagers.value = users.filter(u => u.position === '产品经理' || u.position === '产品')
  } catch (e) {
    console.error('获取用户列表失败:', e)
  }
}

async function fetchReviewInfo(id) {
  try {
    const res = await http.get(`UpgradeRecordAPI.php?action=get_review&id=${id}`)
    return res || {}
  } catch (e) {
    console.error('获取复盘信息失败:', e)
    return {}
  }
}

// 5.2 表单操作
function openDialog(type, row) {
  dialogType.value = type
  if (type === 'edit' && row) {
    currentRow.value = row
    formData.value = {
      ...row,
      country: row.country ? row.country.split(',').map(s => s.trim()) : [],
      tester: row.tester ? row.tester.split(',').map(s => s.trim()) : [],
      updater: row.updater ? row.updater.split(',').map(s => s.trim()) : [],
    }
  } else {
    currentRow.value = {}
    formData.value = {
      country: [],
      type: '',
      platform: '',
      content: '',
      impact: '',
      is_review: 0,
      tester: [],
      updater: [],
      update_time: '',
      update_time_out: '',
      remark: '',
    }
  }
  dialogVisible.value = true
}

async function submitForm() {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  try {
    const countries = formData.value.country
    const baseData = {
      content: formData.value.content,
      update_time: formData.value.update_time,
      update_time_out: formData.value.update_time_out,
      updater: formData.value.updater.join(','),
      tester: formData.value.tester.join(','),
      type: formData.value.type,
      platform: formData.value.platform,
      impact: formData.value.impact,
      remark: formData.value.remark,
    }

    if (dialogType.value === 'edit') {
      // 编辑模式只更新一条
      await http.post('UpgradeRecordAPI.php?action=update', {
        ...baseData,
        id: currentRow.value.id,
        country: countries.join(','),
      })
      ElMessage.success('更新成功')
    } else {
      // 新增模式：每个国家单独创建一条
      for (const country of countries) {
        await http.post('UpgradeRecordAPI.php?action=create', {
          ...baseData,
          country,
        })
      }
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
    await http.get(`UpgradeRecordAPI.php?action=delete&id=${row.id}`)
    ElMessage.success('删除成功')
    fetchRecords()
  } catch (e) {
    if (e !== 'cancel') console.error('删除失败:', e)
  }
}

// 5.3 复盘功能
async function review(row) {
  currentRow.value = row
  const info = await fetchReviewInfo(row.id)
  reviewInfo.value = {
    ...info,
    review_person: info.review_person ? info.review_person.split(',').map(s => s.trim()) : [],
  }
  conclusion.value = info.review_conclusion || ''
  reviewDialogVisible.value = true
}

async function submitFormReview() {
  try {
    await http.post('UpgradeRecordAPI.php?action=save_review', {
      id: currentRow.value.id,
      review_conclusion: conclusion.value,
      review_person: Array.isArray(reviewInfo.value.review_person) 
        ? reviewInfo.value.review_person.join(',') 
        : reviewInfo.value.review_person,
    })
    ElMessage.success('复盘保存成功')
    reviewDialogVisible.value = false
    fetchRecords()
  } catch (e) {
    console.error('复盘保存失败:', e)
  }
}

// 5.4 文本解析
function openTextParser() {
  ElMessageBox.prompt('请粘贴格式化文本', '解析文本内容', {
    inputType: 'textarea',
    inputPlaceholder: '例如：【地区】巴西\n【更新内容】修复xxx问题\n【上线时间】2026-03-12 10:00:00',
    confirmButtonText: '解析',
    cancelButtonText: '取消',
  }).then(({ value }) => {
    if (value) parseContent(value)
  }).catch(() => {})
}

function parseContent(text) {
  // 解析地区（匹配不到默认巴西1）
  const countryMatch = text.match(/【地区】[：:\s]*(.+?)(?:\n|$)/)
  if (countryMatch) {
    const countryText = countryMatch[1].trim()
    const matched = countryOptions.value.filter(opt =>
      countryText.includes(opt.label) || countryText.includes(opt.value)
    )
    formData.value.country = matched.length > 0 ? matched.map(m => m.value) : ['BR1']
  } else {
    formData.value.country = ['BR1']
  }

  // 解析更新内容
  const contentMatch = text.match(/【更新内容】[：:\s]*(.+?)(?=ꔷ\s*【|$)/s)
  if (contentMatch) {
    formData.value.content = contentMatch[1].trim()
  }

  // 解析上线时间（北京/国内）
  const timeStr = getConvertUpdateTime(text)
  if (timeStr) {
    formData.value.update_time = timeStr
  }

  // 解析当地时间
  const localTimeStr = getConvertLocalTime(text)
  if (localTimeStr) {
    formData.value.update_time_out = localTimeStr
  }

  // 解析影响范围
  const impactMatch = text.match(/【影响范围】[：:\s]*(.+?)(?:\n|$)/)
  if (impactMatch) {
    formData.value.impact = impactMatch[1].trim()
  }

  // 解析平台（兼容【产品】）
  const platformMatch = text.match(/【(?:平台|产品)】[：:\s]*(.+?)(?:\n|$)/)
  if (platformMatch) {
    formData.value.platform = platformMatch[1].trim()
  }

  // 解析开发人员（模糊匹配）
  const devMatch = text.match(/【(?:开发|研发)(?:人员)?】[：:\s]*(.+?)(?:\n|$)/)
  if (devMatch) {
    const names = devMatch[1].trim().split(/[,，、\s]+/).filter(n => n)
    const matched = develops.value.filter(d => names.some(n => d.partner_name.includes(n) || n.includes(d.partner_name)))
    if (matched.length > 0) {
      formData.value.updater = matched.map(m => m.partner_name)
    }
  }

  // 解析测试人员（模糊匹配）
  const testerMatch = text.match(/【测试(?:人员)?】[：:\s]*(.+?)(?:\n|$)/)
  if (testerMatch) {
    const names = testerMatch[1].trim().split(/[,，、\s]+/).filter(n => n)
    const matched = testers.value.filter(t => names.some(n => t.partner_name.includes(n) || n.includes(t.partner_name)))
    if (matched.length > 0) {
      formData.value.tester = matched.map(m => m.partner_name)
    }
  }

  // 解析备注（兼容文档地址/禅道地址）
  const remarkMatch = text.match(/【(?:备注|文档地址[\/／、]*禅道地址|文档地址|禅道地址)】[：:\s]*(.+?)(?=ꔷ\s*【|$)/s)
  if (remarkMatch) {
    formData.value.remark = remarkMatch[1].trim()
  }

  ElMessage.success('文本解析完成')
}

function getConvertUpdateTime(text) {
  const timeRegex = /(\d{4}[-/]\d{1,2}[-/]\d{1,2}\s+\d{1,2}:\d{2}(?::\d{2})?)/
  const patterns = [
    /【上线时间[（(](?:国内|北京)[)）]】[：:\s]*/,
    /【(?:上线|更新)时间】[：:\s]*/,
    /【时间[（(](?:国内|北京)[)）]】[：:\s]*/,
    /【时间】[：:\s]*/,
  ]
  for (const pattern of patterns) {
    const match = text.match(new RegExp(pattern.source + timeRegex.source))
    if (match) return match[1].trim()
  }
  return null
}

function getConvertLocalTime(text) {
  const timeRegex = /(\d{4}[-/]\d{1,2}[-/]\d{1,2}\s+\d{1,2}:\d{2}(?::\d{2})?)/
  const patterns = [
    /【上线时间[（(]当地[)）]】[：:\s]*/,
    /【当地时间】[：:\s]*/,
    /【时间[（(]当地[)）]】[：:\s]*/,
  ]
  for (const pattern of patterns) {
    const match = text.match(new RegExp(pattern.source + timeRegex.source))
    if (match) return match[1].trim()
  }
  return null
}

// 5.5 消息与导出
async function sendMessageToGroup(row) {
  try {
    await ElMessageBox.confirm('确认将此记录发送到钉钉群？', '提示')
    await http.post('UpgradeRecordAPI.php?action=send_dingding', {
      id: row.id,
    })
    ElMessage.success('发送成功')
  } catch (e) {
    if (e !== 'cancel') console.error('发送失败:', e)
  }
}

function copyYesterdayContent() {
  const now = new Date()
  const twoDaysAgo = new Date(now.getTime() - 2 * 24 * 60 * 60 * 1000)
  const recent = tableData.value.filter(row => {
    if (!row.update_time) return false
    const t = new Date(row.update_time)
    return t >= twoDaysAgo && t <= now
  })

  if (recent.length === 0) {
    ElMessage.warning('近2天没有记录')
    return
  }

  const text = recent.map((row, idx) => {
    return `${idx + 1}. 【${formatCountryLabel(row.country)}】${row.content}（${row.update_time}）`
  }).join('\n')

  navigator.clipboard.writeText(text).then(() => {
    ElMessage.success('复制成功')
  }).catch(() => {
    ElMessage.error('复制失败')
  })
}

async function copyScreenshot() {
  try {
    const tableEl = tableRef.value?.$el
    if (!tableEl) return

    const canvas = await html2canvas(tableEl, {
      useCORS: true,
      scale: 2,
    })

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

function exportToExcel() {
  const exportData = tableData.value.map((row, idx) => ({
    '序号': idx + 1,
    '国家': formatCountryLabel(row.country),
    '升级内容': row.content,
    '更新时间': row.update_time,
    '影响范围': row.impact,
  }))

  const ws = XLSX.utils.json_to_sheet(exportData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, '升级记录')
  const buf = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  saveAs(new Blob([buf], { type: 'application/octet-stream' }), '升级记录.xlsx')
  ElMessage.success('导出成功')
}

// 5.6 格式化 & 辅助
function formatContentWithLinks(content) {
  if (!content) return ''
  // 对内容进行HTML转义防止XSS
  const escaped = content
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
  // 先匹配 URL|描述文字| 模式（竖线分隔）
  let result = escaped.replace(/(https?:\/\/[^\s|]+)\|([^|]+)\|/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>')
  // 匹配 URL（描述文字）模式（中文括号分隔）
  result = result.replace(/(https?:\/\/[^\s）)]+)（([^）]+)）/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>')
  // 再匹配剩余的纯 URL（不在 a 标签内的）
  result = result.replace(/(?<!href="|">)(https?:\/\/[^\s<]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
  // 换行符转为 <br>
  result = result.replace(/\n/g, '<br>')
  return result
}

function formatCountry(row) {
  return formatCountryLabel(row.country)
}

function formatCountryLabel(value) {
  if (!value) return ''
  const opt = countryOptions.value.find(o => o.value === value)
  return opt ? opt.label : value
}

function formatReview(row) {
  return row.is_review == 1 ? '复盘' : '不复盘'
}

function handleCountryChange(selected) {
  // 选择特定组合国家时自动填充影响范围
  if (selected.includes('latin_all')) {
    formData.value.impact = '拉美全部国家'
  } else if (selected.includes('global')) {
    formData.value.impact = '全球'
  } else {
    const labels = selected.map(v => {
      const opt = countryOptions.value.find(o => o.value === v)
      return opt ? opt.label : v
    })
    formData.value.impact = labels.join('、')
  }
}

function handleEditorReady(quill) {
  editorInstance.value = quill

  // 粘贴处理器
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
          const res = await http.post('UpgradeRecordAPI.php?action=upload_image', fd)
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
  })
}

function insertImageToEditor(url) {
  const quill = editorInstance.value
  if (!quill) return
  const range = quill.getSelection(true)
  quill.insertEmbed(range.index, 'image', url)
  quill.setSelection(range.index + 1)
}

function handleResize() {
  windowWidth.value = window.innerWidth
  windowHeight.value = window.innerHeight
}

// ========== 生命周期 ==========
onMounted(() => {
  // 设置默认查询时间范围（过去7天至今天 23:59:59）
  const now = new Date()
  const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)

  const pad = (n) => String(n).padStart(2, '0')
  searchParams.value.start_time = `${sevenDaysAgo.getFullYear()}-${pad(sevenDaysAgo.getMonth() + 1)}-${pad(sevenDaysAgo.getDate())} 00:00:00`
  searchParams.value.end_time = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} 23:59:59`
  searchParams.value.country = 'ALL'

  fetchProjectGroups()
  fetchUserList()
  fetchRecords()

  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.update-list {
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
.review-content {
  max-height: 70vh;
  overflow-y: auto;
}
.review-original {
  background: var(--el-fill-color-light);
  padding: 12px;
  border-radius: 4px;
}
.review-original h4 {
  margin: 0 0 8px;
}
:deep(.ql-editor) {
  min-height: 200px;
}
:deep(.ql-container) {
  font-size: 14px;
}
</style>
