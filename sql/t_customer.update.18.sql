ALTER TABLE `t_customer` ADD COLUMN `ka_seed` TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'ka种子 1为种子 0为非种子，默认为种子' AFTER `rice_grain_estimated`;
