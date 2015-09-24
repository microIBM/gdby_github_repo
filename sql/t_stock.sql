CREATE TABLE `t_stock` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `warehouse_id` int unsigned NOT NULL DEFAULT 0 COMMENT '仓库id',
    `sku_number` int unsigned NOT NULL DEFAULT 0 COMMENT 'sku货号',
    `in_stock` int NOT NULL DEFAULT 0 COMMENT 'wms提供的库存数据',
    `virtual_stock` int NOT NULL DEFAULT -1 COMMENT '虚拟库存，用来调整可以售卖的具体量,-1表示不做限制',
    `stock_locked` int NOT NULL DEFAULT 0 COMMENT '已经被订单锁定的库存，当下单时减，取消订单时会加',
    `exceed_limit` int NOT NULL DEFAULT 0 COMMENT '可超卖值',
    `created_time` int(11) unsigned NOT NULL DEFAULT 0,
    `updated_time` int(11) unsigned NOT NULL DEFAULT 0,
    `status` tinyint unsigned NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY sku_number(`sku_number`),
    KEY warehouse_id(`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='库存表';

