ALTER TABLE `t_workflow_log` ADD COLUMN `edit_type` INT NOT NULL DEFAULT '0' COMMENT 'log行为类型，具体参见配置文件';
ALTER TABLE `t_workflow_log` CHANGE `order_id` `obj_id` INT NOT NULL DEFAULT 0 COMMENT '订单id/wave_id/分拣任务id';
alter table t_workflow_log add index obj_id (obj_id);
