-- t_order 商城支持多城市
ALTER TABLE t_order ADD COLUMN location_id INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开放城市id';
ALTER TABLE t_order ADD INDEX localId(location_id);

UPDATE t_order set location_id = 804;
