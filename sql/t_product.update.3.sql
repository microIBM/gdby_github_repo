--- datetime: 2015-3-26
--- description: pms以及sms上线需要
--- author: liaoxianwen@dachuwang.com
alter table t_product drop column suttle;
alter table t_product drop column sub_title;
alter table t_product drop column error_code;
alter table t_product change description adv_words varchar(255) not null default '' comment '广告语';
alter table t_product modify sku_number int(11) unsigned not null default 0;
alter table t_product change pic_url pic_ids varchar(255) not null default '' comment '商品图片id信息';
alter table t_product add index sku(`sku_number`);
alter table t_product add column is_active int(11) unsigned not null default 1 comment '当前货号的产品是否正常';
alter table t_product add column close_unit int(11) unsigned not null default 1 comment '结算单位id';
alter table t_product add column single_price int(11) unsigned not null default 0 comment '结算单价';
alter table t_product add column is_round int(11) unsigned not null default 0 comment '是否在售卖价前面加上约字';
