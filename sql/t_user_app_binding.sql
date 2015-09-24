CREATE TABLE `t_user_app_binding` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `app_type_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'app type 1大厨 2大果 3crm',
    `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
    `imei_code` varchar(50) NOT NULL DEFAULT '' COMMENT '设备imei码',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '',
    `created_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY user_id(`user_id`),
    KEY app_type_id(`app_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供应订单表'
