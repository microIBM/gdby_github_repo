-- 增加录入条码
alter table t_sku add column net_weight int(11) unsigned not null default 0 comment '净重';
