CREATE TABLE `t_sms_log` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增编号',
 `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '手机号',
 `content` text NOT NULL COMMENT '发送内容',
 `status` int(11) NOT NULL DEFAULT '1',
 `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
 `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
 `type_id` TINYINT NOT NULL DEFAULT '1' COMMENT '1为高优先级短信，2为低优先级短信',
 PRIMARY KEY (`id`),
 KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信发送日志'
