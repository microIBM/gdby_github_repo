-- product 支持限购
ALTER TABLE t_product ADD COLUMN storage int(11) unsigned NOT NULL default 0 comment '限产品数量';
ALTER TABLE t_product ADD COLUMN deposit int(11) unsigned NOT NULL default 0 comment '押金';
ALTER TABLE t_product ADD COLUMN buy_limit int(11) unsigned NOT NULL default 0 comment '每人限购多少';
