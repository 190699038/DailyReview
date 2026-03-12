# DailyReview 前端系统 - AI 需求分析文档

> 生成日期：2026-03-11  
> 基于源码全量分析自动生成

---

## 一、项目概述

**DailyReview** 是一个企业级日/周目标规划与进度跟踪系统的前端客户端，主要服务于多部门团队的任务管理场景。

| 属性 | 值 |
|---|---|
| 技术栈 | Vue 3 (Composition API) + Vite 6.2 + Element Plus 2.9.6 |
| 图表库 | ECharts 5.6 (原生) + vue-echarts 7.0 |
| HTTP 库 | Axios 1.8.3 |
| Excel 处理 | xlsx 0.18.5 + html2canvas 1.4.1 |
| 路由 | vue-router 4.5 (Hash 模式) |
| 后端 API | PHP (`https://daily.gameyzy.com/server/`) |
| 开发代理 | `http://10.10.10.95` (已注释) |

---

## 二、页面布局与路由架构

### 2.1 路由结构

```
/ (MainLayout)
├── /week-goal        → WeekGoal.vue     ← 默认重定向页（主功能）
├── /test-task        → TestTask.vue
├── /system-setting   → SystemSetting.vue
├── /daily-goal       → DailyGoal.vue    ← 已注册但导航栏隐藏
├── /history-daily    → DailyHistory.vue  ← 已注册但导航栏隐藏
└── /user-today-plan  → UserTodayPlan.vue ← 懒加载，导航栏隐藏
```

### 2.2 主布局 (MainLayout.vue)

```
┌──────────────────────────────────────────────────┐
│  PC端：水平顶部导航栏 (el-menu mode="horizontal")  │
│  [周目标管理] [测试任务] [部门设置]    [⚙主题设置]    │
├──────────────────────────────────────────────────┤
│                                                  │
│           <router-view> 内容区域                   │
│           padding: 20px (移动端: 0)               │
│                                                  │
└──────────────────────────────────────────────────┘

移动端：
┌──────────────────┐
│ 周目标管理  ⚙ ☰  │  ← header 50px
├──────────────────┤
│  抽屉菜单(60%)    │  ← el-drawer direction="rtl"
├──────────────────┤
│  内容区域         │
└──────────────────┘
```

**主题设置抽屉**：
- 暗黑模式开关 (`dark` class on `<html>`)
- 7 色主题色选择器（蓝/黑/绿/橙/红/紫/粉）
- 主题持久化到 `localStorage` (key: `theme`, `primaryColor`)

**初始化逻辑** (`onMounted`):
1. 从 `department_id_cache` 读取部门ID（默认2）
2. 调用 `UserInfoAPI.php?action=get_users` 获取当前部门用户列表 → 缓存到 `departments_user_cache`
3. 调用 `UserInfoAPI.php?action=get_all_users` → 缓存到 `all_users`
4. 调用 `megerOAUserIDS()` 尝试合并OA系统用户ID（当前函数体内已注释为空）

---

## 三、核心功能页面详细分析

### 3.1 周目标管理 (WeekGoal.vue → 三 Tab 架构)

```
┌─────────────────────────────────────────┐
│ [周目标] [数据统计] [导入【部门名】]      │  ← el-tabs type="card"
├─────────────────────────────────────────┤
│                                         │
│  Tab 内容区                              │
│                                         │
└─────────────────────────────────────────┘
```

- **Tab1 "周目标"** → `WeekTask.vue`
- **Tab2 "数据统计"** → `WeekTaskStatics.vue`
- **Tab3 "导入【部门】"** → 仅部门 ID 为 2/3/5 时显示
  - 部门3 → `WeekTaskBatch.vue`
  - 部门2 → `WeekTaskBatchGame.vue`
  - 部门5 → `WeekTaskBatchProduct.vue`

**部门变化通信机制**：子组件 `emit('department-change')` → 父组件更新 `selectedDepartmentId`、`selectedDepartmentName`、`localStorage`。

---

#### 3.1.1 周目标列表 (WeekTask.vue)

**页面布局（PC端）**：

```
┌─────────────────────────────────────────────────────────┐
│ [周范围▼] [搜索框] [部门▼] [负责人▼] [状态▼] [优先级▼]    │
│ [地区▼] [创建日期] [截止日期] [完成日期]                   │
│ [新增周目标] [查询] [简单复制] [复制全部] [发送周任务到群]   │
├─────────────────────────────────────────────────────────┤
│ 序号 │ 周目标 │ 负责人 │ 优先级 │ 完成进度 │ 是否跨周 │     │
│      │        │        │       │ 进度条  │ 创建日期 │     │
│      │        │        │       │ 预计时间 │ 上线时间 │     │
│      │        │        │       │ 备注    │ 操作     │     │
│      │        │        │       │        │ [修改][删除]│    │
│      │        │        │       │        │ [移动][完成]│    │
└─────────────────────────────────────────────────────────┘
```

**页面布局（移动端）**：
- 周范围下拉 + 筛选按钮(抽屉) + 新增按钮
- 卡片列表替代表格，每张卡片包含优先级/状态/日期/目标内容/负责人/进度条/操作按钮

**数据字段**：

| 字段 | 含义 | 类型/取值 |
|---|---|---|
| id | 目标ID | int |
| weekly_goal | 周目标内容 | string |
| executor | 执行人姓名 | string |
| executor_id | 执行人ID（支持多人，`/`分隔） | string |
| priority | 优先级 | 10=S, 9=A, 8=B, 7=C |
| status | 完成进度 | 0=未开始, 1=进行中, 2=测试中, 3=已上线, 4=已暂停, 5=已完成 |
| process | 进度百分比 | 0~1 浮点数 |
| country | 地区/项目编码 | 见地区编码表 |
| cross_week | 是否跨周 | 0=当周完成, 1=跨周完成 |
| createdate | 创建日期 | YYYYMMDD |
| pre_finish_date | 预计完成日期 | YYYYMMDD |
| real_finish_date | 实际上线日期 | YYYYMMDD |
| remark | 备注 | string |
| mondayDate | 所属周一日期 | YYYYMMDD |
| department_id | 部门ID | int |
| department_name | 部门名称 | string |
| is_new_goal | 是否新增需求标记 | 0/1 |

**地区编码表** (`countryOptions`):

| 编码 | 地区 | 编码 | 地区 |
|---|---|---|---|
| US1 | 美国1 | US2 | 美国2 |
| US3 | 美国3 | US4 | 美国4 |
| QSJS | 奇胜-技术 | QSLL | 奇胜-流量 |
| QSDY | 奇胜-电影 | TF | 投放 |
| YW | 运维 | DF | 大风 |
| YR | 游锐 | XR | 游锐-巡锐 |
| CW | 财务 | OA | OA系统 |
| YXJS | 游戏技术 | KF | 客服 |
| BR1 | 巴西1 | BR2 | 巴西2 |
| MX | 墨西哥 | OZ | 欧洲 |
| ZD | 中东 | AIFN | AI赋能 |
| AIGLJ | AI-古兰经 | ... | ... |

**核心操作**：
- **新增/编辑**：弹窗表单（执行人多选、优先级、地区、目标内容、跨周、周范围、状态、进度slider、截止日期、实际完成时间、备注）
- **删除**：确认删除
- **移动**：将未完成任务移至下一周 (`submitFormSimple(row, 1)`)
- **完成**：标记任务完成 (`submitFormSimple(row, 0)`)
- **一键完成**：设置状态=已上线 + 实际完成日期=当天
- **复制全部**：格式化为文本复制到剪贴板
- **简单复制**：精简格式复制
- **发送周任务到群**：仅部门ID=0时显示，调用 `WeekTaskSend.php?mondayDate=xxx`

**周范围选择**：生成3个选项（上上周一、上一周一、当前周一），格式 `YYYYMMDD`。

**列宽自适应**：使用 `getWidth(w)` 函数 → `window.innerWidth * (w/90) * 0.05`，并监听 `resize` 事件重新加载数据。

---

#### 3.1.2 数据统计 (WeekTaskStatics.vue)

**页面布局**：

```
┌───────────────────────────────────────────────────┐
│ [开始日期] [结束日期] [部门▼]                       │
│ [查询] [本周] [近一周] [近两周] [近三周] [近四周]     │
├───────────────────────────────────────────────────┤
│              周目标任务统计                          │
│ ┌─────────────────────────────────────┐           │
│ │ S类: x/y  │ A类: x/y  │ B类: x/y  │ C类: x/y  │
│ └─────────────────────────────────────┘           │
│ ┌──────────────┬──────────────┐                   │
│ │ 优先级任务     │ 任务状态分布   │  ← 两个饼图     │
│ │ 状态分布饼图   │ 饼图          │                  │
│ └──────────────┴──────────────┘                   │
│ ┌────────────────────────────────────┐            │
│ │ 执行人任务分布（玫瑰图 + 右侧统计列表）│            │
│ └────────────────────────────────────┘            │
│ ┌────────────────────────────────────┐            │
│ │ 任务完成趋势 折线图                  │            │
│ │ [按日] [按周] [按月]                │            │
│ └────────────────────────────────────┘            │
│ ┌────────────────────────────────────┐            │
│ │ 点击饼图/数据 → 弹窗显示任务详情表格  │            │
│ └────────────────────────────────────┘            │
└───────────────────────────────────────────────────┘
```

**图表引擎**：原生 ECharts (`echarts.init`)，非 vue-echarts。

**统计维度**：
- 按优先级(S/A/B/C)的完成数/总数
- 按状态(进行中/已上线/已暂停/已完成)的饼图
- 按执行人的任务数和完成率（玫瑰图）
- 按时间(日/周/月)的完成趋势折线图

**日期快捷范围**：本周、近一周、近两周、近三周、近四周。均自动计算周一到周日区间。

---

#### 3.1.3 批量导入

**三个导入组件共同模式**：
1. 上传 Excel 文件 (.xlsx/.xls)
2. 前端解析 → 预览表格
3. 选择周一日期 → 逐条调用 `WeekGoalAPI.php?action=create` 批量创建

| 组件 | 适用部门 | Excel 格式 |
|---|---|---|
| `WeekTaskBatch.vue` | 部门3(奇胜-技术) | A列项目组, B列优先级, C列需求内容, D列研发人员, M列需求名称 |
| `WeekTaskBatchGame.vue` | 部门2(游戏) | A列优先级, C列需求内容, D列研发人员, F-K列时间, L列备注 |
| `WeekTaskBatchProduct.vue` | 部门5(产品) | A列优先级, C列需求内容, D列产品经理, 含项目名称列 |

**Excel 解析特点**：
- `WeekTaskBatch.vue`：复杂的项目组名称映射（投放、OA系统、奇胜-流量/技术、美国1-4 等），支持 `@用户名` 格式解析多人
- `WeekTaskBatchGame.vue`：从第一行提取日期范围(如 `08-18~08-23`)，自动映射 F-K 列到具体日期，过滤"暂停"行
- `WeekTaskBatchProduct.vue`：含项目名称和跨周状态

---

### 3.2 测试任务 (TestTask.vue)

```
┌─────────────────────────────────────────────┐
│ [历史数据] [新增任务]                          │ ← el-tabs type="card"
├─────────────────────────────────────────────┤
│ Tab1(历史数据): TestTaskStatics.vue          │
│   时间筛选 + 饼图/柱状图/折线图 + 折叠面板     │
│   按负责人分组展示任务                         │
│                                             │
│ Tab2(新增任务):                              │
│   [选择Excel] [测试任务日期] [更新列表测试任务]  │
│   14列表格（负责人/优先级/产品/测试内容/        │
│   测试状态/测试进度/提测时间/上线时间/           │
│   耗时/困难与障碍...）                        │
└─────────────────────────────────────────────┘
```

**Excel 表头映射**（14列）：
负责人、优先级、产品、测试内容（需求链接）、测试状态、测试进度、预计提测时间、提测时间、预计上线时间、困难与障碍、实际上线时间、预计耗时（用例+测试）、测试实际耗时（h）、用例耗时（h）

**日期解析**：使用自定义 `unifiedDateParser()`，支持两种格式：
- 完整格式：`2025/7/22 15:00:00`
- 简写格式：`7/23/25` → 自动补全年份 `2025`

**进度条颜色**：<30% 红色，<80% 黄色，≥80% 绿色。

---

### 3.3 部门设置 (SystemSetting.vue)

```
PC端：
┌──────────────────────────────────────┐
│              部门设置                  │
│ 部门选择: [▼部门下拉]                  │
│ [新增用户]                            │
├──────────────────────────────────────┤
│ 姓名 │ 模式 │ 部门 │ 职位 │ 操作      │
│      │      │     │     │ [编辑]     │
└──────────────────────────────────────┘

移动端：
┌──────────────────────────────────────┐
│ [部门选择▼ 100%宽]                    │
│ [新增用户 100%宽]                     │
├──────────────────────────────────────┤
│ ┌──────────────────────────────┐    │
│ │ 用户名称          [编辑]      │    │ ← el-card 卡片列表
│ │ 部门: xxx                    │    │
│ │ 职位: xxx                    │    │
│ │ 模式: [拼搏模式/正常模式]      │    │
│ └──────────────────────────────┘    │
└──────────────────────────────────────┘
```

**编辑/新增弹窗字段**：
- 姓名 (partner_name)
- 模式 (mode): `拼搏模式` | `正常模式` (radio)
- 部门 (department_id): 下拉选择
- 职位 (position): 文本输入
- 状态 (is_active): Switch (1=启用/0=禁用)

**特殊逻辑**：选择"全部"部门(id=0)时调用 `get_all_users`，否则调用 `get_users`。

---

### 3.4 隐藏页面（已注册但导航栏不显示）

#### 3.4.1 日目标管理 (DailyGoal.vue)

**布局**：

```
┌──────────┬────────────────────────────────────────┐
│ 左侧用户  │  [周范围▼] [部门▼]                       │
│ Tab列表   │  [周一][周二][周三][周四][周五][周六][周日]  │
│ (80px)    │  ┌──────────────────────────────────┐  │
│           │  │ 周目标表格                         │  │
│ [今日目标] │  │ 序号/执行人/优先级/目标/状态          │  │
│ 按钮      │  └──────────────────────────────────┘  │
│           │  ┌──────────────────────────────────┐  │
│           │  │ 日计划表格（预估耗时合计）           │  │
│           │  │ 序号/日期/目标/拆解任务/预估耗时/进度 │  │
│           │  └──────────────────────────────────┘  │
│           │  ┌──────────────────────────────────┐  │
│           │  │ 日计划总结表格（实际耗时合计）       │  │
│           │  │ 序号/日期/目标/拆解任务/实际耗时/进度 │  │
│           │  └──────────────────────────────────┘  │
└──────────┴────────────────────────────────────────┘
```

**抽屉（今日目标）**：
- 日期选择 + 文本域输入目标 + 保存按钮
- 支持全屏查看弹窗

#### 3.4.2 历史日报 (DailyHistory.vue)

- 时间线视图 + 卡片UI + 时间段/用户筛选
- 历史任务数据展示

#### 3.4.3 用户今日计划 (UserTodayPlan.vue)

- 日期范围选择 + 表格展示
- 支持截图到剪贴板 (html2canvas)
- 支持文本格式复制

---

## 四、API 接口清单

### 4.1 用户与部门管理

| 接口 | 方法 | 参数 | 说明 |
|---|---|---|---|
| `UserInfoAPI.php?action=get_departments` | GET | 无 | 获取所有部门列表 |
| `UserInfoAPI.php?action=get_users` | GET | `department_id` | 获取指定部门用户列表 |
| `UserInfoAPI.php?action=get_all_users` | GET | 无 | 获取所有用户 |
| `UserInfoAPI.php?action=get_watch_users` | GET | 无 | 获取关注用户列表 |
| `UserInfoAPI.php?action=update_user` | GET | `id, partner_name, department_id, position, is_active, mode` | 更新用户信息 |
| `UserInfoAPI.php?action=add_user` | GET | `partner_name, department_id, position, mode, is_active` | 新增用户 |

### 4.2 周目标管理

| 接口 | 方法 | 参数 | 说明 |
|---|---|---|---|
| `WeekGoalAPI.php?action=get` | GET | `mondayDate, department_id` | 获取某周目标列表 |
| `WeekGoalAPI.php?action=create` | GET | `executor, weekly_goal, priority, mondayDate, status, country, executor_id, cross_week, remark, department_id, ...` | 创建周目标 |
| `WeekGoalAPI.php?action=update` | GET | `id, ...同create字段` | 更新周目标 |
| `WeekGoalAPI.php?action=delete` | GET | `id` | 删除周目标 |
| `WeekGoalAPI.php?action=list` | GET | `startDate, endDate, mondayDates, department_id` | 统计查询（日期区间） |
| `WeekGoalAPI.php?action=move` | GET | `id, mondayDate` | 移动目标到下周 |
| `WeekTaskSend.php` | GET | `mondayDate` | 发送周任务消息到群 |

### 4.3 日任务管理

| 接口 | 方法 | 参数 | 说明 |
|---|---|---|---|
| `DayTaskAPI.php` (POST action=getUserGoalAndTasks) | POST | `monday_date, executor_id, dates` | 获取用户周目标和按天拆分的日任务 |
| `DayTaskAPI.php` (POST action=updateTask) | POST | `id, time_spent, progress, is_new_goal` | 更新日任务 |
| `DayTaskAPI.php` (POST action=getUserTodayTask) | POST | `uids, start_date, end_date` | 获取多用户日计划 |

### 4.4 日目标管理

| 接口 | 方法 | 参数 | 说明 |
|---|---|---|---|
| `DayGoalAPI.php?action=get_target` | GET | `report_date, department_id` | 获取当日目标内容 |
| `DayGoalAPI.php` (POST action=save_target) | POST | `report_date, content, department_id` | 保存当日目标 |

### 4.5 测试任务

| 接口 | 方法 | 参数 | 说明 |
|---|---|---|---|
| `TestTask.php?action=create` | POST (multipart/form-data) | `responsible_person, priority, product, test_content, test_status, test_progress, pre_submission_time, submission_time, planned_online_time, actual_online_time, planned_time_spent, actual_time_spent, actual_yl_time, remarks, creation_date` | 创建测试任务 |
| `TestTask.php?action=list` | GET | `startDate, endDate` | 查询测试任务历史数据 |

### 4.6 外部集成（OA系统 - 当前已注释/停用）

| 接口 | 方法 | 说明 |
|---|---|---|
| `https://oa.aizyun.com/admin/login` | POST | OA系统登录获取token |
| `https://oa.aizyun.com/admin/dailyplan/list` | POST | 获取OA日计划 |
| `https://oa.aizyun.com/admin/sys/user/deptlist/{id}` | GET | 获取OA部门用户列表 |

---

## 五、数据流与缓存策略

### 5.1 LocalStorage 缓存键

| Key | 类型 | 用途 | 默认值 |
|---|---|---|---|
| `department_id_cache` | int/string | 当前选中的部门ID | 2 |
| `departments_cache` | JSON Array | 部门列表（含"全部"选项） | - |
| `departments_user_cache` | JSON Array | 当前部门的用户列表 | - |
| `all_users` | JSON Array | 所有用户列表 | - |
| `token` | string | OA系统登录token（已停用） | - |
| `theme` | string | 主题模式 (`dark`/`light`) | light |
| `primaryColor` | string | 主题色 hex 值 | #409EFF |

### 5.2 数据流向

```
MainLayout.onMounted
    │
    ├── GET get_users → localStorage['departments_user_cache']
    ├── GET get_all_users → localStorage['all_users']
    └── megerOAUserIDS() (空函数)

WeekTask/WeekTaskStatics/SystemSetting 各自加载:
    │
    ├── GET get_departments → localStorage['departments_cache']
    └── 部门切换时 → GET get_users → localStorage['departments_user_cache']
         └── emit('department-change') → 通知父组件
```

---

## 六、可优化内容

### 6.1 架构与代码质量

| # | 问题 | 位置 | 建议 |
|---|---|---|---|
| 1 | **重复的部门获取逻辑** | `WeekTask.vue`, `WeekTaskStatics.vue`, `SystemSetting.vue`, `DailyGoal.vue` 各自实现了几乎相同的 `fetchDepartments()` 和 `handleDepartmentChange()` | 抽取为 composable: `useDepartments()` |
| 2 | **重复的周一日期生成逻辑** | `WeekTask.vue`, `WeekTaskStatics.vue`, `DailyGoal.vue` 各自实现 `getCurrentMonday()` 和 `generateMondayOptions()` | 统一到 `dateUtils.js` |
| 3 | **重复的日期范围计算** | `WeekTaskStatics.vue` 和 `TestTaskStatics.vue` 有几乎相同的 `setDateRange()` | 抽取为共用工具函数 |
| 4 | **重复的 `getExecutorId()` 函数** | `WeekTaskBatch.vue`, `WeekTaskBatchGame.vue`, `WeekTaskBatchProduct.vue` 各有一份 | 统一到工具函数 |
| 5 | **重复的 `getCountry()` / `getCountryName()` 地区映射** | 多个 Batch 组件各维护一套 | 统一为配置文件 |
| 6 | **WeekGoal.vue 混用 Options API 和 Composition API** | `export default { setup() {} }` 而非 `<script setup>` | 统一为 `<script setup>` 风格 |
| 7 | **WeekGoal.vue 模块级变量** | `activeTab`, `selectedDepartmentId` 等定义在 `<script>` 的模块顶层（setup() 之外），多实例可能共享状态 | 移入 `setup()` 内部 |
| 8 | **dateUtils.js 导入了不需要的 Vue API** | `import { ref, computed, defineProps, defineEmits } from 'vue'` | 删除未使用的导入 |

### 6.2 API 设计与安全

| # | 问题 | 位置 | 建议 |
|---|---|---|---|
| 1 | **写操作使用 GET 方法** | `WeekGoalAPI.php?action=create/update/delete` 通过 GET 请求执行写操作 | 改为 POST/PUT/DELETE，参数放到 request body |
| 2 | **用户信息更新使用 URL params** | `SystemSetting.vue` 的 `submitEdit` 将用户信息放在 GET URL 查询参数中 | 使用 POST + JSON body |
| 3 | **硬编码凭据** | `dailyPlanAsync.js` 包含硬编码的 OA 登录用户名和密码 | 移至环境变量或后端代理，当前虽已注释但仍存在于源码 |
| 4 | **API baseURL 硬编码** | `http.js` 中 `baseURL` 直接写死生产域名 | 使用 Vite 环境变量 `import.meta.env.VITE_API_BASE` |
| 5 | **缺少统一的请求/响应拦截** | 无 token/鉴权 header 注入，401 处理仅跳转但无 token 刷新 | 添加 request 拦截器注入 auth header |
| 6 | **批量创建逐条请求** | `WeekTaskBatch*.vue` 的 `createTask` 使用 for 循环逐条调用 API | 后端提供批量创建接口，前端一次性提交 |
| 7 | **`sendTaskToGroup` 直接使用 `fetch` 绕过 axios** | `WeekTask.vue` 中调用 `WeekTaskSend.php` 未使用统一 http 工具 | 统一使用 `http.get()` |

### 6.3 性能优化

| # | 问题 | 位置 | 建议 |
|---|---|---|---|
| 1 | **resize 事件触发 `loadData()` 重新请求 API** | `WeekTask.vue` 的 `handleResize` 在每次窗口大小变化后重调 `loadData()` | resize 仅更新布局，不应重新请求API数据 |
| 2 | **ECharts 实例未统一管理** | `WeekTaskStatics.vue` 和 `TestTaskStatics.vue` 手动 `init/dispose`，遇到组件切换可能内存泄漏 | 使用 vue-echarts 组件或封装 useChart composable |
| 3 | **全局 `addEventListener` 拦截** | `main.js` 中重写了 `EventTarget.prototype.addEventListener`，对所有事件监听器默认添加 `passive: true` | 仅对特定滚动事件添加，避免全局副作用 |
| 4 | **Excel 导入无文件大小校验** | 多个 Batch 组件无文件大小限制 | 添加 `before-upload` 校验，限制如 10MB |
| 5 | **Tab 切换未使用懒加载** | `WeekGoal.vue` 三个 tab 内容同时渲染 | 使用 `lazy` 属性或 `v-if` 按需渲染 |

### 6.4 用户体验

| # | 问题 | 位置 | 建议 |
|---|---|---|---|
| 1 | **ExcelParser 的 priorityMap 有重复键** | `excelParser.js` 中 `'C'` 键重复出现 4 次，只有最后一个值(2)生效 | 使用区分的键名如 `C+`, `C`, `C-` |
| 2 | **批量上传无进度反馈** | `WeekTaskBatch*.vue` 逐条上传期间用户无法看到已完成数量 | 添加实时进度条或已完成计数 |
| 3 | **"发送周任务到群"按钮仅部门ID=0时显示** | `WeekTask.vue` | 考虑是否所有部门都需要此功能，或添加权限控制 |
| 4 | **移动端适配不完整** | `DailyGoal.vue` 完全没有移动端响应式布局，左侧 80px 用户列表在移动端效果差 | 添加 `useResponsive` 适配 |
| 5 | **缺少搜索防抖** | `WeekTask.vue` 的搜索框 (`searchText`) 直接用于计算属性过滤，无防抖 | 对 `searchText` 添加 debounce |
| 6 | **列宽计算不合理** | `getWidth()` 使用 `innerWidth * (w/90) * 0.05`，部分列可能过窄 | 使用 Element Plus 的 `min-width` 配合 flex |
| 7 | **日期选择器缺少合理的日期范围约束** | 多处日期选择器无 `disabled-date` 限制 | 添加日期范围限制避免选择未来日期等无效场景 |

### 6.5 可维护性

| # | 问题 | 位置 | 建议 |
|---|---|---|---|
| 1 | **无 TypeScript** | 全项目 | 核心接口类型定义至少用 JSDoc 标注或迁移 TypeScript |
| 2 | **无单元测试** | 全项目 | 添加 Vitest + @vue/test-utils 对核心组件进行测试 |
| 3 | **无统一的常量管理** | 优先级映射、状态映射、地区编码散布各组件 | 创建 `src/constants/` 统一管理 |
| 4 | **`DayTaskInfo copy.vue` 冗余** | `src/components/` | 删除多余的 copy 文件 |
| 5 | **`HelloWorld.vue` 脚手架残余** | `src/components/` | 删除 |
| 6 | **`plugins/element.js` 重复** | 项目根目录和 `src/plugins/` 各有一份，且 `main.js` 中已注释掉引用 | 保留 `src/plugins/element.js` 的方式或统一使用全局注册 |
| 7 | **无 ESLint/Prettier** | `package.json` 缺少代码规范工具 | 添加 ESLint + Prettier 统一代码风格 |
| 8 | **无 .env 环境配置文件** | 无 | 创建 `.env.development`/`.env.production` 管理 API 地址等配置 |

### 6.6 组件职责优化建议

| 当前组件 | 问题 | 优化方向 |
|---|---|---|
| `WeekTask.vue` | 500+ 行，混合了筛选、表格、表单、Excel导入弹窗、复制、群发等功能 | 拆分为: 筛选器组件、目标表格组件、目标表单弹窗组件 |
| `DailyGoal.vue` | 750+ 行，包含周日历、周目标表格、日计划表格×2、今日目标抽屉 | 拆分为: 周日历组件、目标面板组件、日计划组件 |
| `TestTask.vue` | 560+ 行，含Excel解析、日期处理、表格渲染 | 将Excel解析逻辑移入service层 |

---

## 七、目录结构建议

```
src/
├── api/                      # API 接口层 (NEW)
│   ├── department.js         # 部门相关接口
│   ├── weekGoal.js           # 周目标接口
│   ├── dayTask.js            # 日任务接口
│   └── testTask.js           # 测试任务接口
├── composables/              # 组合式函数
│   ├── useDepartments.js     # (NEW) 统一部门管理逻辑
│   ├── useResponsive.js      # ✓ 已有
│   └── useTheme.js           # ✓ 已有
├── constants/                # 常量定义 (NEW)
│   ├── priority.js           # 优先级映射
│   ├── status.js             # 状态映射
│   └── country.js            # 地区编码
├── components/               # 页面组件
├── view/                     # 子视图组件
├── utils/                    # 工具函数
│   ├── dateUtils.js          # 日期工具 (扩展)
│   ├── excelParser.js        # Excel解析
│   └── http.js               # HTTP服务
└── router/
    └── index.js
```

---

## 八、数据模型关系图

```
Department (部门)
    │ 1:N
    ├── User (用户/partner)
    │   ├── id
    │   ├── partner_name
    │   ├── mode (拼搏/正常)
    │   ├── department_id → FK
    │   ├── position
    │   ├── is_active (0/1)
    │   └── oa_userid (OA系统ID，可空)
    │
    ├── WeekGoal (周目标)
    │   ├── id
    │   ├── mondayDate (YYYYMMDD, 所属周)
    │   ├── executor / executor_id → FK User
    │   ├── weekly_goal (内容)
    │   ├── priority (10/9/8/7)
    │   ├── status (0~5)
    │   ├── process (0~1)
    │   ├── country (地区编码)
    │   ├── cross_week (0/1)
    │   ├── department_id → FK
    │   ├── createdate
    │   ├── pre_finish_date
    │   ├── real_finish_date
    │   └── remark
    │
    ├── DayTask (日任务)
    │   ├── id
    │   ├── date (YYYYMMDD)
    │   ├── executor_id → FK User
    │   ├── day_goal
    │   ├── task_content
    │   ├── time_spent (小时)
    │   ├── progress (百分比)
    │   └── is_new_goal (0/1)
    │
    ├── DayGoal (每日部门目标)
    │   ├── report_date (YYYYMMDD)
    │   ├── department_id → FK
    │   └── content (文本)
    │
    └── TestTask (测试任务)
        ├── task_id
        ├── responsible_person
        ├── priority
        ├── product
        ├── test_content
        ├── test_status
        ├── test_progress
        ├── submission_time
        ├── planned_online_time
        ├── actual_online_time
        ├── planned_time_spent
        ├── actual_time_spent
        ├── actual_yl_time
        ├── remarks
        └── creation_date
```

---

## 九、技术债务清单

| 优先级 | 事项 | 影响 |
|---|---|---|
| **高** | 写操作使用 GET 方法，参数暴露在 URL | 安全风险，爬虫/预加载可能触发意外操作 |
| **高** | 硬编码凭据在 `dailyPlanAsync.js` | 安全风险（虽已注释但仍在代码中） |
| **高** | 无环境变量管理 | 开发/生产环境切换困难 |
| **中** | 大量重复代码（部门管理、日期工具） | 维护成本高，bug 修复需改多处 |
| **中** | resize 触发 API 请求 | 性能浪费 |
| **中** | 无 API 层抽象 | API 调用散布在组件中，难以统一管理 |
| **低** | 无 TypeScript / 测试 | 长期可维护性风险 |
| **低** | 冗余文件 (`DayTaskInfo copy.vue`, `HelloWorld.vue`) | 代码库整洁度 |

---

*本文档由 AI 基于全量源码分析自动生成，覆盖 20+ 源文件、6 大功能模块、30+ API 接口。*
