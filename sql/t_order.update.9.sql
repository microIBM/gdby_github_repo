ALTER TABLE `t_order` ADD COLUMN `sale_id` int(11) NOT NULL DEFAULT 0 COMMENT '下单时客户关联销售id';
ALTER TABLE `t_order` ADD COLUMN `sale_role` int(11) NOT NULL DEFAULT 0 COMMENT '下单时客户关联销售id';
