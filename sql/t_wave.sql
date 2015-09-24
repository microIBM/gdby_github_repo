CREATE TABLE `t_wave` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `created_time` int(11) unsigned NOT NULL DEFAULT 0,
    `updated_time` int(11) unsigned NOT NULL DEFAULT 0,
    `status` tinyint unsigned NOT NULL DEFAULT 1,
    `wave_type` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '波次类型，1自动，2手动',
    `order_count` int unsigned NOT NULL DEFAULT 0 COMMENT '波次中包含的订单数目',
    `line_count` int unsigned NOT NULL DEFAULT 0 COMMENT '波次中包含的订单条目数，即在详单中共有多少行',
    `total_count` int unsigned NOT NULL DEFAULT 0 COMMENT 'sum sku*count',
    `site_src` int unsigned NOT NULL DEFAULT 1 COMMENT '1大厨2大果',
    `pick_task_created` int unsigned NOT NULL DEFAULT 0 COMMENT '0未生成，1已生成',
    `city_id` int unsigned NOT NULL DEFAULT 0 COMMENT '城市id',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='波次表'
