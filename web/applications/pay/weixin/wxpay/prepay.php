<?php
require_once '../../config/header.php';
require_once '../../logics/lib/log.php';
require_once '../../logics/order.php';
require_once "lib/WxPay.Api.php";

try {
    // 初始化日志
    $logHandler = new CLogFileHandler(Config::PAY_LOG_PATH . date('Y-m-d') . '.log');
    $log = Log::Init($logHandler, Config::PAY_LOG_LEVER);

    // 第二步：获取订单信息
    if (empty($_REQUEST['order_id'])) {
        throw new Exception('order_id required,but empty be given');
    } else {
        $order_info = Order::get_order_by_id($_REQUEST['order_id']);
    }
    
    //第三步：验证支付请求身份
    if ($_REQUEST['user_id'] != $order_info['user_id']) {
        throw new Exception('illegal pay request');
    }
    
    // 第四步：创建预付订单
    if (! empty($order_info)) {
        $pay_toatal_price = $order_info['total_price']+$order_info['deliver_fee']-$order_info['minus_amount']-$order_info['pay_reduce']+$order_info['service_fee'];
        $event_reduce = $order_info['minus_amount'] + $order_info['pay_reduce'];
        $input = new WxPayUnifiedOrder();
        $input->SetBody(Config::WX_ORDER_TITLE); // 商品或支付单简要描述
        $input->SetAttach(PAY_ENV); // 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $input->SetOut_trade_no( $order_info['id']); // 商户系统内部的订单号,32个字符内
        $input->SetTotal_fee($pay_toatal_price); // 订单支付总金额，整数，以分为单位
        $input->SetTime_start(date("YmdHis")); // 订单生成时间，格式为yyyyMMddHHmmss
        $input->SetTime_expire(date("YmdHis", time() + Config::WX_ORDER_TIMEOUT)); // 订单失效时间，格式为yyyyMMddHHmmss
        $input->SetGoods_tag(Config::WX_ORDER_TAG); // 设置商品标记
        $input->SetNotify_url(Config::API_NOTIFY_URL); // 接收微信支付异步通知回调地址
        $input->SetTrade_type("APP"); // 交易类型
        $pre_order = WxPayApi::unifiedOrder($input,Config::API_TIMEOUT,Config::API_NOTIFY_URL); // 创建预付订单
        //app调起支付所需要的信息
        $new_order['appid'] = $pre_order['appid'];
        $new_order['packagevalue'] = 'Sign=WXPay';
        $new_order['timestamp'] = time();
        $new_order['noncestr'] = md5($pre_order['once_str']);
        $new_order['partnerid'] = $pre_order['mch_id'];
        $new_order['prepayid'] = $pre_order['prepay_id'];
        $new_order['sign'] = Common::MakeSign($pre_order);
        $new_order['sign_noncestr'] = WxPayConfig::KEY;
        Common::Success($new_order);
    } else {
        throw new Exception('order info error');
    }
    //第四步：在支付成功回调通知中处理成功之后的事宜，见 notify.php

} catch (Exception $e) {
    Log::ERROR(sprintf('run exceptin:%s|file:%s|line:%s', $e->getMessage(), $e->getFile(), $e->getLine()));
    Common::Failed($e->getMessage());
}
?>
