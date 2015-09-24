-- 用户优惠券
CREATE TABLE `t_customer_coupons` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `coupon_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规则id',
    `customer_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '客户id',
    `coupon_code` char(32) NOT NULL DEFAULT '' COMMENT '券码',
    `coupon_nums` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放数量',
    `coupon_used_nums` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已用数量',
    `coupon_rule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '规则id',
    `minus_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '免多少',
    `require_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '满多少',
    `valid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生效时间',
    `invalid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '失效时间',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '过期就失效0，1 就是未使用,2数量用完即置为已使用',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY couponId(`coupon_id`),
    KEY ruleId(`coupon_rule_id`),
    KEY customerId(`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户优惠券'
-- end
