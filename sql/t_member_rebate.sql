-- -----------------------------------
-- Table structure for t_member_rebate
-- -----------------------------------
DROP TABLE IF EXISTS `t_member_rebate`;
CREATE TABLE `t_member_rebate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户ID',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `category_name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `rebate` int(11) NOT NULL DEFAULT '0' COMMENT '用户折扣，0~100',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作者ID',
  `operator` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='会员折扣表';
