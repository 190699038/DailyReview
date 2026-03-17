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

        <!-- 板块一：跟进事项（表格形式，可编辑） -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">跟进事项</span>
              <div>
                <el-button type="success" link @click="copyFollowUpSimple">简单复制</el-button>
                <el-button type="success" link @click="copyFollowUpText">复制跟进事项</el-button>
                <el-button type="primary" link @click="addFollowUp">+ 添加跟进项</el-button>
              </div>
            </div>
          </template>

          <el-table :data="followUpItems" border size="small" class="followup-table" v-if="followUpItems.length > 0">
            <el-table-column label="序号" width="50" align="center">
              <template #default="{ $index }">{{ $index + 1 }}</template>
            </el-table-column>
            <el-table-column label="跟进事项" min-width="180">
              <template #default="{ row }">
                <el-input v-model="row.title" placeholder="标题" size="small" />
              </template>
            </el-table-column>
            <el-table-column label="状态" width="110" align="center">
              <template #default="{ row }">
                <el-select v-model="row.status" size="small" placeholder="状态">
                  <el-option label="进行中" value="进行中" />
                  <el-option label="已完成" value="已完成" />
                  <el-option label="暂停" value="暂停" />
                  <el-option label="待开始" value="待开始" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="负责人" width="120">
              <template #default="{ row }">
                <el-input v-model="row.responsible_person" placeholder="负责人" size="small" />
              </template>
            </el-table-column>
            <el-table-column label="今日已跟进" width="90" align="center">
              <template #default="{ row }">
                <el-checkbox v-model="row.progress" />
              </template>
            </el-table-column>
            <el-table-column label="下一步" min-width="180">
              <template #default="{ row }">
                <el-input v-model="row.next_step" type="textarea" :autosize="{ minRows: 1, maxRows: 3 }" placeholder="下一步计划" size="small" />
              </template>
            </el-table-column>
            <el-table-column label="异常汇报" min-width="150">
              <template #default="{ row }">
                <el-input v-model="row.exception" type="textarea" :autosize="{ minRows: 1, maxRows: 3 }" placeholder="异常信息" size="small" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="60" align="center">
              <template #default="{ $index }">
                <el-button type="danger" link size="small" @click="removeFollowUp($index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div v-else class="empty-tip">暂无跟进事项，请点击 "添加跟进项" 添加</div>
        </el-card>

        <!-- 板块二：业务部门S级任务（只读，可折叠） -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title clickable" @click="sGoalCollapsed = !sGoalCollapsed">
                {{ sGoalCollapsed ? '▸' : '▾' }} 业务部门S级任务
                <el-tag size="small" type="info" style="margin-left: 8px">只读</el-tag>
              </span>
            </div>
          </template>

          <template v-if="!sGoalCollapsed">
            <template v-if="Object.keys(sGoalGroups).length > 0">
              <div v-for="deptId in deptOrder" :key="deptId" class="dept-block">
                <template v-if="sGoalGroups[deptId]">
                  <div class="dept-header">
                    <span class="dept-name">{{ deptMap[deptId] || deptId }}</span>
                  </div>
                  <div v-if="sGoalStats[deptId]" class="dept-summary">
                    本周已完成 <b>{{ sGoalStats[deptId].completed }}</b> 个，未完成 <b>{{ sGoalStats[deptId].in_progress }}</b> 个，已暂停 <b>{{ sGoalStats[deptId].paused }}</b> 个
                  </div>
                  <div class="goal-list">
                    <div v-for="(goal, gIdx) in sGoalGroups[deptId]" :key="goal.id" class="goal-item">
                      <span class="goal-text">
                        {{ gIdx + 1 }}、【S】{{ goal.weekly_goal }} - {{ codeMap[goal.country] || goal.country }} - {{ Math.round(goal.process * 100) }}% - {{ deptMap[deptId] || deptId }} - {{ goal.executor }} - {{ goal.cross_week == 1 ? '跨周' : '当周完成' }} - {{ formatGoalStatus(goal.status) }}
                      </span>
                    </div>
                  </div>
                </template>
              </div>
            </template>
            <div v-else class="empty-tip">暂无S级任务数据</div>
          </template>
          <div v-else class="collapsed-tip">
            共 {{ totalSGoalCount }} 条S级任务，点击标题展开查看
          </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import http from '@/utils/http'
import { getTodayDate, getMondayDate } from '@/utils/dateUtils'
import { ElMessage, ElMessageBox } from 'element-plus'

// 基础数据
const currentDate = ref(getTodayDate())
const reporter = ref('叶积建')
const reportId = ref(null)
const reportStatus = ref(0)
const showPreview = ref(true)

// 自动保存定时器
let saveTimer = null
let isLoading = false

// S级任务（只读）
const sGoalGroups = ref({})   // { dept_id: [goal, ...] }
const sGoalStats = ref({})    // { dept_id: { total, completed, in_progress, paused } }
const deptMap = ref({})       // { dept_id: dept_name }
const codeMap = ref({})       // { country_code: country_name }
const deptOrder = ['2', '5']  // 展示顺序: 游戏技术组、产品组

// 跟进事项（可编辑）
const followUpItems = ref([])

// S级任务折叠状态
const sGoalCollapsed = ref(true)

// S级任务总数
const totalSGoalCount = computed(() => {
  let count = 0
  for (const deptId of deptOrder) {
    if (sGoalGroups.value[deptId]) count += sGoalGroups.value[deptId].length
  }
  return count
})

// S级任务状态映射（与 WeekTask.vue 保持一致）
const formatGoalStatus = (status) => {
  const map = { 0: '未开始', 1: '进行中', 2: '测试中', 3: '已上线', 4: '已暂停', 5: '已完成' }
  return map[status] ?? '未知状态'
}

// ============ 跟进事项 ============

const addFollowUp = () => {
  followUpItems.value.push({ title: '', status: '进行中', responsible_person: '', progress: false, next_step: '', exception: '' })
}

const removeFollowUp = async (idx) => {
  try {
    await ElMessageBox.confirm('确定删除该跟进事项？', '提示', { type: 'warning' })
    followUpItems.value.splice(idx, 1)
  } catch {}
}

// ============ 工具函数 ============

const getWeekDay = (dateStr) => {
  const year = parseInt(dateStr.substr(0, 4))
  const month = parseInt(dateStr.substr(4, 2)) - 1
  const day = parseInt(dateStr.substr(6, 2))
  const weekNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
  return weekNames[new Date(year, month, day).getDay()]
}

// ============ 数据组装（仅跟进事项） ============

const buildSaveData = (status) => {
  const items = []
  let orderCounter = 1

  followUpItems.value.forEach((item) => {
    if (item.title) {
      items.push({
        category: 'followup',
        section_title: item.title,
        section_order: orderCounter++,
        content: { status: item.status, responsible_person: item.responsible_person, progress: item.progress, next_step: item.next_step, exception: item.exception }
      })
    }
  })

  return {
    reporter: reporter.value,
    report_date: currentDate.value,
    week_day: getWeekDay(currentDate.value),
    status: status,
    items: items
  }
}

// ============ 保存 ============

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

// ============ 加载S级任务 ============

const loadSGoals = async () => {
  try {
    const mondayDate = getMondayDate(currentDate.value)
    const res = await http.get('FollowUpReportAPI.php', {
      params: { action: 'sync_s_goals', monday_date: mondayDate }
    })

    const grouped = res.grouped || {}
    codeMap.value = res.codeMap || {}
    deptMap.value = res.deptMap || {}

    // 按部门分组
    sGoalGroups.value = grouped
    sGoalStats.value = res.stats || {}
  } catch (err) {
    console.error('加载S级任务失败:', err)
  }
}

// ============ 加载汇报 ============

const loadReport = async () => {
  if (!currentDate.value || !reporter.value) return
  isLoading = true

  try {
    // 始终加载S级任务
    await loadSGoals()

    const res = await http.get('FollowUpReportAPI.php', {
      params: { action: 'get', report_date: currentDate.value, reporter: reporter.value }
    })

    const report = res.data
    if (!report) {
      resetForm()
      isLoading = false
      return
    }

    reportId.value = report.id
    reportStatus.value = parseInt(report.status)

    // 仅重建跟进事项
    const items = (report.items || []).filter(i => i.category === 'followup')
    followUpItems.value = items.map(item => {
      const content = typeof item.content === 'string' ? JSON.parse(item.content) : item.content
      return {
        title: item.section_title || '',
        status: content.status || '进行中',
        responsible_person: content.responsible_person || '',
        progress: !!content.progress,
        next_step: content.next_step || '',
        exception: content.exception || ''
      }
    })
  } catch (err) {
    console.error('加载失败:', err)
  }
  isLoading = false
}

// ============ 重置表单 ============

const resetForm = () => {
  reportId.value = null
  reportStatus.value = 0
  followUpItems.value = []
}

// ============ 复制昨日 ============

const copyYesterday = async () => {
  const dateStr = currentDate.value
  const year = parseInt(dateStr.substr(0, 4))
  const month = parseInt(dateStr.substr(4, 2)) - 1
  const day = parseInt(dateStr.substr(6, 2))
  const yesterday = new Date(year, month, day)
  yesterday.setDate(yesterday.getDate() - 1)
  const yesterdayStr = `${yesterday.getFullYear()}${String(yesterday.getMonth() + 1).padStart(2, '0')}${String(yesterday.getDate()).padStart(2, '0')}`

  try {
    await http.post('FollowUpReportAPI.php?action=copy', {
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

// ============ Markdown 生成 ============

const markdownText = computed(() => {
  const dateStr = currentDate.value
  const formattedDate = dateStr.substr(0, 4) + '-' + dateStr.substr(4, 2) + '-' + dateStr.substr(6, 2)
  const weekDay = getWeekDay(dateStr)

  let md = `# 每日跟进汇报 — ${reporter.value}\n`
  md += `> 日期：${formattedDate}（${weekDay}）\n\n`

  // 跟进事项（表格模式）
  md += `## 跟进事项\n\n`
  if (followUpItems.value.length > 0) {
    md += `| 序号 | 跟进事项 | 状态 | 负责人 | 今日已跟进 | 下一步 | 异常汇报 |\n`
    md += `| --- | --- | --- | --- | --- | --- | --- |\n`
    followUpItems.value.forEach((item, idx) => {
      if (item.title) {
        const status = item.status || ''
        const person = item.responsible_person || ''
        const progress = item.progress ? '是' : '否'
        const nextStep = item.next_step || ''
        const exception = item.exception || ''
        md += `| ${idx + 1} | ${item.title} | ${status} | ${person} | ${progress} | ${nextStep} | ${exception} |\n`
      }
    })
  } else {
    md += '- 无\n'
  }
  md += '\n'

  // S级任务
  md += `## 业务部门S级任务\n\n`
  deptOrder.forEach(deptId => {
    const goals = sGoalGroups.value[deptId]
    if (!goals || goals.length === 0) return
    const deptName = deptMap.value[deptId] || deptId
    const stats = sGoalStats.value[deptId]

    md += `### ${deptName}\n`
    if (stats) {
      md += `> 本周已完成 ${stats.completed}个，未完成 ${stats.in_progress}个，已暂停 ${stats.paused}个\n\n`
    }

    goals.forEach((g, idx) => {
      const regionName = codeMap.value[g.country] || g.country
      const crossWeekLabel = g.cross_week == 1 ? '跨周' : '当周完成'
      md += `${idx + 1}、【S】${g.weekly_goal} - ${regionName} - ${Math.round(g.process * 100)}% - ${deptName} - ${g.executor} - ${crossWeekLabel} - ${formatGoalStatus(g.status)}\n`
    })
    md += '\n'
  })

  return md
})

const exportMarkdown = () => {
  copyToClipboard(markdownText.value)
}

const copyMarkdownText = () => {
  copyToClipboard(markdownText.value)
}

const chineseNums = ['一','二','三','四','五','六','七','八','九','十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十']

const copyFollowUpText = () => {
  if (followUpItems.value.length === 0) {
    ElMessage.warning('暂无跟进事项可复制')
    return
  }
  const strip = (s) => (s || '').replace(/[\r\n]+/g, ' ').trim()
  const text = followUpItems.value
    .filter(item => item.title)
    .map((item, idx) => {
      const num = chineseNums[idx] || (idx + 1)
      const parts = [`${num}、${strip(item.title)}`]
      parts.push(item.progress ? '已跟进' : '未跟进')
      if (item.next_step) parts.push(`下一步：${strip(item.next_step)}`)
      if (item.exception) parts.push(`异常：${strip(item.exception)}`)
      parts.push(item.status || '')
      return parts.join(' | ')
    })
    .join('\n')
  copyToClipboard(text)
}

const copyFollowUpSimple = () => {
  if (followUpItems.value.length === 0) {
    ElMessage.warning('暂无跟进事项可复制')
    return
  }
  const strip = (s) => (s || '').replace(/[\r\n]+/g, ' ').trim()
  const text = followUpItems.value
    .filter(item => item.title)
    .map((item, idx) => {
      const num = chineseNums[idx] || (idx + 1)
      return `${num}、${strip(item.title)} | ${item.status || ''}`
    })
    .join('\n')
  copyToClipboard(text)
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('已复制到剪贴板')
  } catch {
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

// ============ 自动保存（仅跟进事项变更时触发） ============

const triggerAutoSave = () => {
  if (reportStatus.value === 1 || isLoading) return
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    if (reporter.value && currentDate.value) {
      handleSave(0)
    }
  }, 3000)
}

watch(
  () => JSON.stringify(followUpItems.value),
  () => {
    triggerAutoSave()
  }
)

// ============ 初始化 ============

onMounted(async () => {
  await loadReport()
})
</script>

<style scoped>
.follow-up-report {
  min-height: 100%;
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
  gap: 12px;
  overflow: visible;
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

.section-title.clickable {
  cursor: pointer;
  user-select: none;
}

.section-title.clickable:hover {
  color: var(--el-color-primary);
}

.collapsed-tip {
  color: var(--el-text-color-secondary);
  font-size: 13px;
  padding: 4px 0;
}

/* S级任务板块 */
.dept-block {
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px dashed var(--el-border-color-lighter);
}

.dept-block:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.dept-header {
  margin-bottom: 4px;
}

.dept-name {
  font-weight: 600;
  font-size: 15px;
  color: var(--el-text-color-primary);
}

.dept-summary {
  font-size: 13px;
  color: var(--el-text-color-secondary);
  margin-bottom: 8px;
}

.dept-summary b {
  color: var(--el-color-primary);
}

.goal-list {
  padding-left: 4px;
}

.goal-item {
  padding: 2px 0;
  line-height: 1.5;
}

.goal-text {
  font-size: 13px;
  color: var(--el-text-color-regular);
}

/* 跟进事项表格 */
.followup-table {
  width: 100%;
}

.followup-table :deep(.el-table__cell) {
  padding: 4px 0;
  vertical-align: top;
}

.followup-table :deep(.el-textarea__inner) {
  padding: 4px 8px;
}

.followup-table :deep(.el-input__inner) {
  padding: 0 8px;
}

.empty-tip {
  color: var(--el-text-color-placeholder);
  font-size: 13px;
  padding: 8px 0;
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
}
</style>
