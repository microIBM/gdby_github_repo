ALTER TABLE `t_line` ADD COLUMN `warehouse_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '所属仓库编号';
ALTER TABLE `t_line` ADD COLUMN `warehouse_name` varchar(255) NOT NULL DEFAULT '' COMMENT '所属仓库名称';
