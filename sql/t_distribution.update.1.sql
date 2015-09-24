ALTER TABLE `t_distribution` 
ADD COLUMN `order_type` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '配送单类型，同订单类型一致。' AFTER `city_id`;

