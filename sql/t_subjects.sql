-- 创建广告数据库 by xianwen
-- datetime 15-4-24
create table t_subjects(
    id INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    title varchar(255) NOT NULL DEFAULT '' COMMENT '专题caption',
    location_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主题所属城市id',
    location_cn varchar(255)  NOT NULL DEFAULT '' COMMENT '主题所属城市名称',
    site_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主题所属站点',
    product_ids text COMMENT '商品ids逗号关联',
    site_cn varchar(255)  NOT NULL DEFAULT '' COMMENT '主题所属名称',
    pic_url varchar(255) NOT NULL DEFAULT '' COMMENT '主题单图片url',
    banner_url varchar(255) NOT NULL DEFAULT '' COMMENT '主题图片url',
    detail_img varchar(255) NOT NULL DEFAULT '' COMMENT '主题页尾图',
    link_url varchar(255)  NOT NULL DEFAULT '' COMMENT '主题连接地址',
    status INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '主题状态',
    subject_type INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '主题类型',
    subject_type_cn varchar(255) NOT NULL DEFAULT '' COMMENT '主题类型名称',
    online_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上线时间',
    offline_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下线时间',
    created_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    updated_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY(`id`),
    KEY `locationId`(`location_id`),
    KEY `siteId`(`site_id`),
    KEY `title`(`title`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
-- end
