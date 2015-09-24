ALTER TABLE `t_complaint` ADD COLUMN `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户ID' AFTER `shop_name`;
ALTER TABLE `t_complaint` ADD INDEX `user_id`(`user_id`);
