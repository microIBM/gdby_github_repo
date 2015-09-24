-- 创建商品快照表
-- author : liaoxianwen
-- datetime: 8.21
-- ------------------------------
CREATE TABLE `t_product_snapshot` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '快照自增id',
    `product_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
    `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类编号',
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家id',
    `unit_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计量单位id',
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
    `adv_words` varchar(255) NOT NULL DEFAULT '' COMMENT '广告语',
    `weight` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品权重',
    `spec` text NOT NULL COMMENT '商品规格信息',
    `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品价格',
    `market_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品市场价格',
    `sku_number` int(11) unsigned NOT NULL DEFAULT '0',
    `close_unit` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '结算单位id',
    `single_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结算单价',
    `is_round` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否在售卖价前面加上约字',
    `location_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '销售地区 代表对应的开放城市id',
    `storage` int(11) DEFAULT '-1' COMMENT '限购库存',
    `deposit` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '押金',
    `buy_limit` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每人限购多少',
    `line_id` text COMMENT '线路id',
    `visiable` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '1全部 2部分 3 全部不可见',
    `customer_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 普通用户 2 KA用户',
    `collect_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 预采 2 现采',
    `op_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作用户信息id',
    `customer_visiable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 全部，1 普通用户可见 2 KA客户可见',
    `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品状态0-禁用1-已通过2-待审',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品更新时间',
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `user_id` (`user_id`),
    KEY `op_user_id` (`op_user_id`),
    KEY `unit_id` (`unit_id`),
    KEY `title` (`title`),
    KEY `sku` (`sku_number`),
    KEY `location_id` (`location_id`),
    KEY `product_id` (`product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
