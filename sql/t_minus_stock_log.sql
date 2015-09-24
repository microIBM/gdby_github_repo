CREATE TABLE `t_minus_stock_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `draft_date` int(11) NOT NULL DEFAULT 0 COMMENT '为该日期配送的订单生成销存草稿',
  `line_id` int(11) NOT NULL DEFAULT 0 COMMENT '线路id',
  `site_id` int(11) NOT NULL DEFAULT 0 COMMENT '站点id',
  `return_code` int(11) NOT NULL DEFAULT 0 COMMENT '调用销库存时的返回码',
  `return_msg` varchar(300) NOT NULL DEFAULT 0 COMMENT '调用销库存时的返回消息',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
);
