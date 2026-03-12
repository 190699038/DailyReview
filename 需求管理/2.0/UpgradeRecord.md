# UpgradeRecord 组件功能文档

## 1. 架构概览

`UpgradeRecord.vue` 是一个薄包装组件，实际业务逻辑全部位于子组件 `UpdateList.vue` 中。

- **路由路径**：`/upgrade-record`（`MainLayout` 的子路由，同时也是默认重定向目标）
- **原计划设计**：使用 `el-tabs` 切换「更新记录」和「更新记录数据统计」两个页签（`UpdateList` + `UpdateStatic`），但目前 Tab 已被注释掉，仅直接渲染 `UpdateList`

---

## 2. 页面 UI 结构

### 2.1 搜索/操作栏

| UI 元素 | 说明 |
|---------|------|
| 新增记录按钮 | 打开新增弹窗 |
| 开始时间 / 结束时间 | `el-date-picker`，格式 `YYYY-MM-DD HH:mm:ss` |
| 国家选择 | `el-select`，包含 "ALL" 选项 |
| 查询按钮 | 触发 `fetchRecords()` |
| 复制昨日上线内容 | 筛选近2天记录，拼接格式化文本复制到剪贴板 |
| 复制截图 | 使用 `html2canvas` 截取表格前8列生成 PNG 图片复制到剪贴板 |
| 导出到 Excel | 使用 xlsx + file-saver 导出为 `.xlsx` 文件 |

### 2.2 数据表格

| 列名 | 字段 (`prop`) | 宽度 | 特殊处理 |
|------|--------------|------|----------|
| 序号 | — | 90px | `scope.$index + 1`（自动行号） |
| 国家 | `country` | 100px | `formatCountry` 格式化为中文标签 |
| 升级内容 | `content` | 自适应 | `formatContentWithLinks()` 自动识别 URL 并渲染为可点击链接 |
| 影响范围 | `impact` | 150px | — |
| 研发 | `updater` | 200px | 超出省略 tooltip |
| 更新时间(北京) | `update_time` | 200px | — |
| 更新时间(当地) | `update_time_out` | 200px | — |
| 测试 | `tester` | 200px | 超出省略 tooltip |
| 操作 | — | 150px | 编辑 / 删除 / 查看复盘（`is_review==1` 时显示）/ 发送到群 |

**已注释掉的列**：类型 `type`、平台 `platform`、复盘 `is_review`、备注 `remark`

### 2.3 新增/编辑弹窗

| 表单字段 | 组件类型 | 说明 |
|---------|---------|------|
| 国家 | `el-select` 多选 | 选中后自动匹配影响范围 |
| 类型 | `el-select`（隐藏） | 新功能/新游戏/bug修复/功能优化 |
| 平台 | `el-select` | Android/IOS/前端/后端/前后端/数据库 |
| 升级内容 | `el-input textarea` | 4行文本输入 |
| 影响范围 | `el-input` | — |
| 复盘 | `el-select`（隐藏） | 是/否 |
| 测试人员 | `el-select` 多选可搜索 | 从用户列表过滤 |
| 更新人 | `el-select` 多选可搜索 | 从用户列表过滤 |
| 时间(北京) | `el-date-picker datetime` | — |
| 时间(当地) | `el-date-picker datetime` | — |
| 备注 | `el-input textarea` | — |

弹窗头部包含 **「解析文本内容」** 按钮，支持从格式化文本自动填充表单。

### 2.4 复盘弹窗

- 显示原始升级内容
- 选择复盘人员（产品经理列表）
- **QuillEditor** 富文本编辑器（支持加粗、斜体、颜色、列表、链接等）
- 支持图片粘贴自动上传至服务端

---

## 3. 数据属性

| 属性 | 类型 | 说明 |
|------|------|------|
| `tableData` | `ref([])` | 表格数据列表 |
| `develops` | `ref([])` | 研发人员列表 |
| `testers` | `ref([])` | 测试人员列表 |
| `ProductManagers` | `ref([])` | 产品经理列表 |
| `currentRow` | `ref({})` | 当前操作的行数据 |
| `dialogVisible` | `ref(false)` | 新增/编辑弹窗显示状态 |
| `dialogType` | `ref('create')` | 弹窗模式：`create` / `edit` |
| `reviewDialogVisible` | `ref(false)` | 复盘弹窗显示状态 |
| `reviewInfo` | `ref({})` | 复盘详情信息 |
| `conclusion` | `ref('')` | 复盘结论 HTML 内容 |
| `searchParams` | `ref({})` | 查询参数（start_time, end_time, country） |
| `formData` | `ref({})` | 表单数据 |
| `windowWidth / windowHeight` | `ref(...)` | 窗口尺寸（用于复盘弹窗自适应大小） |
| `editorInstance` | `ref(null)` | Quill 编辑器实例引用 |
| `countryOptions` | 常量 | 21个国家选项，含组合国家 |
| `typeOptions` | 常量 | 新功能/新游戏/bug修复/功能优化 |
| `platformOptions` | 常量 | Android/IOS/前端/后端/前后端/数据库 |

---

## 4. 计算属性

| 名称 | 逻辑 |
|------|------|
| `dialogTitle` | 根据 `dialogType` 返回 `'新增记录'` 或 `'编辑记录'` |

---

## 5. 方法清单

### 5.1 数据查询

| 方法 | 功能 |
|------|------|
| `fetchRecords()` | 根据搜索条件查询升级记录列表 |
| `fetchUserList()` | 获取用户列表，按职位分类为测试/研发/产品经理 |
| `fetchReviewInfo()` | 获取指定记录的复盘详情 |

### 5.2 表单操作

| 方法 | 功能 |
|------|------|
| `openDialog(type, row)` | 打开新增/编辑弹窗；编辑时自动解析 country、tester、updater 字符串为数组 |
| `submitForm()` | 提交表单，**遍历选中的每个国家单独创建一条记录** |
| `handleDelete(row)` | 确认后删除指定记录 |

### 5.3 复盘功能

| 方法 | 功能 |
|------|------|
| `review(row)` | 打开复盘弹窗并加载复盘数据 |
| `submitFormReview()` | 提交复盘结论（新建或更新） |

### 5.4 文本解析

| 方法 | 功能 |
|------|------|
| `openTextParser()` | 弹窗输入格式化文本后调用 `parseContent()` |
| `parseContent(text)` | 正则解析 `【地区】`、`【更新内容】`、`【上线时间】` 等标签，自动填充表单；开发/测试人员支持模糊匹配 |
| `getConvertUpdateTime()` | 兼容多种时间格式的正则解析 |

### 5.5 消息与导出

| 方法 | 功能 |
|------|------|
| `sendMessageToGroup(row)` | 将记录发送到钉钉群 |
| `copyYesterdayContent()` | 筛选近2天记录，格式化文本并复制到剪贴板 |
| `copyScreenshot()` | html2canvas 截取表格生成 PNG 图片到剪贴板 |
| `exportToExcel()` | 导出为 xlsx 文件（序号/国家/内容/更新时间/影响范围） |

### 5.6 格式化 & 辅助

| 方法 | 功能 |
|------|------|
| `formatContentWithLinks(content)` | 识别 `URL（描述）` 模式渲染为可点击超链接 |
| `formatCountry(row)` | country value → 中文 label |
| `formatReview(row)` | `is_review` → "复盘"/"不复盘" |
| `handleCountryChange(selected)` | 选择特定组合国家时自动填充影响范围 |
| `handleEditorReady(quill)` | 配置 Quill 粘贴处理器：图片→上传并插入；富文本→去除格式 |
| `insertImageToEditor(url)` | 在 Quill 光标位置插入图片 |
| `handleResize()` | 监听窗口大小变化更新弹窗尺寸 |

---

## 6. API 接口

待实现

---

## 7. 生命周期钩子

| 钩子 | 逻辑 |
|------|------|
| `onMounted`（初始化） | 设置默认查询时间范围（过去7天至今天 23:59:59）、默认国家 "ALL"、调用 `fetchUserList()` 和 `fetchRecords()` |
| `onMounted`（事件监听） | 注册 `resize` 窗口大小变化监听 |
| `onBeforeUnmount` | 移除 `resize` 事件监听 |

---

## 8. 表单校验规则

| 字段 | 规则 |
|------|------|
| country | 必填 |
| update_time | 必填 |
| update_time_out | 必填 |
| content | 必填，字符串类型，最少1字符 |
| type | 必填（但 UI 已隐藏） |
| platform | 必填 |
| tester | 必填，数组类型，最少1项 |
| updater | 必填，数组类型，最少1项 |

> ⚠️ **注意**：`submitForm()` 中未调用 `formRef.validate()`，校验规则实际未生效。

---

## 9. 服务端数据模型

- **表名**：`upgrade_record`
- **字段**：`id`, `country`, `content`, `update_time`, `updater`, `tester`, `type`, `platform`, `review_conclusion`, `remark`, `is_review`, `impact`
- **排序**：按 `update_time DESC`
- **筛选**：支持按 country 和时间范围过滤

---

## 10. 特色功能总结

| 功能 | 说明 |
|------|------|
| **文本解析器** | 可粘贴包含 `【地区】`、`【更新内容】`、`【上线时间】` 等标签的格式化文本，自动解析填充表单 |
| **多国家批量创建** | 表单选择多个国家时，每个国家独立创建一条记录 |
| **钉钉群发送** | 一键将升级记录发送到钉钉群 |
| **富文本复盘** | QuillEditor 编辑器支持图片粘贴自动上传 |
| **内容链接识别** | 正则识别 `URL（描述文字）` 模式并渲染为可点击超链接 |
| **截图复制** | html2canvas 截取表格生成 PNG 图片到剪贴板 |
| **Excel 导出** | 导出序号/国家/内容/更新时间/影响范围 5列数据 |
| **无分页** | 当前一次性加载所有符合条件的数据 |
