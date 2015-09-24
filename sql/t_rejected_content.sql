-- ---------------------------------------
-- Table structure for t_rejected_content
-- ---------------------------------------
DROP TABLE IF EXISTS `t_rejected_content`;
CREATE TABLE `t_rejected_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rejected_id` int(11) NOT NULL DEFAULT '0' COMMENT '退货退款单id',
  `product_id` int(11) NOT NULL DEFAULT '0' COMMENT '产品id',
  `product_number` int(11) NOT NULL DEFAULT '0' COMMENT '产品编号',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '一级分类',
  `name` varchar(255) NOT NULL COMMENT '产品名称',
  `sku_number` varchar(255) NOT NULL COMMENT 'sku编号',
  `quantity` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '单价',
  `sum_price` int(11) NOT NULL DEFAULT '0' COMMENT '总价',
  `spec` text NOT NULL COMMENT '规格 ',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8

