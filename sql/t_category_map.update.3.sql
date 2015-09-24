-- description: 增加分类映射下商品数量
-- author: liaoxianwen
-- datetime : 15-08-25
-- 为了不影响下单，默认先给1，然后再默认为0
ALTER TABLE t_category_map add column product_nums int(11) not null default 1 comment '映射对应分类下面的商品数量';
-- --------------------
ALTER TABLE t_category_map modify column product_nums int(11) not null default 0 comment '映射对应分类下面的商品数量';
