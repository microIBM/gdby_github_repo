-- 把以前产生的空数据清洗为NULL
UPDATE `t_sku` SET `guarantee_period` = '' WHERE `guarantee_period` = 0;
UPDATE `t_sku` SET `effect_stage`     = '' WHERE `effect_stage`     = 0;
UPDATE `t_sku` SET `net_weight`       = '' WHERE `net_weight`       = 0;
UPDATE `t_sku` SET `code`             = '' WHERE `code`             = 0;
