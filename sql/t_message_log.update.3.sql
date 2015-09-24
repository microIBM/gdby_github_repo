ALTER TABLE `t_message_log` ADD COLUMN `platform` tinyint(3) NOT NULL DEFAULT '0' COMMENT '设备平台 1安卓，2iOS' AFTER `app_type_id`;
