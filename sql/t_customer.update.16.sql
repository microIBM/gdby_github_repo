ALTER TABLE `t_customer` ADD COLUMN `greens_meat_estimated` tinyint(4) NOT NULL DEFAULT '0' COMMENT '预估日均采购量（蔬菜/肉）';
ALTER TABLE `t_customer` ADD COLUMN `rice_grain_estimated` tinyint(4) NOT NULL DEFAULT '0' COMMENT '预估日均采购量（米面粮油）';
