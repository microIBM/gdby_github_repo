-- author liaoxianwen
-- datetime 2015-5-04
ALTER TABLE t_product ADD COLUMN line_id INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '线路id';
ALTER TABLE t_product ADD COLUMN visiable INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '可见性1 全部，2部分，3 不可见';
-- end
