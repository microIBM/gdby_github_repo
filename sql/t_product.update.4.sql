-- product 支持多城市
ALTER TABLE t_product ADD COLUMN location_id int(11) unsigned NOT NULL default 0 comment '销售地区 代表对应的开放城市id';
ALTER TABLE t_product ADD INDEX localId(location_id);

UPDATE t_product set location_id = 804;
