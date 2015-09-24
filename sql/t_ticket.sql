-- ----------------------------
-- Table structure for t_customer
-- ----------------------------
DROP TABLE IF EXISTS `t_ticket`;
CREATE TABLE `t_ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `customer_id` int(10) unsigned NOT NULL default 0 comment '客户编号',
  `type_id` int(10) unsigned NOT NULL default 0 comment '券类型编号',
  `number` int(10) unsigned NOT NULL default 0 COMMENT '券码',
  `money` int(10) unsigned NOT NULL default 0 COMMENT '面额',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`)
);
