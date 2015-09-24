CREATE TABLE `t_message_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `msg_type` int unsigned NOT NULL DEFAULT 0 COMMENT '1通知app进行页面跳转',
    `app_type_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'app type id 1大厨 2大果 3crm，以后可能会有网页端推送，向上扩展即可',
    `app_uid` int unsigned NOT NULL DEFAULT 0 COMMENT '设备id',
    `title` varchar(20) NOT NULL DEFAULT '' COMMENT '推送消息标题',
    `content` text NOT NULL DEFAULT '' COMMENT '推送消息内容',
    `url` varchar(200) NOT NULL DEFAULT '' COMMENT '推送url',
    `extra` text NOT NULL DEFAULT '' COMMENT '推送附加参数',
    `receive_time` int(11) NOT NULL DEFAULT 0 COMMENT '接收时间',
    `read_time` int(11) NOT NULL DEFAULT 0 COMMENT '阅读时间',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '消息状态 0未推送 1已推送 2已读',
    `created_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY app_uid(`app_uid`),
    KEY app_type_id(`app_type_id`),
    KEY msg_type(`msg_type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='消息表'
