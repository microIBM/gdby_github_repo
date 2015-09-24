-- ------------------------------------
-- Table structure for t_customer_image
-- ------------------------------------
DROP TABLE IF EXISTS `t_customer_image`;
CREATE TABLE `t_customer_image` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `owner_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '拥有型类型:1潜在客户，2客户',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拥有者id',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '原图片路径',
  `thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图路径',
  `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常，0已删除',
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '客户图片表';
