CREATE TABLE `t_jpush_notice_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `app_type_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'app type id 1大厨 2大果 3crm',
    `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
    `content` text NOT NULL DEFAULT '' COMMENT '推送消息内容',
    `push_time` int(11) NOT NULL DEFAULT 0 COMMENT '推送时间',
    `receive_time` int(11) NOT NULL DEFAULT 0 COMMENT '客户端接收到极光推送通知的时间',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '消息状态 0未推送 1已推送 2已读',
    `created_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY user_id(`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供应订单表'
