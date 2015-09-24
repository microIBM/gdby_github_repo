-- ---------------------------------------
-- Table structure for t_rejected_log
-- ---------------------------------------
DROP TABLE IF EXISTS `t_rejected_log`;
CREATE TABLE `t_rejected_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL COMMENT '日志内容',
  `rejected_id` int(11) NOT NULL DEFAULT '0' COMMENT '退货退款单 identifier',
  `author_id` int(11) NOT NULL DEFAULT '0' COMMENT '作者id',
  `author_name` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名称',
  `role_name` varchar(20) NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '角色id',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `auto` tinyint(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '是否自动生成',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='退货退款单操作记录表'

