ALTER TABLE `t_complaint` 
ADD COLUMN `deal_result` TINYINT(4) NOT NULL DEFAULT 0 COMMENT '\'understanding\'  1 \'voucher\'  2, ’rejected\' 3,’failure\'  4',
ADD COLUMN `result_param` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '处理结果信息',
ADD COLUMN `relation_content` TINYINT(1) UNSIGNED ZEROFILL NOT NULL DEFAULT 0 COMMENT '相关内容 1. 整单 2 sku';
