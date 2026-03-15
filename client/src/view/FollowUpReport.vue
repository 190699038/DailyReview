<template>
  <div class="follow-up-report">
    <!-- 顶部工具栏 -->
    <div class="toolbar">
      <div class="toolbar-left">
        <el-date-picker
          v-model="currentDate"
          type="date"
          placeholder="选择日期"
          format="YYYY-MM-DD"
          value-format="YYYYMMDD"
          style="width: 160px"
          @change="loadReport"
        />
        <el-input
          v-model="reporter"
          placeholder="汇报人"
          style="width: 120px"
        />
        <el-button type="primary" @click="loadReport">查询</el-button>
      </div>
      <div class="toolbar-right">
        <el-button @click="copyYesterday">复制昨日</el-button>
        <el-button type="success" @click="handleSave(0)">保存草稿</el-button>
        <el-button type="primary" @click="handleSave(1)">提交</el-button>
        <el-button @click="exportMarkdown">导出MD</el-button>
      </div>
    </div>

    <div class="report-body" :class="{ 'has-preview': showPreview }">
      <!-- 表单区域 -->
      <div class="form-area">
        <!-- 板块一：今日业务事项进展 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">今日业务事项进展</span>
            </div>
          </template>

          <div v-for="(section, idx) in businessSections" :key="idx" class="section-block">
            <div class="section-block-header">
              <span class="block-title">{{ idx + 1 }}. {{ section.section_title }}</span>
              <span v-if="section.responsible_person" class="block-person">（{{ section.responsible_person }}）</span>
            </div>
            <div v-for="(field, fIdx) in section.fields" :key="fIdx" class="field-row">
              <label class="field-label">{{ field.label }}：</label>
              <el-input
                v-if="field.type === 'select'"
                v-model="section.contentData[field.key]"
                style="flex: 1"
              >
                <template #append>
                  <el-select v-model="section.contentData[field.key]" style="width: 100px">
                    <el-option label="是" value="是" />
                    <el-option label="否" value="否" />
                  </el-select>
                </template>
              </el-input>
              <el-input
                v-else
                v-model="section.contentData[field.key]"
                type="textarea"
                :autosize="{ minRows: 1, maxRows: 4 }"
                :placeholder="'请输入' + field.label"
                style="flex: 1"
              />
            </div>
          </div>
        </el-card>

        <!-- 板块二：今日AI事项进展 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">今日AI事项进展</span>
            </div>
          </template>

          <div v-for="(section, idx) in aiSections" :key="idx" class="section-block">
            <div class="section-block-header">
              <span class="block-title">{{ section.section_title }}</span>
            </div>
            <div v-for="(field, fIdx) in section.fields" :key="fIdx" class="field-row">
              <label class="field-label">{{ field.label }}：</label>
              <el-input
                v-model="section.contentData[field.key]"
                type="textarea"
                :autosize="{ minRows: 1, maxRows: 4 }"
                :placeholder="'请输入' + field.label"
                style="flex: 1"
              />
            </div>
          </div>
        </el-card>

        <!-- 板块三：跨部门协调事项 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">跨部门协调事项</span>
            </div>
          </template>
          <el-input
            v-model="coordinationMatters"
            type="textarea"
            :autosize="{ minRows: 2, maxRows: 6 }"
            placeholder="需CEO关注或决策的事项"
          />
        </el-card>

        <!-- 板块四：风险与异常预警 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">风险与异常预警</span>
            </div>
          </template>

          <div v-for="(risk, idx) in riskItems" :key="idx" class="risk-row">
            <el-tag :type="risk.risk_level === 2 ? 'danger' : 'warning'" size="small" class="risk-tag">
              {{ risk.risk_level === 2 ? '🔴 紧急' : '🟡 关注' }}
            </el-tag>
            <el-input
              v-model="risk.description"
              type="textarea"
              :autosize="{ minRows: 1, maxRows: 3 }"
              placeholder="事项描述"
              style="flex: 1"
            />
            <el-select v-model="risk.risk_level" style="width: 100px; margin-left: 8px">
              <el-option :value="2" label="🔴 紧急" />
              <el-option :value="1" label="🟡 关注" />
            </el-select>
            <el-button type="danger" link @click="removeRisk(idx)" style="margin-left: 4px">
              删除
            </el-button>
          </div>
          <el-button type="primary" link @click="addRisk">+ 添加风险项</el-button>
        </el-card>
      </div>

      <!-- Markdown 预览区 -->
      <div v-if="showPreview" class="preview-area">
        <div class="preview-header">
          <span>Markdown 预览</span>
          <el-button link type="primary" @click="copyMarkdownText">复制文本</el-button>
        </div>
        <div class="preview-content">
          <pre class="md-preview">{{ markdownText }}</pre>
        </div>
      </div>
    </div>

    <!-- 底部预览开关 -->
    <div class="bottom-bar">
      <el-switch v-model="showPreview" active-text="预览" inactive-text="" />
      <span v-if="reportStatus === 1" class="status-tag">
        <el-tag type="success" size="small">已提交</el-tag>
      </span>
      <span v-else-if="reportId" class="status-tag">
        <el-tag type="info" size="small">草稿</el-tag>
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import http from '@/utils/http'
import { getTodayDate } from '@/utils/dateUtils'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useResponsive } from '@/composables/useResponsive'

const { isMobile } = useResponsive()

// 基础数据
const currentDate = ref(getTodayDate())
const reporter = ref('叶积建')
const reportId = ref(null)
const reportStatus = ref(0)
const coordinationMatters = ref('')
const showPreview = ref(false)

// 自动保存定时器
let saveTimer = null

// 业务事项板块模板
const businessSections = ref([
  {
    section_title: '支付平台',
    responsible_person: '韩益忠 + 钱贵祥',
    category: 'business',
    section_order: 1,
    contentData: { '进展': '', '风险/卡点': '' },
    fields: [
      { key: '进展', label: '进展', type: 'textarea' },
      { key: '风险/卡点', label: '风险/卡点', type: 'textarea' }
    ]
  },
  {
    section_title: 'S级需求（美国/欧洲/澳洲）',
    responsible_person: '陈苏熙 + 王雪斌',
    category: 'business',
    section_order: 2,
    contentData: { '开发进度': '', '测试进度': '', '是否有B级任务混入': '否' },
    fields: [
      { key: '开发进度', label: '开发进度', type: 'textarea' },
      { key: '测试进度', label: '测试进度', type: 'textarea' },
      { key: '是否有B级任务混入', label: '是否有B级任务混入', type: 'select' }
    ]
  },
  {
    section_title: '投放攻坚',
    responsible_person: '梁浩风 + 张梁',
    category: 'business',
    section_order: 3,
    contentData: { '攻坚进展': '', '投放数据': '' },
    fields: [
      { key: '攻坚进展', label: '攻坚进展', type: 'textarea' },
      { key: '投放数据', label: '投放数据', type: 'textarea' }
    ]
  },
  {
    section_title: '产品核心指标',
    responsible_person: '张梁',
    category: 'business',
    section_order: 4,
    contentData: { '裂变': '', '新增付费率': '', '一付到二付': '' },
    fields: [
      { key: '裂变', label: '裂变', type: 'textarea' },
      { key: '新增付费率', label: '新增付费率', type: 'textarea' },
      { key: '一付到二付', label: '一付到二付', type: 'textarea' }
    ]
  }
])

// AI事项板块模板
const aiSections = ref([
  {
    section_title: 'AI全员使用',
    category: 'ai',
    section_order: 1,
    contentData: { '管理层方案收集': '', '游戏技术组AI演练': '' },
    fields: [
      { key: '管理层方案收集', label: '管理层方案收集', type: 'textarea' },
      { key: '游戏技术组AI演练', label: '游戏技术组AI演练', type: 'textarea' }
    ]
  },
  {
    section_title: 'AI工具库',
    category: 'ai',
    section_order: 2,
    contentData: { '新增工具': '', '使用问题汇总': '' },
    fields: [
      { key: '新增工具', label: '新增工具', type: 'textarea' },
      { key: '使用问题汇总', label: '使用问题汇总', type: 'textarea' }
    ]
  }
])

// 风险预警
const riskItems = ref([])

const addRisk = () => {
  riskItems.value.push({ description: '', risk_level: 1 })
}

const removeRisk = (idx) => {
  riskItems.value.splice(idx, 1)
}

// 获取星期几
const getWeekDay = (dateStr) => {
  const year = parseInt(dateStr.substr(0, 4))
  const month = parseInt(dateStr.substr(4, 2)) - 1
  const day = parseInt(dateStr.substr(6, 2))
  const weekNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
  return weekNames[new Date(year, month, day).getDay()]
}

// 组装保存数据
const buildSaveData = (status) => {
  const items = []

  // 业务事项
  businessSections.value.forEach(s => {
    items.push({
      category: s.category,
      section_title: s.section_title,
      responsible_person: s.responsible_person,
      section_order: s.section_order,
      content: { ...s.contentData },
      risk_level: 0
    })
  })

  // AI事项
  aiSections.value.forEach(s => {
    items.push({
      category: s.category,
      section_title: s.section_title,
      responsible_person: '',
      section_order: s.section_order,
      content: { ...s.contentData },
      risk_level: 0
    })
  })

  // 风险预警
  riskItems.value.forEach((r, idx) => {
    if (r.description) {
      items.push({
        category: 'risk',
        section_title: r.risk_level === 2 ? '紧急事项' : '需关注事项',
        responsible_person: '',
        section_order: idx + 1,
        content: { description: r.description },
        risk_level: r.risk_level
      })
    }
  })

  return {
    reporter: reporter.value,
    report_date: currentDate.value,
    week_day: getWeekDay(currentDate.value),
    coordination_matters: coordinationMatters.value,
    status: status,
    items: items
  }
}

// 保存
const handleSave = async (status) => {
  if (!reporter.value) {
    ElMessage.warning('请输入汇报人')
    return
  }

  try {
    const data = buildSaveData(status)
    const res = await http.post('FollowUpReportAPI.php?action=save', data)
    reportId.value = res.id
    reportStatus.value = status
    ElMessage.success(status === 1 ? '提交成功' : '保存成功')
  } catch (err) {
    console.error('保存失败:', err)
  }
}

// 加载汇报
const loadReport = async () => {
  if (!currentDate.value || !reporter.value) return

  try {
    const res = await http.get('FollowUpReportAPI.php', {
      params: { action: 'get', report_date: currentDate.value, reporter: reporter.value }
    })

    const report = res.data
    if (!report) {
      // 没有记录，重置为空模板
      resetForm()
      return
    }

    reportId.value = report.id
    reportStatus.value = report.status
    coordinationMatters.value = report.coordination_matters || ''

    // 填充明细
    const items = report.items || []

    // 填充业务事项
    businessSections.value.forEach(section => {
      const found = items.find(item => item.category === 'business' && item.section_title === section.section_title)
      if (found) {
        const content = typeof found.content === 'string' ? JSON.parse(found.content) : found.content
        Object.keys(section.contentData).forEach(key => {
          section.contentData[key] = content[key] || ''
        })
      } else {
        Object.keys(section.contentData).forEach(key => {
          section.contentData[key] = ''
        })
      }
    })

    // 填充AI事项
    aiSections.value.forEach(section => {
      const found = items.find(item => item.category === 'ai' && item.section_title === section.section_title)
      if (found) {
        const content = typeof found.content === 'string' ? JSON.parse(found.content) : found.content
        Object.keys(section.contentData).forEach(key => {
          section.contentData[key] = content[key] || ''
        })
      } else {
        Object.keys(section.contentData).forEach(key => {
          section.contentData[key] = ''
        })
      }
    })

    // 填充风险预警
    const risks = items.filter(item => item.category === 'risk')
    riskItems.value = risks.map(r => {
      const content = typeof r.content === 'string' ? JSON.parse(r.content) : r.content
      return {
        description: content.description || '',
        risk_level: parseInt(r.risk_level) || 1
      }
    })

  } catch (err) {
    console.error('加载失败:', err)
  }
}

// 重置表单
const resetForm = () => {
  reportId.value = null
  reportStatus.value = 0
  coordinationMatters.value = ''
  businessSections.value.forEach(s => {
    Object.keys(s.contentData).forEach(k => { s.contentData[k] = '' })
  })
  aiSections.value.forEach(s => {
    Object.keys(s.contentData).forEach(k => { s.contentData[k] = '' })
  })
  riskItems.value = []
}

// 复制昨日
const copyYesterday = async () => {
  const dateStr = currentDate.value
  const year = parseInt(dateStr.substr(0, 4))
  const month = parseInt(dateStr.substr(4, 2)) - 1
  const day = parseInt(dateStr.substr(6, 2))
  const yesterday = new Date(year, month, day)
  yesterday.setDate(yesterday.getDate() - 1)
  const yesterdayStr = `${yesterday.getFullYear()}${String(yesterday.getMonth() + 1).padStart(2, '0')}${String(yesterday.getDate()).padStart(2, '0')}`

  try {
    const res = await http.post('FollowUpReportAPI.php?action=copy', {
      source_date: yesterdayStr,
      target_date: currentDate.value,
      reporter: reporter.value
    })
    ElMessage.success('复制昨日内容成功')
    await loadReport()
  } catch (err) {
    console.error('复制失败:', err)
  }
}

// 生成 Markdown 文本
const markdownText = computed(() => {
  const dateStr = currentDate.value
  const formattedDate = dateStr.substr(0, 4) + '-' + dateStr.substr(4, 2) + '-' + dateStr.substr(6, 2)
  const weekDay = getWeekDay(dateStr)

  let md = `# 每日工作汇报 — ${reporter.value}\n`
  md += `> 日期：${formattedDate}（${weekDay}）\n\n`

  md += `## 今日业务事项进展\n\n`
  businessSections.value.forEach((s, idx) => {
    const person = s.responsible_person ? `（${s.responsible_person}）` : ''
    md += `### ${idx + 1}. ${s.section_title}${person}\n`
    s.fields.forEach(f => {
      md += `- ${f.label}：${s.contentData[f.key] || ''}\n`
    })
    md += '\n'
  })

  md += `## 今日AI事项进展\n\n`
  aiSections.value.forEach(s => {
    md += `### ${s.section_title}\n`
    s.fields.forEach(f => {
      md += `- ${f.label}：${s.contentData[f.key] || ''}\n`
    })
    md += '\n'
  })

  md += `## 三、跨部门协调事项\n`
  md += `- ${coordinationMatters.value || '无'}\n\n`

  md += `## 四、风险与异常预警\n`
  if (riskItems.value.length > 0) {
    riskItems.value.forEach(r => {
      const icon = r.risk_level === 2 ? '🔴' : '🟡'
      md += `- ${icon} ${r.description || ''}\n`
    })
  } else {
    md += '- 无\n'
  }

  return md
})

// 导出 Markdown
const exportMarkdown = () => {
  copyToClipboard(markdownText.value)
}

// 复制 Markdown
const copyMarkdownText = () => {
  copyToClipboard(markdownText.value)
}

// 复制到剪贴板
const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('已复制到剪贴板')
  } catch {
    // 降级方案
    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    ElMessage.success('已复制到剪贴板')
  }
}

// 自动保存（防抖 3 秒）
const triggerAutoSave = () => {
  if (reportStatus.value === 1) return // 已提交不自动保存
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    if (reporter.value && currentDate.value) {
      handleSave(0)
    }
  }, 3000)
}

// 监听所有表单数据变化，触发自动保存
watch(
  () => [
    coordinationMatters.value,
    ...businessSections.value.map(s => JSON.stringify(s.contentData)),
    ...aiSections.value.map(s => JSON.stringify(s.contentData)),
    JSON.stringify(riskItems.value)
  ],
  () => {
    triggerAutoSave()
  },
  { deep: true }
)

onMounted(() => {
  loadReport()
})
</script>

<style scoped>
.follow-up-report {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
  padding: 0 4px;
}

.toolbar-left,
.toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.report-body {
  flex: 1;
  overflow: auto;
  display: flex;
  gap: 16px;
}

.report-body.has-preview {
  display: flex;
}

.form-area {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.preview-area {
  width: 45%;
  min-width: 300px;
  border: 1px solid var(--el-border-color);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-bottom: 1px solid var(--el-border-color);
  font-weight: bold;
  background: var(--el-fill-color-lighter);
}

.preview-content {
  flex: 1;
  overflow: auto;
  padding: 16px;
}

.md-preview {
  white-space: pre-wrap;
  word-break: break-word;
  font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.6;
  color: var(--el-text-color-primary);
  margin: 0;
}

.section-card {
  margin-bottom: 0;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-title {
  font-size: 16px;
  font-weight: bold;
}

.section-block {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px dashed var(--el-border-color-lighter);
}

.section-block:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.section-block-header {
  margin-bottom: 10px;
}

.block-title {
  font-weight: 600;
  font-size: 14px;
  color: var(--el-text-color-primary);
}

.block-person {
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

.field-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;
}

.field-label {
  min-width: 120px;
  line-height: 32px;
  text-align: right;
  color: var(--el-text-color-regular);
  font-size: 14px;
  flex-shrink: 0;
}

.risk-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 10px;
}

.risk-tag {
  margin-top: 6px;
  flex-shrink: 0;
}

.bottom-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 10px 4px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.status-tag {
  margin-left: auto;
}

@media screen and (max-width: 768px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-left,
  .toolbar-right {
    flex-wrap: wrap;
  }

  .report-body.has-preview {
    flex-direction: column;
  }

  .preview-area {
    width: 100%;
    min-width: unset;
    max-height: 400px;
  }

  .field-row {
    flex-direction: column;
    gap: 4px;
  }

  .field-label {
    text-align: left;
    min-width: unset;
    line-height: 24px;
  }

  .risk-row {
    flex-wrap: wrap;
  }
}
</style>
