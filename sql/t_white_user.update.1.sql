-- ---------------------------------------
-- Table structure for t_white_user
-- ---------------------------------------

ALTER TABLE `t_white_user` ADD COLUMN `user_role` tinyint NOT NULL DEFAULT 3 COMMENT '1超级管理员,2管理员,3普通用户' AFTER `mobile`;

UPDATE `t_white_user` SET `user_role` = 1 WHERE `mobile` = '18501361389';







