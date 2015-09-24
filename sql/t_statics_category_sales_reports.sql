-- bi数据分析所用
-- 库名:d_statics
-- start_date 15-8-29
CREATE TABLE `t_statics_category_sales_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增',
  `category_id` varchar(20) NOT NULL DEFAULT '' COMMENT '一级品类ID',
  `category_name` varchar(20) NOT NULL DEFAULT '' COMMENT '一级品类名称',
  `city_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '城市ID',
  `city_name` varchar(32) NOT NULL DEFAULT '' COMMENT '城市名称',
  `sign_orders` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '签收单数',
  `sign_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '签收金额',
  `sign_amount_rate` float NOT NULL DEFAULT '0' COMMENT '金额占比',
  `sign_rate` float NOT NULL DEFAULT '0' COMMENT '签收率',
  `purchase_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '采购成本',
  `gross_profit_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '毛利额',
  `gross_profit_margin` float NOT NULL DEFAULT '0' COMMENT '毛利率',
  `stock_cost` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '库存成本',
  `stock_cost_rate` float NOT NULL DEFAULT '0' COMMENT '库存占比',
  `turnover_rate` float NOT NULL DEFAULT '0' COMMENT '周转率',
  `crossover_rate` float NOT NULL DEFAULT '0' COMMENT '交叉比率',
  `sign_sku_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被签收的SKU种数',
  `active_sku_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已上架SKU种数',
  `pin_rate` float NOT NULL DEFAULT '0' COMMENT '动销率:sign_sku_count/active_sku_count',
  `complain_orders` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '投诉单数',
  `rejection_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '拒收金额',
  `return_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '退货金额',
  `date_start` varchar(20) NOT NULL DEFAULT '0000-00-00' COMMENT '开始日期',
  `date_end` varchar(20) NOT NULL DEFAULT '0000-00-00' COMMENT '结束日期',
  `inhive_date` varchar(20) NOT NULL DEFAULT '0000-00-00' COMMENT '插入时间',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `date_start` (`date_start`),
  KEY `date_end` (`date_end`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COMMENT='分城市分类别核心运营数据';