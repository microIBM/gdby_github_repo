-- ---------------------------------------
-- Table structure for t_order_update
-- ---------------------------------------
DROP TABLE IF EXISTS `t_order_update`;
CREATE TABLE `t_order_update` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '母订单id',
    `modify_time` int(11) NOT NULL DEFAULT '0' COMMENT '订单状态修改时间',
    `order_status` int(11) NOT NULL DEFAULT '0' COMMENT '订单所处的状态',
    `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '本条记录最后插入时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_id` (`order_id`,`order_status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='workflow_log数据静态对应表';