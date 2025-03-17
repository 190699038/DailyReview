#  目标:实现周目标、日目标、日任务管理系统服务器接口，使用PHP来实现 

# MYSQL数据库表结构
> -- 创建周任务表
CREATE TABLE weekly_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    executor VARCHAR(50) NOT NULL,
    executor_id INT NOT NULL,
    weekly_goal TEXT NOT NULL,
    is_new_goal BOOLEAN DEFAULT FALSE,
    createdate int NOT NULL,
    mondayDate int NOT NULL comment '周一日期,通过这个判断出是否本周的周任务',
);

-- 创建日目标表
CREATE TABLE daily_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    executor_id INT NOT NULL,
    goal_content TEXT NOT NULL,
    status TEXT DEFAULT "研发中",
    is_new_goal BOOLEAN DEFAULT FALSE
);

-- 创建当日任务表
CREATE TABLE daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    executor_id INT NOT NULL,
    task_content TEXT NOT NULL,
    progress INT DEFAULT 0,
    time_spent INT DEFAULT 0,
    is_new_goal BOOLEAN DEFAULT FALSE
);

-- 创建部门表
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE
);
-- 创建用户表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(100) NOT NULL,
    mode VARCHAR(50) NOT NULL,
    department_id INT NOT NULL,
    position VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);



## 0. 项目简介
> 本项目是一个基于PHP的目标管理系统，使用MySQL数据库存储数据。
> PHP接口需要同时支持POST和GET请求。支持跨域 

## 1. 周目标  
### 1.1 周目标的增删改查 WeekGoalAPI.php 
##### 1.1.1 查询weekly_goals表中的数据，通过PHP获取本周周一到日期，查询weekly_goals表中该周的周目标,需要通过查询结果，联表查询users和departments,获取到伙伴名称(partner_name)和部门名称（department_name）
##### 1.1.2 插入weekly_goals表中的数据，上传字段包括executor_id、weekly_goal、mondayDate、is_new_goal，通过executor_id，查询users和departments,获取到伙伴名称(executor)和部门ID（department_id），createtime由PHP来自动生成(如:20250310) 
##### 1.1.3 更新daily_goals表中的数据，通过id来更新数据，可以更新 weekly_goal和executor_id
##### 1.1.4 删除daily_goals表中的数据，通过id来删除数据


## 2. 日目标  
### 2.1 日目标的增删改查 DayGoalAPI.php
##### 2.1.1 查询daily_tasks表中的数据，通过前端上传的日期，查询daily_goals表中当日的日目标。
##### 2.1.2 插入daily_goals表中的数据，上传字段包括date(DATE-mysql)、executor_id、goal_content和is_new_goal
##### 2.1.3 更新daily_goals表中的数据，通过id来更新数据，可以更新 goal_content和executor_id 
##### 2.1.4 删除daily_goals表中的数据，通过id来删除数据


## 3. 日任务
### 3.1 日任务的增删改查 DayTaskAPI.php
##### 3.1.1 查询daily_tasks表中的数据，通过前端上传的日期，查询daily_tasks表中当日的日任务。
##### 3.1.2 插入daily_tasks表中的数据，上传字段包括date(DATE-mysql)、executor_id、task_content、is_new_task、progress、time_spent，如果上传的time_spent小于等于0，该条记录不入库
##### 3.1.3 更新daily_tasks表中的数据，通过id来更新数据，可以更新 task_content、is_new_task、progress、time_spent，如果上传的time_spent
##### 3.1.4 删除daily_tasks表中的数据，通过id来删除数据


## 4. 用户信息UserInfoAPI.php
### 4.1 查询users表中的数据，通过前端上传的部门ID，查询users表中该部门的伙伴信息(is_active=1)
### 4.2 插入users表中的数据，上传字段包括partner_name、mode、department_id、position 新增的用户is_active 默认我true 
### 4.3 更新users表中的数据，通过id来更新数据，可以更新 partner_name、mode、department_id、position和is_active(0或1)
### 4.4 查询部门信息departments表中的数据，查询departments表中所有的部门信息