-- ---------------------------------------
-- Table structure for t_complaint_content
-- ---------------------------------------
DROP TABLE IF EXISTS `t_complaint_content`;
CREATE TABLE `t_complaint_content` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '投诉id',
    `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单id',
    `product_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '货物id',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '货物名称',
    `quantity` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '总数量',
    `price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单价',
    `sum_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单品的总价格',
    `spec` text NOT NULL DEFAULT '' COMMENT '单品的规格描述，存储json',
    `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
    `created_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `cid` (`cid`),
    KEY `product_id` (`product_id`),
    KEY `order_id` (`order_id`),
    KEY `created_time` (`created_time`),
    KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='投诉内容表';
