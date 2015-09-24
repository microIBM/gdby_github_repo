-- ----------------------------
-- Table structure for t_customer
-- ----------------------------
DROP TABLE IF EXISTS `t_customer`;
CREATE TABLE `t_customer` (
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
  `site_id` tinyint(4) NOT NULL DEFAULT '' COMMENT '所属站点:1 大厨/2大果',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  KEY `uname` (`name`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='客户信息表';
