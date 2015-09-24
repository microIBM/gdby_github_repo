ALTER TABLE `t_order` ADD COLUMN `order_type` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '1.普通订单;2.冻品订单;';
