CREATE TABLE IF NOT EXISTS `chen_yaopu_review` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` VARCHAR(8) NOT NULL COMMENT '日期 YYYYMMDD',
  `purpose` TEXT COMMENT '目的',
  `initiator` VARCHAR(255) COMMENT '发起人',
  `participants` VARCHAR(500) COMMENT '参与人',
  `conclusion` TEXT COMMENT '结论(富文本HTML)',
  `screenshot_url` VARCHAR(500) COMMENT '截图URL',
  `content` TEXT COMMENT '结论纯文本',
  `around_goal` VARCHAR(255) COMMENT '围绕目标',
  `next_step` TEXT COMMENT '下一步',
  `valuable` TINYINT(1) DEFAULT 2 COMMENT '有价值: 1=有价值, 0=无价值, 2=常规会议',
  `value_content` TEXT COMMENT '价值体现',
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评审记录表';
