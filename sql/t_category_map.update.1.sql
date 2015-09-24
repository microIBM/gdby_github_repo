-- category_map 增加城市id 多城市支持
ALTER TABLE t_category_map ADD COLUMN location_id INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ' 代表全国 开放城市id';
ALTER TABLE t_category_map ADD INDEX localId(location_id);
UPDATE t_category_map set location_id = 804;
