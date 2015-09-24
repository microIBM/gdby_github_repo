alter table t_product add column error_code int(11) not null default 0 comment '1为成功，其他为wms返回的状态码';

alter table t_product add column sku_number int(11) not null default 0 comment '货号' ;
