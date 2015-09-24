ALTER TABLE `t_customer_transfer_log` ADD COLUMN `ctype` tinyint(4) NOT NULL DEFAULT '0' COMMENT '客户类型：1潜在客户，2客户';
UPDATE `t_customer_transfer_log` SET `ctype`=2;
