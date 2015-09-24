ALTER TABLE `t_order` ADD COLUMN `pay_reduce` int(11) NOT NULL DEFAULT 0 COMMENT '支付减免金额，单位为分' AFTER `pay_status`;
