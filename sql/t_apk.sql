DROP TABLE IF EXISTS `t_apk`;
CREATE TABLE `t_apk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `client_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:安卓 1:IOS',
  `version_name` varchar(50) NOT NULL DEFAULT '',
  `version_num` int(10) NOT NULL DEFAULT '0' COMMENT '版本号',
  `update_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0强制更新 1建议更新 2不更新',
  `update_txt` varchar(200) NOT NULL DEFAULT '' COMMENT '更新内容',
  `down_url` varchar(200) NOT NULL DEFAULT '' COMMENT '下载地址',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='apk更新表';
