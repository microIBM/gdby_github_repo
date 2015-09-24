-- -------------------------------------
-- Table structure for t_consult
-- -------------------------------------
DROP TABLE IF EXISTS `t_consult`;
CREATE TABLE `t_consult` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `ctype` int(11) NOT NULL DEFAULT '0' COMMENT '咨询类型',
  `source` int(11) NOT NULL DEFAULT '0' COMMENT '咨询来源',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '电话',
  `qq` varchar(255) NOT NULL DEFAULT '' COMMENT 'qq',
  `wechat` varchar(255) NOT NULL DEFAULT '' COMMENT '微信',
  `channel` varchar(255) NOT NULL DEFAULT '' COMMENT '了解渠道',
  `company_name` varchar(255) NOT NULL DEFAULT '' COMMENT '企业名',
  `company_area` varchar(255) NOT NULL DEFAULT '' COMMENT '企业地区',
  `company_address` varchar(255) NOT NULL DEFAULT '' COMMENT '企业地址',
  `content` text NOT NULL DEFAULT '' COMMENT '咨询内容',
  `solution` text NOT NULL DEFAULT '' COMMENT '解决方案',
  `creator` varchar(50) NOT NULL DEFAULT '' COMMENT '创建人',
  `creator_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `ctype` (`ctype`),
  KEY `creator_id` (`creator_id`),
  KEY `mobile` (`mobile`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='咨询单表';
