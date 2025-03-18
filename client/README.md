# Vue 3 + Vite

This template should help get you started developing with Vue 3 in Vite. The template uses Vue 3 `<script setup>` SFCs, check out the [script setup docs](https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup) to learn more.

Learn more about IDE Support for Vue in the [Vue Docs Scaling up Guide](https://vuejs.org/guide/scaling-up/tooling.html#ide-support).

# 一. 用户信息 - 程序启动时，默认加载
## 1.1 接口  /DailyReview/server/UserInfoAPI.php?action=get_users&department_id=部门ID， 
### 1.1.1 department_id 从localStorege中获取，key值为department_id_cache,获取不到默认值为2
### 1.1.2 接口说明 返回JSON数据， 主要字段 {"id":16,"partner_name":"曹方毅","mode":"拼搏模式","department_id":2,"position":"开发","is_active":1,"department_name":"游戏技术"} 
### 1.1.2  信息保存到localStorge中， 键为departments_cache， 值为JSON数据

# 二. 部门设置 -SystemSetting.vue
## 0. 先删除部门信息页面的组建 
## 1. 部门信息获取和展示
### 1.1 接口 /DailyReview/server/UserInfoAPI.php?action=get_departments
##### 1.1.1 接口说明 返回JSON数据， 主要字段 id和department_name 
### 1.2 展示 
####   1.2.1 页面展示,展示一个下拉框， 下拉框的选项是部门信息， 选项的值是部门id， 选项的文本是部门名称

## 2. 部门用户信息 
### 2.1 用户数据
#### 2.1.1 departments_cache 中获取本地缓存数据，如果数据为空 见 2.1.2 
#### 2.1.2 接口 /DailyReview/server/UserInfoAPI.php?action=get_users&department_id=部门ID(department_id 从localStorege中获取，key值为department_id_cache,获取不到默认值为2) ， 获取到数据后,  信息保存到localStorge中， 键为departments_cache
##### 2.1.2.1 接口解析，json数组，单个json结构  {"id":16,"partner_name":"曹方毅","mode":"拼搏模式","department_id":2,"position":"开发","is_active":1,"department_name":"游戏技术"}  
### 2.2 数据展示 
#### 2.2.1 页面展示,展示一个表格， 表格的列是部门信息， 用户名-partner_name  部门-department_name  职位-position   操作-修改
#### 2.2.1.1 修改操作，点击修改，弹出一个模态框，模态框中显示用户信息，用户信息可以修改，修改后点击保存，保存后，刷新页面
#### 2.2.1.2 API接口  http://localhost/DailyReview/server/UserInfoAPI.php?action=update_user&partner_name=xxx&department_id=xxx&position=xxx&is_active=x&id=x
### 2.3 添加用户和编辑，添加在下拉框下面加入一个按钮
#### 2.3.1 修改操作，点击修改，弹出一个模态框，模态框中显示用户信息，用户信息可以修改，修改后点击保存，保存后，刷新页面
#### 2.3.2 API接口  http://localhost/DailyReview/server/UserInfoAPI.php?action=add_user&partner_name=xxx&department_id=xxx&position=xxx&mode=xxx&is_active=1
#### 2.3.3 添加成功后，自动执行 2.1.2 接口



# 二. 周目标管理 -WeekGoal.vue
## 1. 周目标获取和展示
### 1.1  周目标获取接口和格式
#### 1.1.1 周目标获取接口 /DailyReview/server/WeekGoalAPI.php?action=get&mondayDate=20250317(星期一日期)&department_id=部门ID(department_id 从localStorege中获取，key值为department_id_cache,获取不到默认值为2)
#### 1.1.2 周目标获取接口返回格式 JSON数据，单条数据格式为:
>{"id":1001,"department_id":2,"executor":"陈苏熙","executor_id":15,"weekly_goal":"完成订单模块的接口性能优化","is_new_goal":1,"createdate":20250317,"mondayDate":20250317,"partner_name":"陈苏熙","department_name":"游戏技术"}
### 1.2 周目标展示,表格展示，样式参考SystemSetting.vue中的表单 
#### 1.2.1 表头和字段解释: 姓名:executor 周目标:weekly_goal  部门 department_name 本周内新增需求-is_new_goal=1 创建日期-createdate 操作:修改,删除, 特别注意:当is_new_goal=1时，本列数据为#FFF3CE ，否则为正常颜色
## 2. 周目标添加和修改和删除
### 2.1 周目标添加和修改, 点击添加按钮，弹出一个模态框
### 2.2 字段为 department-部门, executor-执行人 , weekly_goal-周目标, is_new_goal-下拉框, mondayDate-周一日期(下拉框 本周一日期、上周一日期、下周一日期)
### 2.3 保存按钮, 点击保存按钮，保存周目标（department_id和executor_id默认必须上传的）
> 新增:DailyReview/server/WeekGoalAPI.php?action=create&mondayDate=20250317&department_id=2&department_name=游戏技术&executor=陈苏熙&executor_id=15&weekly_goal=完成订单模块的接口性能优化&is_new_goal=0
> 更新:DailyReview/server/WeekGoalAPI.php?action=create&mondayDate=20250317&department_id=2&department_name=游戏技术&executor=陈苏熙&executor_id=15&weekly_goal=完成订单模块的接口性能优化&is_new_goal=0&id=1
> 删除:DailyReview/server/WeekGoalAPI.php?action=delete&id=1







