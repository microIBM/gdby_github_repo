ALTER TABLE `t_user_app_binding` ADD COLUMN `device_token` varchar(64) NOT NULL DEFAULT '' COMMENT '苹果设备的deviceToken' AFTER `imei_code`;

