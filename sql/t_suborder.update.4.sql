ALTER TABLE `t_suborder` ADD COLUMN `service_fee_rate` int unsigned NOT NULL DEFAULT 0 COMMENT '服务费率';
ALTER TABLE `t_suborder` ADD COLUMN `service_fee` int unsigned NOT NULL DEFAULT 0 COMMENT '服务费';
