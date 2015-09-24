-- ----------------------------------
-- Table structure for t_workflow_log
-- ----------------------------------
DROP TABLE IF EXISTS `t_workflow_log`;
CREATE TABLE `t_workflow_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单ID',
    `log_info` text NOT NULL DEFAULT '' COMMENT '日志详细信息',
    `operate_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '操作类型',
    `log_ip` varchar(255) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `remark` text NOT NULL DEFAULT '' COMMENT '备注',
    `operator_type` int(11) NOT NULL DEFAULT 0 COMMENT '操作者角色类型',
    `operator_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `operator` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT '订单工作流日志表';

