-- sku 图片上传需要的字段
-- author liaoxianwen
-- datetime 2015-04-16 18:00
ALTER TABLE t_sku ADD COLUMN pic_ids VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片信息id';
