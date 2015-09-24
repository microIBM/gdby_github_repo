CREATE TABLE `t_promo_event` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(200) COMMENT 'title',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '是否有效',
    `site_id` tinyint NOT NULL DEFAULT 0 COMMENT '站点id',
    `location_id` int(11) NOT NULL DEFAULT 0 COMMENT '促销活动城市',
    `start_time` int(11) NOT NULL DEFAULT 0 COMMENT '活动开始时间',
    `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '活动开始时间',
    `latest_deliver_time` int(11) NOT NULL DEFAULT 0 COMMENT '最晚配送时间',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='活动表'
