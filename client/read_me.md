# 周日任务校对系统

## 系统级别说明
### 1. 系统架构
> a. 前端：VUE3.0 构建系统 主要3个模块，顶部导航栏 - 项目组周目标-WeekGoal.vue、个人日目标&日任务-DailyGole.vue、系统设置-SystemSetting.vue
> b. 帮我搭建基础框架, 我只需要写业务逻辑, 如基础表格、卡片控件、按钮样式、HTTP请求模块(/DailyReview/server/,域名可配置)
> c. 在当前目录下创建工程





## 模块-周目标
### 1. API参考接口
> 获取周目标：`GET/POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=get`
> 创建周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=create&executor_id=1&weekly_goal=完成项目模块开发&mondayDate=20231002`
> 更新周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=update&id=1&weekly_goal=修改后的目标内容`
> 删除周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=delete&id=1`

###






