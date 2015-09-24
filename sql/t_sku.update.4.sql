-- 可选字段可以为空,如果为空就要返回null,为了方便前端显示默认空字符串''
ALTER TABLE `t_sku` MODIFY COLUMN `guarantee_period` CHAR(30) NOT NULL DEFAULT '' COMMENT '保质期';
ALTER TABLE `t_sku` MODIFY COLUMN `effect_stage`     CHAR(30) NOT NULL DEFAULT '' COMMENT '近效期';
ALTER TABLE `t_sku` MODIFY COLUMN `net_weight`       CHAR(30) NOT NULL DEFAULT '' COMMENT '净重';
ALTER TABLE `t_sku` MODIFY COLUMN `code` CHAR(30)  NOT NULL DEFAULT '' COMMENT '录入条码';
