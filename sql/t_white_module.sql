--
-- Table structure for table `t_white_module`
--

DROP TABLE IF EXISTS `t_white_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_white_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `module_name` varchar(128) NOT NULL DEFAULT '' COMMENT '模块名称',
  `controller` varchar(64) NOT NULL DEFAULT '' COMMENT '模块对应控制器',
  `action` varchar(64) NOT NULL DEFAULT '' COMMENT '模块对应方法',
  `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='白名单模块表';
/*!40101 SET character_set_client = @saved_cs_client */;
