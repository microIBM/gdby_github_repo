/*
 Source Server         : dev
 Source Server Type    : MySQL
 Source Host           : 127.0.0.1
 Source Database       : d_dachuwang

 Date: 06/30/2015
*/

-- ----------------------------
--  Table structure for `t_statics_history`
-- ----------------------------
DROP TABLE IF EXISTS `t_statics_history`;
CREATE TABLE `t_statics_history` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '跑表时间',
`updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新表时间',
`order_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单数',
`order_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '流水（分）',
`potential_cus_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '潜在顾客数',
`register_cus_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '注册顾客数',
`ordered_cus_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单顾客数',
`first_order_cus_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首单顾客数',
`again_order_cus_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '复购顾客数',
`city_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '城市ID',
`customer_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '用户类型',
PRIMARY KEY (`id`),
KEY `t_statics_history_city_id` (`city_id`),
KEY `t_statics_history_customer_type` (`customer_type`),
KEY `t_statics_history_index` (`city_id`,`customer_type`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='数据统计总表-历史总计';
