ALTER TABLE `t_customer` ADD COLUMN `lng` varchar(255) NOT NULL DEFAULT '' COMMENT '经度' AFTER `geo`;
ALTER TABLE `t_customer` ADD COLUMN `lat` varchar(255) NOT NULL DEFAULT '' COMMENT '维度' AFTER `lng`;
#删除geo字段，执行完fix_geo脚本后再删除geo字段
ALTER TABLE `t_customer` DROP COLUMN `geo`;
