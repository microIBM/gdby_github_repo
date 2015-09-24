ALTER TABLE t_ads ADD COLUMN need_login INT(11) UNSIGNED NOT NULL default 1 COMMENT '1需要登录，0不需要登录';
ALTER TABLE t_ads ADD COLUMN line_ids TEXT COMMENT 'line_ids';
ALTER table t_ads add column offline_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下线时间';
ALTER table t_ads add column online_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上线时间';
