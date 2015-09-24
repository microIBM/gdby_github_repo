CREATE TABLE `t_statics_customer_cate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '城市id',
  `line_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '线路id',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `customer_type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户类型',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '分类路径',
  `order_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单订单数',
  `sale_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '下单金额',
  `sign_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '签收订单数',
  `sign_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '签收金额',
  `data_date` varchar(20) NOT NULL DEFAULT '0000-00-00' COMMENT '记录时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `data_date` (`data_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户维度销售信息表';