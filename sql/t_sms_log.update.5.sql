ALTER TABLE `d_dachuwang`.`t_sms_log` ADD COLUMN `job_id` varchar(10) NOT NULL COMMENT '队列处理任务ID' AFTER `send_response`;
