-- -------------------------------------------
-- Table structure for t_customer_transfer_log
-- -------------------------------------------
DROP TABLE IF EXISTS `t_customer_transfer_log`;
CREATE TABLE `t_customer_transfer_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `cid` int(11) NOT NULL DEFAULT '0' COMMENT '移交的客户ID',
  `src_id` int(11) NOT NULL DEFAULT '0' COMMENT '移交前销售ID',
  `src_role` int(11) NOT NULL DEFAULT '0' COMMENT '移交前销售角色',
  `dest_id` int(11) NOT NULL DEFAULT '0' COMMENT '移交后销售ID',
  `dest_role` int(11) NOT NULL DEFAULT '0' COMMENT '移交后销售角色',
  `operator_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作者ID',
  `operator` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者',
  `log_ip` varchar(255) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `remark` text NOT NULL DEFAULT '' COMMENT '备注',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='客户移交记录表';

