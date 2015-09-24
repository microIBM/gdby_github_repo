ALTER TABLE `t_suborder` ADD COLUMN `driver_name` varchar(20) NOT NULL DEFAULT "" COMMENT "订单运输司机";
ALTER TABLE `t_suborder` ADD COLUMN `driver_mobile` varchar(20) NOT NULL DEFAULT "" COMMENT "订单运输司机的手机号";
