INSERT INTO `t_suborder` (SELECT * FROM t_order);
ALTER TABLE `t_suborder` ADD COLUMN `order_id` int unsigned NOT NULL DEFAULT 0 COMMENT '母订单id';
UPDATE `t_suborder` SET order_id = id;
