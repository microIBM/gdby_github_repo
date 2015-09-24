ALTER TABLE `t_customer` ADD COLUMN `latest_ordered_time` INT NOT NULL DEFAULT 0 COMMENT '客户最后一次下单时间';

