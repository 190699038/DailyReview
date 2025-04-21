
use dailyReview;



--
-- 表的索引 `daily_tasks`
--
ALTER TABLE `daily_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_oa_taskid_yesterday` (`oa_taskid`);

--
-- 表的索引 `daily_tasks_today`
--
ALTER TABLE `daily_tasks_today`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_executor_date` (`executor_id`,`date`),
  ADD KEY `idx_monday_date` (`mondayDate`),
  ADD KEY `index_oa_today_taskid` (`oa_taskid`);

--
-- 表的索引 `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD KEY `group_id` (`group_id`);

--
-- 表的索引 `today_target`
--
ALTER TABLE `today_target`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_date` (`report_date`),
  ADD KEY `idx_report_date` (`report_date`,`id`),
  ADD KEY `idx_message_prefix` (`message`(255));

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
-- 使用表AUTO_INCREMENT `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7047;

--
-- 使用表AUTO_INCREMENT `daily_tasks_today`
--
ALTER TABLE `daily_tasks_today`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=1106;

--
-- 使用表AUTO_INCREMENT `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 使用表AUTO_INCREMENT `today_target`
--
ALTER TABLE `today_target`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- 使用表AUTO_INCREMENT `weekly_goals`
--
ALTER TABLE `weekly_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=455;

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