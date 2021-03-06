ALTER TABLE `t_potential_customer` ADD COLUMN `ka_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'KA客户账号类型：1母账号，2子账号';
ALTER TABLE `t_potential_customer` ADD COLUMN `bank` int(11) NOT NULL DEFAULT '0' COMMENT '开户银行';
ALTER TABLE `t_potential_customer` ADD COLUMN `sub_bank` varchar(255) NOT NULL DEFAULT '' COMMENT '开户银行支行';
ALTER TABLE `t_potential_customer` ADD COLUMN `bank_account` varchar(255) NOT NULL DEFAULT '' COMMENT '银行账号';
ALTER TABLE `t_potential_customer` ADD COLUMN `parent_mobile` char(11) NOT NULL DEFAULT '' COMMENT '关联母账号';
ALTER TABLE `t_potential_customer` ADD COLUMN `billing_cycle` varchar(20) NOT NULL DEFAULT '' COMMENT '结账周期';
ALTER TABLE `t_potential_customer` ADD COLUMN `check_date` varchar(20) NOT NULL DEFAULT '' COMMENT '对账日期';
ALTER TABLE `t_potential_customer` ADD COLUMN `invoice_date` varchar(20) NOT NULL DEFAULT '' COMMENT '开票日期';
ALTER TABLE `t_potential_customer` ADD COLUMN `pay_date` varchar(20) NOT NULL DEFAULT '' COMMENT '付款日期';
