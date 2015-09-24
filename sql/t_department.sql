-- ----------------------------
-- Table structure for t_department
-- ----------------------------
DROP TABLE IF EXISTS `t_department`;
CREATE TABLE `t_department` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '部门名称',
  `description` varchar(150) NOT NULL DEFAULT '' COMMENT '部门描述',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '上级部门',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '路径，用.分隔',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '权限的级别',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `dept_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='部门表';

