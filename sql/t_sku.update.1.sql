-- 增加录入条码
alter table t_sku add column guarantee_period int(11) unsigned not null default 0 comment '保质期';
alter table t_sku add column effect_stage int(11) unsigned not null default 0 comment '近效期';
alter table t_sku add column code int(11) unsigned not null default 0 comment '录入条码';
