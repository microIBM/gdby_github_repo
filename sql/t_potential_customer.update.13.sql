ALTER TABLE `t_potential_customer` MODIFY COLUMN `billing_cycle` varchar(100) NOT NULL DEFAULT '' COMMENT '结账周期';
ALTER TABLE `t_potential_customer` MODIFY COLUMN `check_date` varchar(100) NOT NULL DEFAULT '' COMMENT '对账日期';
ALTER TABLE `t_potential_customer` MODIFY COLUMN `invoice_date` varchar(100) NOT NULL DEFAULT '' COMMENT '开票日期';
ALTER TABLE `t_potential_customer` MODIFY COLUMN `pay_date` varchar(100) NOT NULL DEFAULT '' COMMENT '付款日期';
