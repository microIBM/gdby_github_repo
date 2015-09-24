CREATE TABLE `t_pick_task` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `created_time` int(11) unsigned NOT NULL DEFAULT 0,
    `updated_time` int(11) unsigned NOT NULL DEFAULT 0,
    `status` tinyint unsigned NOT NULL DEFAULT 1,
    `wave_id` int unsigned NOT NULL DEFAULT 0 COMMENT '对应的波次id',
    `sku_count` int unsigned NOT NULL DEFAULT 0 COMMENt '分拣任务中包含的sku数，重复的sku不计',
    `site_src` int unsigned NOT NULL DEFAULT 1 COMMENT '1大厨2大果',
    `line_id` int unsigned NOT NULL DEFAULT 0 COMMENT '线路id',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='分拣任务表'
