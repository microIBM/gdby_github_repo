-- 创建推荐商品表 by xianwen
-- datetime 15-6-1
create table t_recommend(
    id INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    title varchar(255) NOT NULL DEFAULT '' COMMENT '推荐名称',
    location_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属城市id',
    location_cn varchar(255)  NOT NULL DEFAULT '' COMMENT '所属城市名称',
    site_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属站点',
    product_ids text COMMENT '商品ids逗号关联',
    site_cn varchar(255)  NOT NULL DEFAULT '' COMMENT '所属名称',
    status INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态',
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
