DROP TABLE IF EXISTS `t_img_upload`;
CREATE TABLE `t_img_upload` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `bucket` varchar(255) NOT NULL DEFAULT '' COMMENT 'bucket名称',
  `bucket_info` varchar(255) NOT NULL DEFAULT '' COMMENT 'bucket说明',
  `watermask` tinyint(255) NOT NULL DEFAULT '0' COMMENT '是否要加水印, 0为否',
  `status` varchar(255) NOT NULL DEFAULT '1' COMMENT '纪录状态',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '纪录创建时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '纪录更新时间',
  PRIMARY KEY (`id`),
  KEY `wm_status` (`watermask`, `status`) 
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
