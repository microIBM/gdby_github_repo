ALTER TABLE `t_customer` ADD COLUMN `pre_forbid` TINYINT(4) NOT NULL DEFAULT 0;
ALTER TABLE `t_customer` MODIFY COLUMN `billing_cycle` varchar(100) NOT NULL DEFAULT '' COMMENT '结账周期';
ALTER TABLE `t_customer` MODIFY COLUMN `check_date` varchar(100) NOT NULL DEFAULT '' COMMENT '对账日期';
ALTER TABLE `t_customer` MODIFY COLUMN `invoice_date` varchar(100) NOT NULL DEFAULT '' COMMENT '开票日期';
ALTER TABLE `t_customer` MODIFY COLUMN `pay_date` varchar(100) NOT NULL DEFAULT '' COMMENT '付款日期';
ALTER TABLE `t_customer` ADD COLUMN `invoice_title` varchar(100) NOT NULL DEFAULT '' COMMENT '发票抬头';
