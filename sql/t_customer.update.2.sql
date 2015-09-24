ALTER TABLE `t_customer` ADD COLUMN `shop_type` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '餐饮类型';
ALTER TABLE `t_customer` ADD COLUMN `is_link` int(11) unsigned NOT NULL DEFAULT 0 COMMENT  '是否连锁';
ALTER TABLE `t_customer` ADD COLUMN `geo` varchar(255)  NOT NULL DEFAULT '' COMMENT '地理位置信息';
ALTER TABLE `t_customer` ADD COLUMN `geo_hash` varchar(255)  NOT NULL DEFAULT '' COMMENT  '地理位置hash';
