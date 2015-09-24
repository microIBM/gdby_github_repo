-- ----------------------------
-- Table structure for t_location
-- ----------------------------
DROP TABLE IF EXISTS `t_line`;
CREATE TABLE `t_line` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `location_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应的地址编号',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '线路简称',
  `full_name` varchar(255) NOT NULL DEFAULT '' COMMENT '线路全称',
  `description` text NOT NULL DEFAULT '' COMMENT '线路具体描述',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `location_id` (`location_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='线路表';

INSERT INTO t_line(location_id, name, full_name, description, created_time, updated_time) VALUES
(804, '北京南站/开阳里', '北京南站/开阳里', '北京南站/开阳里', 1426780800, 1426780800),
(804, '菜市口/陶然亭/虎坊桥', '菜市口/陶然亭/虎坊桥', '菜市口/陶然亭/虎坊桥', 1426780800, 1426780800),
(804, '大望路/武圣路/双井', '大望路/武圣路/双井', '大望路/武圣路/双井', 1426780800, 1426780800),
(804, '方庄/蒲黄榆', '方庄/蒲黄榆', '方庄/蒲黄榆', 1426780800, 1426780800),
(804, '刘家窑/宋家庄', '刘家窑/宋家庄', '刘家窑/宋家庄', 1426780800, 1426780800),
(804, '潘家园/十里河', '潘家园/十里河', '潘家园/十里河', 1426780800, 1426780800),
(804, '新街口', '新街口', '新街口', 1426780800, 1426780800),
(804, '广渠门', '广渠门', '广渠门', 1426780800, 1426780800),
(804, '天坛', '天坛', '天坛', 1426780800, 1426780800),
(804, '永定门', '永定门', '永定门', 1426780800, 1426780800);
