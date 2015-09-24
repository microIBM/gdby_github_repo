CREATE TABLE `t_order_type_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `category_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '需要独立拆分的品类',
    `sku_numbers` varchar(255) NOT NULL DEFAULT '' COMMENT '需要独立拆分的sku',
    `city_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '配置该规则的城市，如果是0，表示全国通用',
    `score` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '优先级',
    `order_type_id` int(10) unsigned NOT NULL DEFAULT 1,
    `type_name` varchar(20) NOT NULL DEFAULT '' COMMENT '订单类型名',
    `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
    `created_time` int(11) unsigned NOT NULL DEFAULT 0,
    `updated_time` int(11) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `city_id` (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供应订单表'
