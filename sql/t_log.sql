-- ----------------------------------
-- Table structure for t_log
-- ----------------------------------
DROP TABLE IF EXISTS `t_log`;
CREATE TABLE `t_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `controller` varchar(20) NOT NULL DEFAULT '' COMMENT '控制器名称',
    `method` varchar(20) NOT NULL DEFAULT '' COMMENT '方法名',
    `param` text NOT NULL DEFAULT '' COMMENT '参数：json',
    `host` varchar(50) NOT NULL DEFAULT '' COMMENT '域名',
    `user_agent` varchar(255) NOT NULL DEFAULT '' COMMENT '浏览器',
    `request_method` varchar(10) NOT NULL DEFAULT '' COMMENT '请求类型',
    `is_ajax` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否ajax请求',
    `http_headers` text NOT NULL DEFAULT '' COMMENT 'HTTP请求头',
    `log_ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `operator_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `operator` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`, `created_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT '系统日志表'
PARTITION BY RANGE (created_time)(
    PARTITION p0 VALUES LESS THAN (1438358400),
    PARTITION p1 VALUES LESS THAN (1443628800),
    PARTITION p2 VALUES LESS THAN (1448899200),
    PARTITION p3 VALUES LESS THAN (1451577600),
    PARTITION p4 VALUES LESS THAN (1454256000),
    PARTITION p5 VALUES LESS THAN (1456761600),
    PARTITION p6 VALUES LESS THAN (1459440000),
    PARTITION p7 VALUES LESS THAN (1462032000),
    PARTITION p VALUES LESS THAN MAXVALUE
);

