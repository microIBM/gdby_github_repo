ALTER TABLE `t_order_detail` ADD COLUMN `suborder_id` int unsigned NOT NULL DEFAULT 0 COMMENT '子订单id';
UPDATE `t_order_detail` SET `suborder_id` = `order_id`;
