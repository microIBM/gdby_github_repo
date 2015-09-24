ALTER TABLE `t_order_type_config` ADD COLUMN `collect_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 预采 2现采' AFTER `score`;
