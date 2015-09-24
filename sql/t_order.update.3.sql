ALTER TABLE `t_order` ADD COLUMN `deliver_date` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '配送日期';
ALTER TABLE `t_order` MODIFY COLUMN `deliver_time` int unsigned NOT NULL DEFAULT 0 COMMENT  '配送时间段';
