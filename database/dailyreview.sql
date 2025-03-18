-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-03-15 10:08:48
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `dailyreview`
--

-- --------------------------------------------------------



-- --------------------------------------------------------

--
-- 表的结构 `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `executor_id` int(11) NOT NULL,
  `task_content` text NOT NULL,
  `progress` int(11) DEFAULT 0,
  `time_spent` decimal(10,2) DEFAULT 0.00,
  `is_new_goal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(5, '产品'),
(13, '大富'),
(3, '奇胜'),
(9, '总办'),
(1, '技术'),
(4, '投放'),
(11, '政委'),
(2, '游戏技术'),
(7, '用人'),
(12, '行政'),
(14, '观星'),
(8, '财务'),
(6, '选人'),
(10, '鲲鹏');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `partner_name` varchar(100) NOT NULL,
  `mode` varchar(50) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `partner_name`, `mode`, `department_id`, `position`, `is_active`) VALUES
(1, '杨杨', '拼搏模式', 13, '队长', 1),
(2, '郦一琦', '拼搏模式', 13, '助理', 1),
(3, '梅习勋', '正常模式', 13, '客服', 1),
(4, '杜佳琦', '正常模式', 13, '客服', 1),
(5, '蒋鑫', '正常模式', 13, '客服', 1),
(6, '徐丽琴', '正常模式', 13, '客服', 1),
(7, '刘志珍', '正常模式', 13, '客服', 1),
(8, '翁林涛', '正常模式', 13, '客服', 1),
(9, '赵艺徽', '正常模式', 13, '客服', 1),
(10, '徐缘康', '正常模式', 13, '客服', 1),
(11, '董陈刚', '拼搏模式', 1, '队长', 1),
(12, '郑燕飞', '拼搏模式', 1, '开发', 1),
(13, '王雪斌', '拼搏模式', 1, '测试', 1),
(14, '方旭', '拼搏模式', 1, '运维', 1),
(15, '陈苏熙', '拼搏模式', 2, '队长', 1),
(16, '曹方毅', '拼搏模式', 2, '开发', 1),
(17, '曹远浩', '拼搏模式', 2, '开发', 1),
(18, '李欣', '拼搏模式', 2, '开发', 1),
(19, '帅维城', '拼搏模式', 2, '开发', 1),
(20, '王威', '拼搏模式', 4, '队长', 1),
(21, '王斌', '拼搏模式', 4, '投手', 1),
(22, '张梁', '拼搏模式', 5, '队长', 1),
(23, '张锴楠', '拼搏模式', 5, '5', 1),
(24, '孙晓远', '拼搏模式', 6, '队长', 1),
(25, '章志雄', '拼搏模式', 7, '队长', 1),
(26, '杨秀玲', '正常模式', 8, '队长', 1),
(27, '何流芳', '正常模式', 8, '出纳', 1),
(28, '杨思涵', '正常模式', 8, '8', 1),
(29, '徐艳', '正常模式', 8, '8', 1),
(30, '潘楚涵', '正常模式', 8, '8', 1),
(31, '叶积建', '拼搏模式', 9, '跟进', 1),
(32, '王旭', '拼搏模式', 9, '风控', 1),
(33, '钱贵祥', '拼搏模式', 3, '队长', 1),
(34, '孔祥东', '拼搏模式', 3, '开发', 1),
(35, '王子金', '拼搏模式', 3, '开发', 1),
(36, '周杭民', '拼搏模式', 3, '开发', 1),
(37, '朱军丹', '拼搏模式', 3, '调研', 1),
(38, '王凯祥', '拼搏模式', 3, '调研', 1),
(39, '徐宜邦', '拼搏模式', 3, '调研', 1),
(40, '陶乐欣', '拼搏模式', 3, '调研', 1),
(41, '梁浩风', '拼搏模式', 10, '队长', 1),
(42, '钱星瑞', '正常模式', 1, '测试', 1),
(43, '王然', '正常模式', 1, '测试', 1),
(44, '朱唯冬', '正常模式', 1, '运维', 1),
(45, '谢国良', '正常模式', 2, '开发', 1),
(46, '梁嘉轩', '正常模式', 2, '开发', 1),
(47, '王子腾', '正常模式', 2, '开发', 1),
(48, '陶俊华', '正常模式', 2, '开发', 1),
(49, '刘肖', '正常模式', 4, '投手', 1),
(50, '王奖焱', '正常模式', 4, '投手', 1),
(51, '余佳浩', '正常模式', 4, '投手', 1),
(52, '张文俊', '正常模式', 4, '投手', 1),
(53, '占承帅', '正常模式', 4, '投手', 1),
(54, '朴曙明', '正常模式', 4, '投手', 1),
(55, '施晓虎', '正常模式', 4, '投手', 1),
(56, '杨楠', '正常模式', 5, '设计', 0),
(57, '朱靖恺', '正常模式', 5, '设计', 1),
(58, '解午刚', '正常模式', 5, '设计', 1),
(59, '左鹏', '正常模式', 5, '设计', 1),
(60, '陈凡希', '正常模式', 11, '11', 1),
(61, '童梅君', '正常模式', 6, '6', 1),
(62, '康琪琪', '正常模式', 6, '6', 1),
(63, '邵雨谦', '正常模式', 6, '6', 1),
(64, '王艺凝', '正常模式', 11, '11', 1),
(65, '张祎璇', '正常模式', 7, '7', 1),
(66, '朱清源', '正常模式', 8, '8', 1),
(67, '陈堯朴', '正常模式', 9, '9', 1),
(68, '董展', '正常模式', 3, '开发', 1),
(69, '梁超', '正常模式', 14, '商务', 1),
(70, '徐璠', '正常模式', 14, '数据', 1),
(71, '乃扎提', '正常模式', 4, '投手', 1),
(72, '秦涛', '正常模式', 3, '开发', 1),
(73, '李卓浩', '正常模式', 3, '开发', 1);

-- --------------------------------------------------------

--
-- 表的结构 `weekly_goals`
--

CREATE TABLE `weekly_goals` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `executor` varchar(50) NOT NULL,
  `executor_id` int(11) NOT NULL,
  `weekly_goal` text NOT NULL,
  'priority' int(5) DEFAULT 5 comment '优先级',
  `is_new_goal` tinyint(1) DEFAULT 0,
  `createdate` int(11) NOT NULL,
  `mondayDate` int(11) NOT NULL COMMENT '周一日期,通过这个判断出是否本周的周任务'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 表的结构 `daily_goals`
--

CREATE TABLE `daily_goals` (
  `id` int(11) NOT NULL,
  `weekly_goals_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `executor` varchar(50) NOT NULL,
  `executor_id` int(11) NOT NULL,
  `weekly_goal` text NOT NULL,
  `priority` int(5) DEFAULT 5 comment '优先级',
  `is_new_goal` tinyint(1) DEFAULT 0,
  `createdate` int(11) NOT NULL,
  `mondayDate` int(11) NOT NULL COMMENT '周一日期,通过这个判断出是否本周的周任务'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- 转储表的索引
--

--
-- 表的索引 `daily_goals`
--
ALTER TABLE `daily_goals`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `daily_tasks`
--
ALTER TABLE `daily_tasks`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- 表的索引 `weekly_goals`
--
ALTER TABLE `weekly_goals`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `daily_goals`
--
ALTER TABLE `daily_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- 使用表AUTO_INCREMENT `weekly_goals`
--
ALTER TABLE `weekly_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
