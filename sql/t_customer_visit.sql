CREATE TABLE `t_customer_visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标识符',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_name` varchar(255) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `remarks` varchar(255) NOT NULL COMMENT '备注',
  `visit_date` int(11) NOT NULL DEFAULT '0' COMMENT '拜访日期',
  `status` tinyint(1) unsigned zerofill NOT NULL DEFAULT '1' COMMENT '状态',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `bd_id` int(11) NOT NULL DEFAULT '0' COMMENT 'bd_id',
  `is_potential` tinyint(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '是否是潜在客户表中的数据 1 是，  0 为 t_customer中的数据',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
