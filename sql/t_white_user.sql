-- ---------------------------------------
-- Table structure for t_white_user
-- ---------------------------------------


--
-- Table structure for table `t_white_user`
--

DROP TABLE IF EXISTS `t_white_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_white_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` int(10) NOT NULL COMMENT '与hop系统user 表关联的用户id',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT ' 白名单人员姓名',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8 COMMENT='白名单表';
/*!40101 SET character_set_client = @saved_cs_client */;







