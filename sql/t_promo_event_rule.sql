CREATE TABLE `t_promo_event_rule` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `rule_type` int(11) NOT NULL DEFAULT 0 COMMENT '规则类型，可能会有满减，连续下单减之类的',
    `rule_json` text COMMENT '规则的json，根据各种规则进行定制',
    `promo_event_id` int NOT NULL DEFAULT 0 COMMENT '',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '是否有效',
    `site_id` tinyint NOT NULL DEFAULT 0 COMMENT '站点id',
    `location_id` int(11) NOT NULL DEFAULT 0 COMMENt '促销活动城市',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='活动规则表'
