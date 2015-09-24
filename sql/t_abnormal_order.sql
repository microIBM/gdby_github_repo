-- -------------------------------------
-- Table structure for t_abnormal_order
-- -------------------------------------
DROP TABLE IF EXISTS `t_abnormal_order`;
CREATE TABLE `t_abnormal_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `otype` varchar(255) NOT NULL DEFAULT '' COMMENT '异常单类型',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应的订单编号',
  `order_number` char(32) NOT NULL DEFAULT '' COMMENT '对应的订单编号',
  `site_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属系统',
  `city_id` int(11) NOT NULL DEFAULT '0' COMMENT '地区',
  `line_id` int(11) NOT NULL DEFAULT '0' COMMENT '线路',
  `shop_name` varchar(255) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '客户姓名',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '客户电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '客户地址',
  `deliver_date` int(11) NOT NULL DEFAULT '0' COMMENT '送货日期',
  `deliver_time` int(11) NOT NULL DEFAULT '0' COMMENT '送货时间',
  `product_id` int(11) NOT NULL DEFAULT '0' COMMENT '异常单内容产品ID',
  `product_name` varchar(255) NOT NULL DEFAULT '' COMMENT '异常单内容产品名称',
  `reason` text NOT NULL DEFAULT '' COMMENT '异常原因',
  `solution` text NOT NULL DEFAULT '' COMMENT '解决方案',
  `creator` varchar(50) NOT NULL DEFAULT '' COMMENT '创建人',
  `creator_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `order_number` (`order_number`),
  KEY `site_id` (`site_id`),
  KEY `city_id` (`city_id`),
  KEY `line_id` (`line_id`),
  KEY `otype` (`otype`),
  KEY `product_id` (`product_id`),
  KEY `creator_id` (`creator_id`),
  KEY `mobile` (`mobile`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT=' 异常单表';
