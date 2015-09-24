alter table t_order_detail modify spec varchar(255) not null default '';
alter table t_order_detail add column unit_id int not null default 0;
