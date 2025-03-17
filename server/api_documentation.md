# API接口文档

## 周目标接口 (WeekGoalAPI.php)
- 获取周目标：`GET/POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=get`
- 创建周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=create&executor_id=1&weekly_goal=完成项目模块开发&mondayDate=20231002`
- 更新周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=update&id=1&weekly_goal=修改后的目标内容`
- 删除周目标：`POST http://localhost/DailyReview/server/WeekGoalAPI.php?action=delete&id=1`

## 每日目标接口 (DayGoalAPI.php)
- 获取目标：`GET/POST http://localhost/DailyReview/server/DayGoalAPI.php?action=get&date=2023-10-01`
- 创建目标：`POST http://localhost/DailyReview/server/DayGoalAPI.php?action=create&date=2023-10-01&executor_id=1&goal_content=完成用户登录模块`
- 更新目标：`POST http://localhost/DailyReview/server/DayGoalAPI.php?action=update&id=1&goal_content=修改后的内容&executor_id=2`
- 标记完成：`POST http://localhost/DailyReview/server/DayGoalAPI.php?action=complete&id=1`

## 每日任务接口 (DayTaskAPI.php)
- 获取任务：`GET/POST http://localhost/DailyReview/server/DayTaskAPI.php?action=get&date=2023-10-01`
- 创建任务：`POST http://localhost/DailyReview/server/DayTaskAPI.php?action=create&date=2023-10-01&executor_id=1&task_content=开发用户注册功能&time_spent=120`
- 更新进度：`POST http://localhost/DailyReview/server/DayTaskAPI.php?action=update&id=1&task_content=更新后的任务内容&progress=50&time_spent=180`
- 删除任务：`POST http://localhost/DailyReview/server/DayTaskAPI.php?action=delete&id=1`

## 通用说明
1. 所有参数同时支持GET/POST方式传递
2. 数据返回格式为JSON
3. 更新/删除操作需要POST方法
4. 日期格式：YYYY-MM-DD（日接口）/YYYYMMDD（周接口）
5. 测试前请确保服务器已配置正确数据库连接



http://localhost/DailyReview/server/UserInfoAPI.php?action=get_departments
http://localhost/DailyReview/server/UserInfoAPI.php?action=get_users&department_id=2
http://localhost/DailyReview/server/UserInfoAPI.php?action=update_user&partner_name=xxx&department_id=xxx&position=xxx&is_active=x