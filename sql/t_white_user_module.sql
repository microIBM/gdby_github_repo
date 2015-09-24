--
-- Table structure for table `t_white_user_module`
--

DROP TABLE IF EXISTS `t_white_user_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_white_user_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '与hop系统user表关联的用户id',
  `manager_id` int(10) NOT NULL DEFAULT '0' COMMENT '模块创建者id',
  `module_id` int(10) NOT NULL DEFAULT '0' COMMENT '模块id',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户对应模块的状态 1启用 0禁用',
  `created_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `search` (`user_id`,`manager_id`,`module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=738 DEFAULT CHARSET=utf8 COMMENT='白名单用户模块中间表';
/*!40101 SET character_set_client = @saved_cs_client */;