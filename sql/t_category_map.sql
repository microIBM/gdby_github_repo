/*
 Navicat MySQL Data Transfer

 Source Server         : dev
 Source Server Type    : MySQL
 Source Server Version : 50537
 Source Host           : 127.0.0.1
 Source Database       : d_dachuwang

 Target Server Type    : MySQL
 Target Server Version : 50537
 File Encoding         : utf-8

 Date: 03/14/2015 16:59:31 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `t_category_map`
-- ----------------------------
DROP TABLE IF EXISTS `t_category_map`;
CREATE TABLE `t_category_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '分类名称',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '分类路径',
  `pinyin_s` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音缩写',
  `pinyin_a` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音全写',
  `upid` int(255) unsigned NOT NULL DEFAULT '0' COMMENT '分类上级id',
  `weight` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分 类展示权重',
  `status` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '分类状态',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类创建时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类更新时间',
  `origin_id` int(11) NOT NULL DEFAULT '0' COMMENT '原id',
  `origin_name` varchar(255) NOT NULL COMMENT '默认名字',
  `site_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`) USING BTREE,
  KEY `path` (`path`) USING BTREE,
  KEY `pinyin_s` (`pinyin_s`) USING BTREE,
  KEY `pinyin_a` (`pinyin_a`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

SET FOREIGN_KEY_CHECKS = 1;
