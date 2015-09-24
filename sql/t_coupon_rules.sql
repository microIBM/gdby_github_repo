-- 优惠券规则控制器
CREATE TABLE `t_coupon_rules` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(200) COMMENT 'title',
    `rule_type` int(11) NOT NULL DEFAULT 1 COMMENT '优惠券规则',
    `require_amount` int(11) NOT NULL DEFAULT 0 COMMENT '满多少钱',
    `minus_amount` int(11) NOT NULL DEFAULT 0 COMMENT '减多少钱',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '是否有效',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='优惠券规则器'
-- end
