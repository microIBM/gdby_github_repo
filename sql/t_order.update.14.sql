ALTER TABLE `t_order` ADD COLUMN `pay_type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '支付方式：0货到付款（默认），1微信支付';
ALTER TABLE `t_order` ADD COLUMN `pay_status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '支付状态：-1支付失败，0未支付，1已支付';
