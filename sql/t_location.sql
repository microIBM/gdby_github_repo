-- ----------------------------
-- Table structure for t_location
-- ----------------------------
DROP TABLE IF EXISTS `t_location`;
CREATE TABLE `t_location` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `upid` int(11) NOT NULL DEFAULT '0' COMMENT '上级ID',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '路径',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `full_name` varchar(255) NOT NULL DEFAULT '' COMMENT '全名',
  `level` int(11) NOT NULL DEFAULT '1' COMMENT '级别',
  `is_leaf` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否叶子节点',
  `latitude` double NOT NULL COMMENT '纬度',
  `longtitude` double NOT NULL COMMENT '经度',
  `geohash` varchar(255) NOT NULL DEFAULT '' COMMENT '',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `upid` (`upid`),
  KEY `name` (`name`),
  KEY `geohash` (`geohash`),
  KEY `path` (`path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='地域表';
