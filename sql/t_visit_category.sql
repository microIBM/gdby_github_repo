CREATE TABLE `t_visit_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标识',
  `visit_id` int(11) NOT NULL DEFAULT '0' COMMENT '访问id',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '关注分类id',
  `status` tinyint(1) unsigned zerofill NOT NULL DEFAULT '1' COMMENT '状态',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
