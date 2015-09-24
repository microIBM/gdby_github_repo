ALTER TABLE `t_order` ADD COLUMN `dist_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '配送单ID';
ALTER TABLE `t_order` ADD COLUMN `dist_order` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '配送单序号';
ALTER TABLE `t_order` ADD COLUMN `wave_id` int unsigned NOT NULL DEFAULT 0;
ALTER TABLE `t_order` ADD COLUMN `pick_task_id` int unsigned NOT NULL DEFAULT 0;
