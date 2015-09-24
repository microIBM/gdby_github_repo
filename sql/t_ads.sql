-- 创建广告数据库 by xianwen
-- datetime 15-4-24
create table t_ads(
    id INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    title varchar(255) NOT NULL DEFAULT '' COMMENT '广告caption',
    location_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '广告所属城市',
    pos_id varchar(255)  NOT NULL DEFAULT '' COMMENT '广告所属广告位',
    site_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '广告所属站点',
    pic_url varchar(255) NOT NULL DEFAULT '' COMMENT '广告图片url',
    detail_img varchar(255) NOT NULL DEFAULT '' COMMENT '广告详情图片url',
    link_url varchar(255)  NOT NULL DEFAULT '' COMMENT '广告连接地址',
    status INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '广告位状态',
    created_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    updated_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY(`id`),
    KEY `locationId`(`location_id`),
    KEY `posId`(`pos_id`),
    KEY `title`(`title`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
-- end
