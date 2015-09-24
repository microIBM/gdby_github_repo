ALTER TABLE `t_customer` CHANGE COLUMN `ka_type` `account_type` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '客户账号类型：1母账号，2子账号' ;
