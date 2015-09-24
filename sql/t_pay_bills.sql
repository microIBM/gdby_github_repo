-- ----------------------------
--  Table structure for `t_pay_bills`
--  Author:yuanxiaolin@dachuwang.com
-- ----------------------------
DROP TABLE IF EXISTS `t_pay_bills`;
CREATE TABLE `t_pay_bills` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水单ID',
    `order_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID，关联接入方的订单ID',
    `pay_type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '支付方式，0为货到付款，1为微信支付',
    `pay_status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '支付状态，0为支付失败，1为支付成功',
    `transaction_id` varchar(255) NOT NULL DEFAULT '' COMMENT '交易流水号，一般由第三方支付平台生成',
    `trade_no` varchar(255) NOT NULL DEFAULT '' COMMENT '交易订单号，一般由接入支付方生成',
    `total_fee` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '支付总额',
    `cash_fee` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '现金支付总额',
    `created_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    `full_data` text NOT NULL COMMENT '第三方支付平台回调的全部数据',
    `status` tinyint(3) NOT NULL DEFAULT 1 COMMENT '状态标识',
    PRIMARY KEY (`id`),
    KEY `t_pay_bills_transaction_id` (`transaction_id`),
    KEY `t_pay_bills_order_id` (`order_id`),
    KEY `t_pay_bills_trade_no` (`trade_no`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='支付流水表';




