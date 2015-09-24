ALTER TABLE `t_user_app_binding` ADD COLUMN `platform` tinyint(3) NOT NULL DEFAULT '0' COMMENT '设备平台 1安卓，2iOS' AFTER `imei_code`;
