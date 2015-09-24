alter table t_category add column error_code int(11) not null default 0 comment '1为成功，其他为wms返回 的状态码' ;
