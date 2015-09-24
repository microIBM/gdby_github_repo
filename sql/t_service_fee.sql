CREATE TABLE `t_service_fee` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `customer_type` int unsigned NOT NULL DEFAULT 0 COMMENT '客户类型',
    `customer_type_name` varchar(20) NOT NULL DEFAULT '' COMMENT '客户类型名',
    `fee_rate` int unsigned NOT NULL DEFAULT 0 COMMENT '服务费率',
    `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='服务费表'
