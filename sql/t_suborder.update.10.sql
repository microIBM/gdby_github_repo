-- 增加订单来源
ALTER TABLE `t_suborder` ADD COLUMN `order_resource` tinyint(2) unsigned NOT NULL DEFAULT '3' COMMENT '1 ios 2 android 3 chu 4 mall';
