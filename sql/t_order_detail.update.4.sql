ALTER TABLE `t_order_detail` ADD COLUMN `actual_quantity` int unsigned NOT NULL DEFAULT 0 COMMENT  '实收数量';
ALTER TABLE `t_order_detail` ADD COLUMN `actual_price` int unsigned NOT NULL DEFAULT 0 COMMENT  '实收单价';
ALTER TABLE `t_order_detail` ADD COLUMN `actual_sum_price` int unsigned NOT NULL DEFAULT 0 COMMENT  '实收总价';
