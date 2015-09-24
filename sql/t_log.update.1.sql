ALTER TABLE `t_log` CHANGE COLUMN `controller` `controller` varchar(50) NOT NULL DEFAULT '' COMMENT '控制器名称' ;
ALTER TABLE `t_log` CHANGE COLUMN `method` `method` varchar(50) NOT NULL DEFAULT '' COMMENT '方法名' ;
