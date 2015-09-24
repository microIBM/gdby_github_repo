CREATE TABLE `t_warehouse` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `warehouse_id` int unsigned NOT NULL DEFAULT 0 COMMENT '仓库id',
    `warehouse_name` varchar(100) NOT NULL DEFAULT '' COMMENT '仓库名',
    `created_time` int(11) unsigned NOT NULL DEFAULT 0,
    `updated_time` int(11) unsigned NOT NULL DEFAULT 0,
    `status` tinyint unsigned NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY warehouse_id(warehouse_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='仓库表';

