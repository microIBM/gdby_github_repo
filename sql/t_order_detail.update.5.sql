-- --------------------
-- Author: liaoxianwen@dachuwang.com
-- --------------------
alter table t_order_detail add column sku_number int(11) unsigned not null default 0 comment '货号';
alter table t_order_detail add column category_id int(11) unsigned not null default 0 comment '分类id';
alter table t_order_detail add index cate_id(`category_id`);
alter table t_order_detail add column single_price int(11) unsigned not null default 0 comment '结算单价';
alter table t_order_detail add column close_unit int(11) unsigned not null default 1 comment '结算单位';
