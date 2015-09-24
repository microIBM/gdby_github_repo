-- ----------------------------------------
-- Table structure for t_potential_customer
-- ----------------------------------------
DROP TABLE IF EXISTS `t_potential_customer`;
CREATE TABLE `t_potential_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `shop_name` varchar(255) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` char(6) NOT NULL DEFAULT '' COMMENT '盐，加密用',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户角色',
  `province_id` int(11) NOT NULL DEFAULT '0' COMMENT '省份',
  `city_id` int(11) NOT NULL DEFAULT '0' COMMENT '城市',
  `county_id` int(11) NOT NULL DEFAULT '0' COMMENT '区县',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `upid` int(11) NOT NULL DEFAULT '0' COMMENT '总店uid',
  `invite_id` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人id',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否激活',
  `wechat_id` varchar(50) NOT NULL DEFAULT '' COMMENT '微信号',
  `site_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '所属站点:1大厨/2大果',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `remark` text NOT NULL COMMENT '备注',
  `shop_type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '餐饮类型',
  `is_link` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否连锁',
  `geo` varchar(255) NOT NULL DEFAULT '' COMMENT '地理位置信息',
  `geo_hash` varchar(255) NOT NULL DEFAULT '' COMMENT '地理位置hash',
  `line_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属线路编号',
  `last_order_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后下单日期',
  PRIMARY KEY (`id`),
  KEY `uname` (`name`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='潜在客户信息表';