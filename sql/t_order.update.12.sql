ALTER TABLE `t_order` ADD COLUMN `deliver_fee` int unsigned NOT NULL DEFAULT 0 COMMENT '订单运费';
ALTER TABLE `t_order` ADD COLUMN `final_price` int unsigned NOT NULL DEFAULT 0 COMMENT '订单应付金额，综合商品总价，优惠金额运费等字段计算出的总价';
