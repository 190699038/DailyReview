CREATE TABLE IF NOT EXISTS `project_groups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '自增主键',
  `group_code` VARCHAR(50) NOT NULL UNIQUE COMMENT '对应 value (如: US1, AIFN)',
  `group_name` VARCHAR(100) NOT NULL COMMENT '对应 label (如: 美国1, AI赋能)',
  `category` ENUM('Region', 'Department', 'Technical', 'System', 'Other') DEFAULT 'Other' COMMENT '分组类型：地区、部门、技术、系统、其他',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1-启用, 0-禁用',
  `sort_order` INT DEFAULT 0 COMMENT '排序权值，数值越大越靠前',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX `idx_category` (`category`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目组/业务线配置表';

INSERT INTO `project_groups` (`group_code`, `group_name`, `category`, `sort_order`) VALUES
-- 系统类
('ALL', '所有地区', 'System', 99),
('OA', 'OA系统', 'System', 100),

-- 地区类
('US1', '美国1', 'Region', 90),
('US2', '美国2', 'Region', 89),
('US3', '美国3', 'Region', 88),
('US4', '美国4', 'Region', 87),
('OZ', '欧洲', 'Region', 86),
('ZD', '中东', 'Region', 85),
('BR1', '巴西1', 'Region', 84),
('BR2', '巴西2', 'Region', 83),
('MX', '墨西哥', 'Region', 82),
('PE', '秘鲁', 'Region', 81),
('CL', '智利', 'Region', 80),
('AU', '澳大利亚', 'Region', 79),
('CA', '加拿大', 'Region', 78),
('PH', '菲律宾', 'Region', 77),

-- 技术与业务职能类
('QSJS', '奇胜-技术', 'Technical', 70),
('QSDY', '奇胜-调研', 'Technical', 69),
('QSLL', '奇胜-流量', 'Technical', 68),
('YXJS', '游戏技术', 'Technical', 67),
('AIFN', 'AI赋能', 'Technical', 66),
('MVP', 'MVP', 'Technical', 64),

-- 后勤与管理类
('KF', '客服', 'Department', 50),
('XR', '选人', 'Department', 49),
('YR', '用人', 'Department', 48),
('YW', '运维', 'Department', 47),
('FK', '风控', 'Department', 46),
('WH', '文化', 'Department', 45),
('PX', '培训', 'Department', 44),
('CW', '财务', 'Department', 43),
('TF', '投放', 'Department', 42),
('DF', '支付', 'Department', 41),

-- 其它
('QT', '其它', 'Other', 0);
