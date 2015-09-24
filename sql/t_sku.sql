-- ----------------------------
--  Table structure for `t_sku`
--  Author: liaoxianwen@dachuwang.com
-- ----------------------------
DROP TABLE IF EXISTS `t_sku`;
CREATE TABLE `t_sku` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类编号',
  `sku_number` varchar(255) NOT NULL DEFAULT '',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '产品内部备注',
  `spec` text NOT NULL,
    `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品状态0-禁用1-已通过2-待审',
  `error_code` int(11) unsigned NOT NULL DEFAULT '0',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品创建时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品更新时间',
    PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
    KEY `sku_number` (`sku_number`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
