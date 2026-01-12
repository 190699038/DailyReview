# DailyReview Client - AI Assistant Instructions

## Project Overview
Vue 3 + Vite frontend for DailyReview - an enterprise daily/weekly planning and progress tracking system. Manages user daily goals, weekly goals, department settings, and performance metrics with data export capabilities.

**Tech Stack**: Vue 3 (Composition API), Vite 6.2, Element Plus 2.9.6, Axios, XLSX, Vue-ECharts

## Architecture & Key Components

### Build & Development Workflow
- **Build tool**: Vite 6.2 with Vue 3 plugins (SFC compilation + JSX support)
- **Start dev server**: `npm run dev` → serves on `0.0.0.0:5173`
- **Production build**: `npm run build` → generates optimized bundle with sourcemaps enabled
- **Key config**: `vite.config.js` uses path alias `@` → `./src` and relative asset paths (`base: './'`)
- **Dev Proxy**: Maps `/DailyReview/server/*` → backend API (configured for both local dev `10.10.10.95` and production `https://daily.gameyzy.com`)
- **Backend API Base**: Production defaults to `https://daily.gameyzy.com/server/` in [http.js](src/utils/http.js#L5)

### Router Architecture ([src/router/index.js](src/router/index.js))
- Uses hash-based history (`createWebHashHistory`) for reliable client-side routing without server rewrite rules
- Main layout wrapper: [MainLayout.vue](src/components/MainLayout.vue) (horizontal top nav bar)
- Route structure: All routes nested under `/` with MainLayout parent
- **Active routes**:
  - `week-goal` → [WeekGoal.vue](src/components/WeekGoal.vue) (default redirect, main feature)
  - `test-task` → [TestTask.vue](src/components/TestTask.vue)
  - `system-setting` → [SystemSetting.vue](src/components/SystemSetting.vue)
- **Inactive/Draft routes**: `daily-goal`, `history-daily`, `user-today-plan` (components exist but not shown in nav)

### Core Features & Components

#### 1. **Weekly Goals Management** ([WeekGoal.vue](src/components/WeekGoal.vue) → [src/view/WeekTask*.vue](src/view/))
- **Structure**: Wrapper component that manages 3 tabs based on department
  - Tab 1: "周目标" → [WeekTask.vue](src/view/WeekTask.vue) (weekly goal CRUD)
  - Tab 2: "数据统计" → [WeekTaskStatics.vue](src/view/WeekTaskStatics.vue) (charts)
  - Tab 3: "导入【Department】" → Department-specific bulk import (only for depts 2, 3, 5)
- **Bulk import sub-components**: [WeekTaskBatchGame.vue](src/view/WeekTaskBatchGame.vue), [WeekTaskBatchProduct.vue](src/view/WeekTaskBatchProduct.vue), [WeekTaskBatch.vue](src/view/WeekTaskBatch.vue)
- **Table columns**: executor name, weekly_goal, department_name, is_new_goal flag, create_date, actions
- **Conditional styling**: Goals with `is_new_goal=1` get yellow background (#FFF3CE)
- **Dept-specific rendering**: WeekGoal.vue conditionally renders import tabs: `v-if="selectedDepartmentId == 2 || selectedDepartmentId == 3 || selectedDepartmentId == 5"`
- **API endpoint**: `/WeekGoalAPI.php?action=get` with params `mondayDate` (YYYYMMDD) + `department_id`
- **Key pattern**: Parent component emits `@department-change` events; child tabs listen and trigger data reloads

#### 2. **Department & User Management** ([SystemSetting.vue](src/components/SystemSetting.vue))
- **Component structure**: Department selector dropdown + Users table + Edit/Add dialogs
- **Department dropdown**: Displays all departments; default selection is ID 2 (from `department_id_cache` localStorage)
- **User table columns**: partner_name (user name), mode (work mode), department_name, position, actions (edit button)
- **Edit modal**: Form to modify partner_name, mode (radio: 拼搏模式 | 正常模式), department_id, position, is_active (switch)
- **Add user button**: Opens modal with empty form; submits new user creation
- **Data structure**: `{id, partner_name, mode, department_id, position, is_active, department_name}`
- **API endpoints**:
  - List departments: `/UserInfoAPI.php?action=get_departments` → returns array with id + department_name
  - Get users by dept: `/UserInfoAPI.php?action=get_users&department_id=<id>`
  - Update user: `/UserInfoAPI.php?action=update_user&id=<id>&partner_name=<>&department_id=<>&position=<>&is_active=<>`
  - Add user: `/UserInfoAPI.php?action=add_user&partner_name=<>&department_id=<>&position=<>&mode=<>&is_active=1`

#### 3. **Excel Import/Export** ([excelParser.js](src/utils/excelParser.js), components)
- Function `parseExcelFile(file, type)` → Promise resolving to array of parsed rows
- **Type parameter handling**:
  - `type='daily'`: Maps columns `[id, executor, _, progress, time_spent, date, day_goal, task_content]`
  - `type='other'`: Maps columns `[id, priority, content, executor]`
- **Column mapping**: Uses Excel row array indices (row[0], row[1], etc.)
- **Priority conversion**: Maps string values (S, A, B, C, C+) to numeric scores via `priorityMap` object
- **Component usage**: [WeekTaskBatch.vue](src/view/WeekTaskBatch.vue), [WeekTaskBatchGame.vue](src/view/WeekTaskBatchGame.vue), [WeekTaskBatchProduct.vue](src/view/WeekTaskBatchProduct.vue) all import files with specific `type` values
- **Export capability**: Uses html2canvas + XLSX packages for rendering and file generation

### Data Management Patterns

#### LocalStorage Caching
- `department_id_cache` (integer): Current selected department ID (default: 2)
- `departments_cache` (JSON array): Cached department & user list from API
  - Sample structure: `{id, department_name, partner_name, position, mode, is_active}`
- **Pattern**: Always check cache first before API call; refresh cache after mutations

#### HTTP Service ([src/utils/http.js](src/utils/http.js))
- Axios instance with baseURL: `https://daily.gameyzy.com/server/`
- Request timeout: 5000ms
- Response interceptor: Automatically extracts `response.data`, handles 401 redirects to `/login`
- Error handling: Uses Element Plus `ElMessage` for toast notifications (timeout errors, network errors, API errors)
- **Critical**: All API calls return promises; await required
- **Dev vs Prod**: Local dev uses proxy to `http://10.10.10.95` (configured in vite.config.js); prod uses `https://daily.gameyzy.com/server/`

### Utility Functions

#### Date Utilities ([src/utils/dateUtils.js](src/utils/dateUtils.js))
- `getWeekDates()` → Array of 7 dates (Mon-Sun) in YYYYMMDD format for current week
- `getTodayDate()` → Current date as YYYYMMDD string
- `formatDate(date)` → Converts Date object to YYYYMMDD format
- `getMondayDate(dateStr)` → Parses YYYYMMDD and returns Monday of that week
- **Date format requirement**: All API payloads use YYYYMMDD (no hyphens)
- **Implementation detail**: ISO weekday conversion (0=Sun becomes 7) for accurate Monday calculation

#### OA Integration ([src/utils/dailyPlanAsync.js](src/utils/dailyPlanAsync.js))
- Legacy integration with OA system (https://oa.aizyun.com)
- Functions for fetching user daily plans and department lists from external OA
- Hardcoded credentials in source (dev token caching via localStorage key 'token')
- **Warning**: May be partially unused in current UI; review dependencies before modifying
- **Export function** `megerOAUserIDS` used in [SystemSetting.vue](src/components/SystemSetting.vue) for merging OA user data

### UI Framework
- **Element Plus** (v2.9.6) for components (El-Menu, El-Table, El-Dialog, El-Tabs, El-Dropdown, etc.)
- Global config in [main.js](src/main.js): Custom loading spinner + global error handler
- Icons: `@element-plus/icons-vue` package
- **Important**: No custom CSS framework; use Element Plus slots and scoped styles for customization
- Passive event listeners configured globally for performance (except touchstart/wheel/mousewheel)
- **Styling Note**: Conditional rendering uses inline style binding (e.g., `background: is_new_goal == 1 ? '#FFF3CE' : ''`)

### Key Plugins & Packages
- `vue-echarts` (v7): Chart rendering for statistics tabs
- `html2canvas`: Screenshot capability for exports
- `xlsx`: Excel file reading/writing for bulk operations
- `vue-router` (v4.5): Routing system

## Development Conventions

### Component Structure
- Use `<script setup>` syntax exclusively (Vue 3 composition API)
- Define props/emits at component top before template
- Import utilities from `@/utils/` (alias always)
- Child-to-parent communication via `emit` (example: `@department-change` events in WeekGoal tabs)

### API Call Pattern
```javascript
// Import http service
import http from '@/utils/http'

// GET request
const data = await http.get('/EndpointName.php', { params: { action: 'get', id: 1 } })

// POST/Mutations (if needed)
const result = await http.post('/EndpointName.php', { action: 'create', ...payload })
```

### Error Handling
- Wrap API calls in try/catch
- ElMessage.error() for user-facing errors
- Console.error() for dev debugging (main.js has global error handler attached)

### LocalStorage Access Pattern
- Always use standardized keys: `department_id_cache`, `departments_cache`, etc.
- Parse/stringify JSON manually; no wrapper layer
- Cache invalidation: Clear or update after successful mutations

### Testing & Validation
- Weekly goal date validation must parse YYYYMMDD format correctly
- Department selector default to ID 2 when cache empty
- Table pagination/sorting: Let Element Plus handle (defined in component data)
- Modal form validation: Use Element Plus Form component with rules

## Common Workflow Examples

### Adding a New Department Settings Field
1. Add column to `SystemSetting.vue` table definition
2. Include field in user update modal form
3. Update API params in mutation call (UserInfoAPI.php)
4. Test with cache refresh

### Fixing Weekly Goal Display Issues
1. Check `departments_cache` exists and is valid JSON
2. Verify API returns correct `is_new_goal` field (0|1)
3. Conditional styling in template uses `is_new_goal == 1 ? '#FFF3CE' : ''`
4. Department-specific tabs in WeekGoal.vue controlled by `selectedDepartmentId` comparisons

### Extending Excel Import
1. Modify `parseExcelFile()` in excelParser.js to handle new columns
2. Update component (WeekTaskBatch*.vue) to pass correct `type` parameter
3. Map Excel row indices to data structure (example: `row[0]=id, row[1]=name, row[2]=content`)

## Performance Notes
- Sourcemaps enabled in prod build for debugging deployed issues
- Large XLSX files parsed in FileReader; consider progress indicators for >10MB files
- API timeout: 5s; long queries need UI loading indicator (ElLoading)
- Static asset path: `./` (relative); works in any deployment path

## Known Integration Points
- **Backend**: `/DailyReview/server/` endpoints (multiple PHP APIs)
- **Authentication**: Implicit via backend sessions (401 redirect on auth failure)
- **External OA**: https://oa.aizyun.com (secondary user data source, partially integrated)
- **Deployment**: Relative paths support any base URL; update baseURL in http.js for server changes
