CREATE TABLE `t_order_detail_weight` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL DEFAULT '0',
        `sub_order_id` int(11) NOT NULL DEFAULT '0',
        `order_detail_id` int(11) NOT NULL DEFAULT '0',
        `weight` decimal(10,3) NOT NULL DEFAULT '0.000',
        `created_time` int(11) NOT NULL DEFAULT '0',
        `updated_time` int(11) NOT NULL DEFAULT '0',
        `status` int(11) NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

