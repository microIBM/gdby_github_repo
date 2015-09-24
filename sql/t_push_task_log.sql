-- ----------------------------
--  Table structure for `t_push_task_log`
-- ----------------------------
DROP TABLE IF EXISTS `t_push_task_log`;
CREATE TABLE `t_push_task_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID ',
  `push_res` varchar(200) NOT NULL COMMENT '返回数据',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
