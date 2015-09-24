-- ------------------------------------
-- Table structure for t_image
-- ------------------------------------
DROP TABLE IF EXISTS `t_image`;
CREATE TABLE `t_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `owner_type` varchar(50) NOT NULL DEFAULT '' COMMENT '拥有型类型',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拥有者id',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '原图片路径',
  `thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图路径',
  `mime_type` varchar(255) NOT NULL DEFAULT '' COMMENT '文件类型',
  `file_size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常，0已删除',
  PRIMARY KEY (`id`),
  KEY `owner_type` (`owner_type`),
  KEY `owner_id` (`owner_id`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '图片表';
