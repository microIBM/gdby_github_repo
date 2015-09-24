-- 关注用户关注列表
DROP TABLE IF EXISTS `t_follow_with_interest`;
CREATE TABLE `t_follow_with_interest` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关注id',
    `product_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注的商品id',
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注的用户id',
    `status` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注状态：1位关注该商品，0为不关注',
    `created_time` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注信息创建时间',
    `updated_time` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注更新时间',
    PRIMARY KEY(`id`),
    KEY `product_id` (`product_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
