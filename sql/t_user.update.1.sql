ALTER TABLE `t_user` ADD COLUMN `max_customer` int(11) NOT NULL DEFAULT 0 COMMENT '私海客容上限';
ALTER TABLE `t_user` ADD COLUMN `max_potential_customer` int(11) NOT NULL DEFAULT 0 COMMENT '私海潜在客户容量上限';
ALTER TABLE `t_user` ADD COLUMN `customer_protect` int(11) NOT NULL DEFAULT 0 COMMENT '私海客户保护期';
ALTER TABLE `t_user` ADD COLUMN `potential_customer_protect` int(11) NOT NULL DEFAULT 0 COMMENT '私海潜在客户保护期';
# 设置初始值
update t_user set `max_customer`=100,`max_potential_customer`=100, `customer_protect`=2592000,`potential_customer_protect`=2592000;
