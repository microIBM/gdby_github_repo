ALTER TABLE `t_potential_customer` ADD COLUMN `is_located` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否定位:1已定位,0未定位';
ALTER TABLE `t_potential_customer` ADD COLUMN `direction` varchar(255) NOT NULL DEFAULT  '' COMMENT '方位';
