ALTER TABLE `t_order_detail` ADD COLUMN `rebate` int unsigned NOT NULL default 100;
ALTER TABLE `t_order_detail` ADD COLUMN `origin_price` int unsigned NOT NULL DEFAULT 0;
