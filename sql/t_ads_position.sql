-- 广告位数据 by xianwen
-- datetime 15-4-24
create table t_ads_position(
    id INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    title varchar(255) NOT NULL DEFAULT '' COMMENT '广告位名称',
    status INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '广告位状态',
    created_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    updated_time INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY(`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
-- end
