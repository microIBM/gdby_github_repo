-- -------------------------------------
-- Table structure for t_product_price
-- -------------------------------------
DROP TABLE IF EXISTS `t_product_price`;
CREATE TABLE `t_product_price` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `product_name` varchar(255) NOT NULL DEFAULT '' COMMENT '产品名称',
  `sku_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'sku货号',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类编号',
  `location_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '销售地区 代表对应的开放城市id',
  `dest_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '普通客户商品价格',
  `dest_ka_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'KA客户商品价格',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `operator_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作者ID',
  `operator` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `sku_number` (`sku_number`),
  KEY `category_id` (`category_id`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='产品价格表';
