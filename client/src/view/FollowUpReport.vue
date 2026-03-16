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
              <el-button type="primary" link @click="addSection('business')">+ 添加子任务</el-button>
            </div>
          </template>

          <div v-for="(section, idx) in businessSections" :key="section._key" class="section-block">
            <div class="section-block-header">
              <span class="block-title">{{ idx + 1 }}. {{ section.section_title }}</span>
              <span v-if="section.responsible_person" class="block-person">（{{ section.responsible_person }}）</span>
              <div class="block-actions">
                <el-button v-if="section.regionSplit" type="primary" link size="small" @click="addRegion(section)">+ 添加地区</el-button>
                <el-button type="danger" link size="small" @click="removeSection('business', idx)">删除</el-button>
              </div>
            </div>

            <!-- 无地区拆分 -->
            <template v-if="!section.regionSplit">
              <div v-for="(field, fIdx) in section.fields" :key="fIdx" class="field-row">
                <label class="field-label">{{ field.label }}：</label>
                <el-select v-if="field.type === 'select'" v-model="section.contentData[field.key]" style="flex: 1" placeholder="请选择">
                  <el-option label="是" value="是" />
                  <el-option label="否" value="否" />
                </el-select>
                <el-input
                  v-else
                  v-model="section.contentData[field.key]"
                  type="textarea"
                  :autosize="{ minRows: 1, maxRows: 4 }"
                  :placeholder="'请输入' + field.label"
                  style="flex: 1"
                />
              </div>
            </template>

            <!-- 地区拆分 -->
            <template v-else>
              <div v-for="(region, rIdx) in section.regions" :key="region.sub_title" class="region-block">
                <div class="region-header">
                  <el-tag type="primary" size="small">📍 {{ region.sub_title }}</el-tag>
                  <el-button type="danger" link size="small" @click="removeRegion(section, rIdx)">删除地区</el-button>
                </div>
                <div v-for="(field, fIdx) in section.fields" :key="fIdx" class="field-row">
                  <label class="field-label">{{ field.label }}：</label>
                  <el-select v-if="field.type === 'select'" v-model="region.contentData[field.key]" style="flex: 1" placeholder="请选择">
                    <el-option label="是" value="是" />
                    <el-option label="否" value="否" />
                  </el-select>
                  <el-input
                    v-else
                    v-model="region.contentData[field.key]"
                    type="textarea"
                    :autosize="{ minRows: 1, maxRows: 4 }"
                    :placeholder="'请输入' + field.label"
                    style="flex: 1"
                  />
                </div>
              </div>
              <div v-if="section.regions.length === 0" class="empty-tip">
                暂无地区，请点击 "添加地区" 添加
              </div>
            </template>
          </div>
        </el-card>

        <!-- 板块二：今日AI事项进展 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">今日AI事项进展</span>
              <el-button type="primary" link @click="addSection('ai')">+ 添加子任务</el-button>
            </div>
          </template>

          <div v-for="(section, idx) in aiSections" :key="section._key" class="section-block">
            <div class="section-block-header">
              <span class="block-title">{{ section.section_title }}</span>
              <div class="block-actions">
                <el-button type="danger" link size="small" @click="removeSection('ai', idx)">删除</el-button>
              </div>
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

        <!-- 板块三：风险与异常预警 -->
        <el-card class="section-card">
          <template #header>
            <div class="card-header">
              <span class="section-title">风险与异常预警</span>
              <el-button type="primary" link @click="addRisk">+ 添加风险项</el-button>
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
            <el-button type="danger" link @click="removeRisk(idx)" style="margin-left: 4px">删除</el-button>
          </div>
          <div v-if="riskItems.length === 0" class="empty-tip">暂无风险项</div>
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

    <!-- 添加子任务弹窗 -->
    <el-dialog v-model="addSectionDialogVisible" title="添加子任务" width="500px" destroy-on-close>
      <el-form label-width="100px">
        <el-form-item label="子任务标题">
          <el-input v-model="newSectionForm.section_title" placeholder="如：新业务线" />
        </el-form-item>
        <el-form-item label="负责人">
          <el-input v-model="newSectionForm.responsible_person" placeholder="可选" />
        </el-form-item>
        <el-form-item label="按地区拆分">
          <el-switch v-model="newSectionForm.regionSplit" />
        </el-form-item>
        <el-form-item label="填写字段">
          <div v-for="(f, idx) in newSectionForm.fields" :key="idx" class="new-field-row">
            <el-input v-model="f.label" placeholder="字段名" style="flex: 1" />
            <el-select v-model="f.type" style="width: 100px; margin-left: 8px">
              <el-option label="文本" value="textarea" />
              <el-option label="选择" value="select" />
            </el-select>
            <el-button type="danger" link @click="newSectionForm.fields.splice(idx, 1)" style="margin-left: 4px">删除</el-button>
          </div>
          <el-button type="primary" link @click="newSectionForm.fields.push({ label: '', type: 'textarea' })">+ 添加字段</el-button>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="addSectionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmAddSection">确定</el-button>
      </template>
    </el-dialog>

    <!-- 添加地区弹窗 -->
    <el-dialog v-model="addRegionDialogVisible" title="添加地区" width="400px" destroy-on-close>
      <el-select v-model="selectedRegions" multiple placeholder="选择地区" style="width: 100%">
        <el-option
          v-for="r in availableRegions"
          :key="r.group_code"
          :label="r.group_name"
          :value="r.group_name"
        />
      </el-select>
      <template #footer>
        <el-button @click="addRegionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmAddRegion">确定</el-button>
      </template>
    </el-dialog>
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
const showPreview = ref(false)
const regionList = ref([])

// 自动保存定时器
let saveTimer = null
// 防止加载数据时触发自动保存
let isLoading = false

// 唯一key生成器
let keyCounter = 0
const genKey = () => ++keyCounter

// ============ 子任务模板定义 ============

const createDefaultBusinessSections = () => [
  {
    _key: genKey(),
    section_title: '支付平台',
    responsible_person: '韩益忠 + 钱贵祥',
    category: 'business',
    regionSplit: false,
    contentData: { '进展': '', '风险/卡点': '' },
    fields: [
      { key: '进展', label: '进展', type: 'textarea' },
      { key: '风险/卡点', label: '风险/卡点', type: 'textarea' }
    ],
    regions: []
  },
  {
    _key: genKey(),
    section_title: 'S级需求',
    responsible_person: '',
    category: 'business',
    regionSplit: true,
    contentData: {},
    fields: [
      { key: '开发进度', label: '开发进度', type: 'textarea' },
      { key: '测试进度', label: '测试进度', type: 'textarea' },
      { key: '是否有B级任务混入', label: '是否有B级任务混入', type: 'select' }
    ],
    regions: []
  },
  {
    _key: genKey(),
    section_title: '投放攻坚',
    responsible_person: '梁浩风 + 张梁',
    category: 'business',
    regionSplit: false,
    contentData: { '攻坚进展': '', '投放数据': '' },
    fields: [
      { key: '攻坚进展', label: '攻坚进展', type: 'textarea' },
      { key: '投放数据', label: '投放数据', type: 'textarea' }
    ],
    regions: []
  },
  {
    _key: genKey(),
    section_title: '产品核心指标',
    responsible_person: '张梁',
    category: 'business',
    regionSplit: false,
    contentData: { '裂变': '', '新增付费率': '', '一付到二付': '' },
    fields: [
      { key: '裂变', label: '裂变', type: 'textarea' },
      { key: '新增付费率', label: '新增付费率', type: 'textarea' },
      { key: '一付到二付', label: '一付到二付', type: 'textarea' }
    ],
    regions: []
  }
]

const createDefaultAiSections = () => [
  {
    _key: genKey(),
    section_title: 'AI全员使用',
    category: 'ai',
    regionSplit: false,
    contentData: { '管理层方案收集': '', '游戏技术组AI演练': '' },
    fields: [
      { key: '管理层方案收集', label: '管理层方案收集', type: 'textarea' },
      { key: '游戏技术组AI演练', label: '游戏技术组AI演练', type: 'textarea' }
    ],
    regions: []
  },
  {
    _key: genKey(),
    section_title: 'AI工具库',
    category: 'ai',
    regionSplit: false,
    contentData: { '新增工具': '', '使用问题汇总': '' },
    fields: [
      { key: '新增工具', label: '新增工具', type: 'textarea' },
      { key: '使用问题汇总', label: '使用问题汇总', type: 'textarea' }
    ],
    regions: []
  }
]

const businessSections = ref(createDefaultBusinessSections())
const aiSections = ref(createDefaultAiSections())
const riskItems = ref([])

// ============ 动态增减子任务 ============

const addSectionDialogVisible = ref(false)
const addSectionTarget = ref('') // 'business' or 'ai'
const newSectionForm = ref({
  section_title: '',
  responsible_person: '',
  regionSplit: false,
  fields: [{ label: '', type: 'textarea' }]
})

const addSection = (category) => {
  addSectionTarget.value = category
  newSectionForm.value = {
    section_title: '',
    responsible_person: '',
    regionSplit: false,
    fields: [{ label: '', type: 'textarea' }]
  }
  addSectionDialogVisible.value = true
}

const confirmAddSection = () => {
  const form = newSectionForm.value
  if (!form.section_title) {
    ElMessage.warning('请输入子任务标题')
    return
  }
  const validFields = form.fields.filter(f => f.label)
  if (validFields.length === 0) {
    ElMessage.warning('请至少添加一个字段')
    return
  }

  const contentData = {}
  validFields.forEach(f => { contentData[f.label] = '' })

  const newItem = {
    _key: genKey(),
    section_title: form.section_title,
    responsible_person: form.responsible_person,
    category: addSectionTarget.value,
    regionSplit: form.regionSplit,
    contentData: contentData,
    fields: validFields.map(f => ({ key: f.label, label: f.label, type: f.type })),
    regions: []
  }

  if (addSectionTarget.value === 'business') {
    businessSections.value.push(newItem)
  } else {
    aiSections.value.push(newItem)
  }
  addSectionDialogVisible.value = false
}

const removeSection = async (category, idx) => {
  try {
    await ElMessageBox.confirm('确定删除该子任务？', '提示', { type: 'warning' })
    if (category === 'business') {
      businessSections.value.splice(idx, 1)
    } else {
      aiSections.value.splice(idx, 1)
    }
  } catch {}
}

// ============ 地区拆分管理 ============

const addRegionDialogVisible = ref(false)
const selectedRegions = ref([])
const currentRegionSection = ref(null)

const availableRegions = computed(() => {
  if (!currentRegionSection.value) return regionList.value
  const existingNames = currentRegionSection.value.regions.map(r => r.sub_title)
  return regionList.value.filter(r => !existingNames.includes(r.group_name))
})

const addRegion = (section) => {
  currentRegionSection.value = section
  selectedRegions.value = []
  addRegionDialogVisible.value = true
}

const confirmAddRegion = () => {
  const section = currentRegionSection.value
  if (!section || selectedRegions.value.length === 0) return

  selectedRegions.value.forEach(regionName => {
    const contentData = {}
    section.fields.forEach(f => { contentData[f.key] = '' })
    section.regions.push({
      sub_title: regionName,
      contentData: contentData
    })
  })
  addRegionDialogVisible.value = false
}

const removeRegion = async (section, rIdx) => {
  try {
    await ElMessageBox.confirm('确定删除该地区？', '提示', { type: 'warning' })
    section.regions.splice(rIdx, 1)
  } catch {}
}

// ============ 风险预警 ============

const addRisk = () => {
  riskItems.value.push({ description: '', risk_level: 1 })
}

const removeRisk = (idx) => {
  riskItems.value.splice(idx, 1)
}

// ============ 工具函数 ============

const getWeekDay = (dateStr) => {
  const year = parseInt(dateStr.substr(0, 4))
  const month = parseInt(dateStr.substr(4, 2)) - 1
  const day = parseInt(dateStr.substr(6, 2))
  const weekNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
  return weekNames[new Date(year, month, day).getDay()]
}

// ============ 数据组装 ============

const buildSaveData = (status) => {
  const items = []
  let orderCounter = 1

  // 业务事项
  businessSections.value.forEach(s => {
    if (s.regionSplit && s.regions.length > 0) {
      s.regions.forEach(r => {
        items.push({
          category: 'business',
          section_title: s.section_title,
          sub_title: r.sub_title,
          responsible_person: s.responsible_person,
          section_order: orderCounter++,
          content: { ...r.contentData },
          risk_level: 0
        })
      })
    } else if (!s.regionSplit) {
      items.push({
        category: 'business',
        section_title: s.section_title,
        sub_title: null,
        responsible_person: s.responsible_person,
        section_order: orderCounter++,
        content: { ...s.contentData },
        risk_level: 0
      })
    }
  })

  // AI事项
  aiSections.value.forEach(s => {
    items.push({
      category: 'ai',
      section_title: s.section_title,
      sub_title: null,
      responsible_person: '',
      section_order: orderCounter++,
      content: { ...s.contentData },
      risk_level: 0
    })
  })

  // 风险预警
  riskItems.value.forEach((r) => {
    if (r.description) {
      items.push({
        category: 'risk',
        section_title: '风险预警',
        sub_title: null,
        responsible_person: '',
        section_order: orderCounter++,
        content: { description: r.description },
        risk_level: r.risk_level
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

// ============ 加载汇报 ============

const loadReport = async () => {
  if (!currentDate.value || !reporter.value) return
  isLoading = true

  try {
    const res = await http.get('FollowUpReportAPI.php', {
      params: { action: 'get', report_date: currentDate.value, reporter: reporter.value }
    })

    const report = res.data
    if (!report) {
      resetForm()
      await syncSGoals()
      isLoading = false
      return
    }

    reportId.value = report.id
    reportStatus.value = parseInt(report.status)

    const items = report.items || []
    rebuildFromItems(items)
  } catch (err) {
    console.error('加载失败:', err)
  }
  isLoading = false
}

// 从明细数据重建表单结构
const rebuildFromItems = (items) => {
  const bizItems = items.filter(i => i.category === 'business')
  const aiItems = items.filter(i => i.category === 'ai')
  const riskItemsData = items.filter(i => i.category === 'risk')

  // 重建业务事项
  const bizGrouped = {}
  bizItems.forEach(item => {
    const title = item.section_title
    if (!bizGrouped[title]) {
      bizGrouped[title] = { items: [], responsible_person: item.responsible_person }
    }
    bizGrouped[title].items.push(item)
  })

  const newBizSections = []
  // 先按默认模板顺序排列
  const defaultBiz = createDefaultBusinessSections()
  const processedTitles = new Set()

  defaultBiz.forEach(defSection => {
    const title = defSection.section_title
    if (bizGrouped[title]) {
      processedTitles.add(title)
      const group = bizGrouped[title]
      const hasSubTitle = group.items.some(i => i.sub_title)

      if (hasSubTitle) {
        // 地区拆分模式
        defSection.regionSplit = true
        defSection.responsible_person = group.responsible_person || defSection.responsible_person
        defSection.regions = group.items.map(i => {
          const content = typeof i.content === 'string' ? JSON.parse(i.content) : i.content
          const contentData = {}
          defSection.fields.forEach(f => { contentData[f.key] = content[f.key] || '' })
          return { sub_title: i.sub_title, contentData }
        })
        newBizSections.push(defSection)
      } else {
        // 普通模式
        const item = group.items[0]
        const content = typeof item.content === 'string' ? JSON.parse(item.content) : item.content
        defSection.responsible_person = item.responsible_person || defSection.responsible_person
        defSection.fields.forEach(f => { defSection.contentData[f.key] = content[f.key] || '' })
        newBizSections.push(defSection)
      }
    } else {
      // 默认模板没有数据，保留空模板
      newBizSections.push(defSection)
    }
  })

  // 处理非默认模板的自定义子任务
  Object.keys(bizGrouped).forEach(title => {
    if (processedTitles.has(title)) return
    const group = bizGrouped[title]
    const hasSubTitle = group.items.some(i => i.sub_title)
    const firstItem = group.items[0]
    const content = typeof firstItem.content === 'string' ? JSON.parse(firstItem.content) : firstItem.content
    const fieldKeys = Object.keys(content)

    const section = {
      _key: genKey(),
      section_title: title,
      responsible_person: firstItem.responsible_person || '',
      category: 'business',
      regionSplit: hasSubTitle,
      contentData: {},
      fields: fieldKeys.map(k => ({ key: k, label: k, type: 'textarea' })),
      regions: []
    }

    if (hasSubTitle) {
      section.regions = group.items.map(i => {
        const c = typeof i.content === 'string' ? JSON.parse(i.content) : i.content
        const cd = {}
        fieldKeys.forEach(k => { cd[k] = c[k] || '' })
        return { sub_title: i.sub_title, contentData: cd }
      })
    } else {
      fieldKeys.forEach(k => { section.contentData[k] = content[k] || '' })
    }
    newBizSections.push(section)
  })

  businessSections.value = newBizSections

  // 重建AI事项
  const newAiSections = []
  const defaultAi = createDefaultAiSections()
  const processedAiTitles = new Set()

  defaultAi.forEach(defSection => {
    const title = defSection.section_title
    const found = aiItems.find(i => i.section_title === title)
    if (found) {
      processedAiTitles.add(title)
      const content = typeof found.content === 'string' ? JSON.parse(found.content) : found.content
      defSection.fields.forEach(f => { defSection.contentData[f.key] = content[f.key] || '' })
    }
    newAiSections.push(defSection)
  })

  aiItems.forEach(item => {
    if (processedAiTitles.has(item.section_title)) return
    const content = typeof item.content === 'string' ? JSON.parse(item.content) : item.content
    const fieldKeys = Object.keys(content)
    newAiSections.push({
      _key: genKey(),
      section_title: item.section_title,
      category: 'ai',
      regionSplit: false,
      contentData: { ...content },
      fields: fieldKeys.map(k => ({ key: k, label: k, type: 'textarea' })),
      regions: []
    })
  })

  aiSections.value = newAiSections

  // 重建风险预警
  riskItems.value = riskItemsData.map(r => {
    const content = typeof r.content === 'string' ? JSON.parse(r.content) : r.content
    return { description: content.description || '', risk_level: parseInt(r.risk_level) || 1 }
  })
}

// ============ 同步S级需求 ============

const syncSGoals = async () => {
  try {
    const mondayDate = getMondayDate(currentDate.value)
    const res = await http.get('FollowUpReportAPI.php', {
      params: { action: 'sync_s_goals', monday_date: mondayDate }
    })

    const grouped = res.grouped || {}
    if (Object.keys(grouped).length === 0) return

    // 找到S级需求的section
    const sSection = businessSections.value.find(s => s.section_title === 'S级需求')
    if (!sSection || !sSection.regionSplit) return

    // 使用后端返回的 codeMap 做 group_code → group_name 映射
    const codeToName = res.codeMap || {}

    const regionNames = Object.keys(grouped)

    regionNames.forEach(countryCode => {
      const regionName = codeToName[countryCode] || countryCode
      // 检查是否已存在
      if (sSection.regions.some(r => r.sub_title === regionName)) return

      const goals = grouped[countryCode]
      const goalTexts = goals.map(g => `${g.weekly_goal}（${g.executor}）`).join('\n')

      const contentData = {}
      sSection.fields.forEach(f => {
        if (f.key === '开发进度') {
          contentData[f.key] = goalTexts
        } else if (f.key === '是否有B级任务混入') {
          contentData[f.key] = '否'
        } else {
          contentData[f.key] = ''
        }
      })

      sSection.regions.push({ sub_title: regionName, contentData })
    })
  } catch (err) {
    console.error('同步S级需求失败:', err)
  }
}

// ============ 重置表单 ============

const resetForm = () => {
  reportId.value = null
  reportStatus.value = 0
  businessSections.value = createDefaultBusinessSections()
  aiSections.value = createDefaultAiSections()
  riskItems.value = []
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

  let md = `# 每日工作汇报 — ${reporter.value}\n`
  md += `> 日期：${formattedDate}（${weekDay}）\n\n`

  // 业务事项
  md += `## 今日业务事项进展\n\n`
  businessSections.value.forEach((s, idx) => {
    const person = s.responsible_person ? `（${s.responsible_person}）` : ''
    md += `### ${idx + 1}. ${s.section_title}${person}\n`

    if (s.regionSplit && s.regions.length > 0) {
      s.regions.forEach(r => {
        md += `\n#### ${r.sub_title}\n`
        s.fields.forEach(f => {
          md += `- ${f.label}：${r.contentData[f.key] || ''}\n`
        })
      })
    } else if (!s.regionSplit) {
      s.fields.forEach(f => {
        md += `- ${f.label}：${s.contentData[f.key] || ''}\n`
      })
    }
    md += '\n'
  })

  // AI事项
  md += `## 今日AI事项进展\n\n`
  aiSections.value.forEach(s => {
    md += `### ${s.section_title}\n`
    s.fields.forEach(f => {
      md += `- ${f.label}：${s.contentData[f.key] || ''}\n`
    })
    md += '\n'
  })

  // 风险预警
  md += `## 风险与异常预警\n`
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

const exportMarkdown = () => {
  copyToClipboard(markdownText.value)
}

const copyMarkdownText = () => {
  copyToClipboard(markdownText.value)
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

// ============ 自动保存 ============

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
  () => [
    ...businessSections.value.map(s => JSON.stringify(s.contentData) + JSON.stringify(s.regions)),
    ...aiSections.value.map(s => JSON.stringify(s.contentData)),
    JSON.stringify(riskItems.value)
  ],
  () => {
    triggerAutoSave()
  },
  { deep: true }
)

// ============ 初始化 ============

onMounted(async () => {
  // 加载地区列表
  try {
    const res = await http.get('FollowUpReportAPI.php', { params: { action: 'get_regions' } })
    regionList.value = res.data || []
  } catch (err) {
    console.error('加载地区列表失败:', err)
  }
  // 加载汇报
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
  gap: 16px;
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
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
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

.block-actions {
  margin-left: auto;
  display: flex;
  gap: 4px;
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

.region-block {
  margin: 8px 0 16px 16px;
  padding: 12px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 6px;
  background: var(--el-fill-color-blank);
}

.region-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
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

.new-field-row {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
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

  .region-block {
    margin-left: 0;
  }
}
</style>
