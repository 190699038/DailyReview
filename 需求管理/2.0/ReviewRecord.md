# ReviewRecord 组件功能文档

## 1. 架构概览

`ReviewRecord.vue` 是一个评审记录管理组件，用于记录和管理评审会议内容。

- **路由路径**：`/review-record`（`MainLayout` 的子路由）
- **实际使用的数据表**：`chen_yaopu_review`（对应服务端 `ChenYaopuReview.php` 模型）
- **路由模式**：Hash 模式

---

## 2. 页面 UI 结构

### 2.1 顶部筛选/操作栏

| UI 元素 | 说明 |
|---------|------|
| 新增记录按钮 | 打开新增对话框 |
| 开始日期 | `el-date-picker`，格式 `YYYYMMDD` |
| 结束日期 | `el-date-picker`，格式 `YYYYMMDD` |
| 查询按钮 | 触发 `fetchRecords()` |
| 复制截图 | 截取表格前5列为 PNG 图片复制到剪贴板 |
| 导出 Excel | 导出全部数据为 `.xlsx` 文件 |

### 2.2 数据表格

| 列名 | 字段 (`prop`) | 宽度 | 特殊处理 |
|------|--------------|------|----------|
| 序号 | — | 90px | `scope.$index + 1`（自动行号） |
| 日期 | `date` | 150px | — |
| 发起人 | `initiator` | 120px | 超出省略 tooltip |
| 参与人 | `participants` | 120px | 超出省略 tooltip |
| 目的 | `purpose` | 300px | 超出省略 tooltip |
| 结论 | `content` | 自适应 | `white-space: pre-line` 保留换行 |
| 下一步 | `next_step` | 200px | — |
| 有价值 | `valuable` | 90px | 条件着色：绿色(有价值=1)、红色(无价值=0)、灰色(常规会议=2) |
| 价值体现 | `value_content` | 200px | — |
| 操作 | — | 150px | 编辑 + 删除按钮 |

### 2.3 新增/编辑对话框

- **宽度**：90%，最大高度 90vh
- **标题**：动态显示"新增记录"或"编辑记录"

| 表单字段 | 组件类型 | 说明 |
|---------|---------|------|
| 日期 | `el-date-picker` | 格式 `YYYYMMDD` |
| 结论 | **QuillEditor 富文本编辑器** | 高度 35vh，含预设模板 |
| 价值 | `el-radio-group` | 三选一：有价值(1) / 无价值(0) / 常规会议(2) |
| 下一步 | `el-input textarea` | 3行文本输入 |
| 价值体现 | `el-input textarea` | 3行文本输入 |

---

## 3. 数据属性

| 属性 | 类型 | 说明 |
|------|------|------|
| `tableData` | `ref([])` | 表格数据列表 |
| `conclusion` | `ref(HTML模板)` | Quill 编辑器内容（双向绑定），含预设模板 |
| `searchParams` | `ref({})` | 查询条件（start_date, end_date） |
| `customColors` | 常量 | 编辑器自定义颜色 `['#000000', '#ff0000', '#ffffff']` |
| `myQuillEditor` | `ref(null)` | Quill 编辑器实例引用 |
| `dialogVisible` | `ref(false)` | 对话框显示状态 |
| `dialogType` | `ref('create')` | 对话框模式：`create` / `edit` |
| `formRules` | 常量 | 表单验证规则 |
| `users` | `ref([])` | 用户列表（已获取但未在模板中使用） |
| `formData` | `ref({})` | 表单数据对象 |
| `editorOptions` | 常量 | Quill 编辑器配置 |

---

## 4. 方法清单

### 4.1 数据查询

| 方法 | 功能 |
|------|------|
| `fetchRecords()` | 根据日期范围查询评审记录列表 |
| `getUsers()` | 获取用户列表 |

### 4.2 表单操作

| 方法 | 功能 |
|------|------|
| `openDialog(type, row)` | 打开新增/编辑对话框，初始化表单数据和预设模板 |
| `handleEdit(row)` | 填充表单数据并打开编辑对话框，同步结论内容到编辑器 |
| `submitForm()` | **核心方法**：从 Quill 富文本中用正则解析出目的、发起人、参会人、结论等字段，调用 create/update API |
| `handleDelete(row)` | 确认后删除指定记录 |

### 4.3 富文本编辑器

| 方法 | 功能 |
|------|------|
| `handleTextChange()` | 编辑器文本变化事件，尝试从内容中解析日期 |
| `handleSelectionChange()` | 编辑器选择变化事件 |
| `handleEditorReady(quill)` | 编辑器就绪回调：配置图片粘贴处理器（base64→上传）和富文本粘贴处理器（去除格式） |
| `insertImageToEditor(url)` | 在编辑器光标位置插入图片 |

### 4.4 导出功能

| 方法 | 功能 |
|------|------|
| `exportToExcel()` | 导出为 xlsx 文件，含表头样式（蓝底白字加粗）、交替行色、边框 |
| `copyScreenshot()` | 克隆表格前5列，使用 html2canvas 生成截图复制到剪贴板 |

---

## 5. API 接口

待实现

---

## 6. 服务端数据模型

- **表名**：`chen_yaopu_review`
- **字段**：`id`, `date`, `purpose`, `initiator`, `participants`, `conclusion`, `screenshot_url`, `content`, `around_goal`, `next_step`, `valuable`, `value_content`
- **排序**：`ORDER BY date DESC`
- **筛选**：支持 `start_date` / `end_date` 日期范围过滤

---

## 7. 生命周期钩子

| 钩子 | 逻辑 |
|------|------|
| `onMounted` | 1. 调用 `getUsers()` 获取用户列表<br>2. 设置默认日期范围（过去7天至当天）<br>3. 初始化 `formData.date` 为当天<br>4. 调用 `fetchRecords()` 加载数据 |

---

## 8. 表单校验规则

| 字段 | 规则 |
|------|------|
| date | 必填 |

> 其他字段无校验约束。

---

## 9. 特色功能

### 9.1 QuillEditor 富文本编辑器

- **工具栏**：加粗、斜体、下划线、删除线、引用、代码块、字体颜色、背景色、清除格式
- **预设模板**：新增时自动填充包含 `目的`、`发起人`、`参会人`、`结论` 标签的模板
- **智能解析**：提交时从富文本 HTML 中通过正则自动提取目的、发起人、参会人、结论四个字段
- **图片粘贴**：base64 图片自动上传至服务端，返回 URL 后插入编辑器
- **粘贴处理**：粘贴富文本时自动去除格式，只保留纯文本

### 9.2 截图复制

- 使用 `html2canvas` 将表格前5列渲染为 Canvas
- 通过 `navigator.clipboard.write` 写入剪贴板

### 9.3 Excel 导出

- 使用 `xlsx` + `file-saver` 库
- 含表头样式（蓝底白字加粗）、交替行色、边框、自动换行
- 文件名格式：`评审记录_YYYY-MM-DD.xlsx`

### 9.4 价值可视化

- 「有价值」列根据值不同显示不同颜色标签：
  - **绿色**：有价值 (`valuable = 1`)
  - **红色**：无价值 (`valuable = 0`)
  - **灰色**：常规会议 (`valuable = 2`)

---

## 10. 组件依赖

| 依赖 | 用途 |
|------|------|
| `@vueup/vue-quill` | QuillEditor 富文本编辑器 |
| `quill-delta` | Quill Delta 对象操作 |
| `html2canvas` | 表格截图 |
| `xlsx` | Excel 文件生成 |
| `file-saver` | 文件下载（saveAs） |
| `element-plus` | UI 组件库 |
| `axios`（通过 api.js） | HTTP 请求 |

---

## 11. 其他说明

- **无分页**：所有查询结果一次性加载显示
- **无计算属性**：未使用 `computed`
- **无侦听器**：未使用 `watch`
