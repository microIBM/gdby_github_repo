-- ----------------------------------
-- Table structure for t_distribution
-- ----------------------------------
DROP TABLE IF EXISTS `t_distribution`;
CREATE TABLE `t_distribution` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dist_number` char(32) NOT NULL DEFAULT '' COMMENT '配送单号',
  `remarks` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `total_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应收总金额',
  `deal_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实收总金额',
  `site_src` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1大厨网，2 大果网',
  `line_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '线路ID',
  `deliver_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '配送日期',
  `deliver_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '配送时间',
  `order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总单数',
  `line_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总行数',
  `sku_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总件数',
  `total_distance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '预估总里程数',
  `begin_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '配送开始时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '配送结束时间',
  `creator` varchar(50) NOT NULL DEFAULT '' COMMENT '创建者',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '状态',
  `is_printed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否打印：1已打印，0未打印',
  `city_id` int(11) NOT NULL DEFAULT '0' COMMENT '城市ID',
  PRIMARY KEY (`id`),
  KEY `dist_number` (`dist_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配送单表';
