CREATE TABLE `t_deliver_fee` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `free_amount` int unsigned NOT NULL DEFAULT 0 COMMENT '最小免运费额度',
    `fee` int unsigned NOT NULL DEFAULT 0 COMMENT '运费',
    `city_id` int unsigned NOT NULL DEFAULT 0 COMMENT '城市id',
    `site_id` int unsigned NOT NULL DEFAULT 0 COMMENT '站点id',
    `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY city_id(city_id),
    KEY site_id(site_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='运费表'
