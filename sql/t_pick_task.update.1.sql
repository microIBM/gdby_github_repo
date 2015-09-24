ALTER TABLE `t_pick_task`  ADD COLUMN `pick_number` char(32) NOT NULL DEFAULT '' COMMENT '分拣单号' AFTER `id`;
