alter table t_promotion add `limit_new_customer` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否限制是新客户，1为必须是新客，0为不要求是新客';
alter table t_promotion add `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '活动分组编号';
