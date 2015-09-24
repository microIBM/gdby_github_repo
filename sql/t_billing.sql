CREATE TABLE `t_billing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billing_num` varchar(20) NOT NULL DEFAULT '' COMMENT '账单编号',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户标识',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际账期 的起始时间 用户统计订单',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际账期 的结束时间 用户统计订单',
  `billing_cycle` varchar(100) NOT NULL DEFAULT '' COMMENT '账期长度',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '账单状态 0 被删除， 1未打款, 2预备打款 ,3已结款, 4已收款 ',
  `total_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '本帐期应付金额总计',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '最晚支付时间',
  `expire_tag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '只要该账单出现 预期未支付，该客户将会留下一个未支付污点',
  `invoice` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开发票',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `billing_remark` text NOT NULL DEFAULT '' COMMENT '备注',
  `expire_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否逾期未付',
  `theory_start` int(11) NOT NULL DEFAULT '0' COMMENT '理论账期开始日期',
  `theory_end` int(11) NOT NULL DEFAULT '0' COMMENT '理论账期结束日期',
  `payment_evidence` varchar(255) NOT NULL DEFAULT '' COMMENT '签收凭证',
  `check_date` varchar(45) NOT NULL DEFAULT '' COMMENT '对账日期',
  `pay_date` varchar(45) NOT NULL DEFAULT '' COMMENT '付款日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='账单表'

