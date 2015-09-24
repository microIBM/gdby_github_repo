CREATE TABLE `t_visit_suggestion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL DEFAULT '0' COMMENT '拜访id',
  `suggestion_id` int(11) NOT NULL DEFAULT '0' COMMENT '建议id',
  `status` tinyint(1) unsigned zerofill NOT NULL DEFAULT '1' COMMENT '状态',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建日期',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
