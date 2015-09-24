alter table t_category add column is_leaf tinyint not null default 0 comment '表示该结点是否是分类的叶  子结点，叶子结点下才可以挂靠商品';
