-- ----------------------------
-- Table structure for t_privilege
-- ----------------------------
DROP TABLE IF EXISTS `t_privilege`;
CREATE TABLE `t_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '权限名称',
  `module` varchar(30) NOT NULL DEFAULT '' COMMENT '模块名',
  `resource` varchar(30) NOT NULL DEFAULT '' COMMENT '资源',
  `operation` varchar(30) NOT NULL DEFAULT '' COMMENT '操作',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '上级权限',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '路径，用.分隔',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '权限的级别',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='权限表';
