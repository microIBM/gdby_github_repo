-- 优惠券通过规则控制器来生成的
CREATE TABLE `t_coupons` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(200) COMMENT 'title',
    `coupon_rule_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规则id',
    `category_ids` text  COMMENT '品类ids',
    `product_ids` text  COMMENT '商品ids',
    `location_id` int(11) NOT NULL DEFAULT 0 COMMENT '城市id',
    `site_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属系统',
    `visiable` int(11) NOT NULL DEFAULT 0 COMMENT '0全部可见 1登录可见',
    `coupon_nums` int(11) NOT NULL DEFAULT 0 COMMENT '发放数量',
    `coupon_used_nums` int(11) NOT NULL DEFAULT 0 COMMENT '已用数量',
    `line_ids` text COMMENT '所属线路',
    `coupon_type` int(11) NOT NULL DEFAULT 1 COMMENT '1 是系统发券,2用户下订单给予的优惠券, 3自行领券，4 特殊要求',
    `coupon_description` varchar(200) NOT NULL DEFAULT '' COMMENT '若是特殊要求的类型，最好填写描述',
    `valid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生效时间',
    `invalid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '失效时间',
    `status` tinyint NOT NULL DEFAULT 2 COMMENT '是否有效',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY ruleId(`coupon_rule_id`),
    KEY locationId(`location_id`),
    KEY siteId(`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='优惠券库存池'
-- end
