-- 增加客户类型可见
ALTER TABLE t_product add column customer_visiable tinyint(1) not null default 0 comment '0 全部，1 普通用户可见 2 KA客户可见';
