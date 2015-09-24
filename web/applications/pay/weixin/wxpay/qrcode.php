<?php

require_once '../../config/header.php';
require_once '../../phpqrcode/phpqrcode.php';
require_once '../../logics/order.php';
require_once '../../logics/customer.php';
require_once '../../logics/lib/log.php';
require_once 'lib/WxPay.Config.php';
require_once 'lib/WxPay.Api.php';
require_once "WxPay.NativePay.php";

// 初始化日志
$logHandler = new CLogFileHandler(Config::PAY_LOG_PATH . date('Y-m-d') . '.log');
$log = Log::Init($logHandler, Config::PAY_LOG_LEVER);
try {
    if (isset($_GET['order_id'])) {
        //获取订单信息
        $order_id = intval($_GET['order_id']);
        $order_info = Order::get_order_by_id($order_id);
        if(empty($order_info)){
            throw new Exception('not fetch orderInfo with orderId:'.$order_id);
        }
        
        //获取当前登陆用户信息
        $user_info = Customer::get_login_customer();
        
        if ($order_info['user_id'] !== $user_info['id']) {
            throw new Exception('illegal pay request');
        }
        
        /**
         * 扫码支付模式一
         * 流程：
         * 1、组装包含支付信息的url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
         * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
         * 5、支付完成之后，微信服务器会通知支付成功
         * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
//         $notify = new NativePay();
//         $url1 = $notify->GetPrePayUrl("123456789");
        
        
        $notify = new NativePay();
        $input = new WxPayUnifiedOrder();
        
        $input->SetBody(Config::WX_ORDER_TITLE);
        $input->SetAttach(PAY_ENV);
        //测试环境必须加上时间戳，否则会有报商户订单号重复得错误（原因是线上和测试环境都是统一使用微信支付创建支付订单）
        $input->SetOut_trade_no($order_info['id']);
        $input->SetTotal_fee($order_info['final_price']);
        //$input->SetTotal_fee(1);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + Config::WX_ORDER_TIMEOUT));
        $input->SetGoods_tag(Config::WX_ORDER_TAG);
        $input->SetNotify_url(Config::API_NOTIFY_URL);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($order_id);
        $result = $notify->GetPayUrl($input,Config::API_TIMEOUT);
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            $short_url = $result["code_url"];
            QRcode::png($short_url,false,3,5,5);
        }else{
            throw new Exception('支付短链接生成异常，异常原因:'.$result['err_code_des']);
        }
    }else{
        throw new Exception('order_id required');
    }
} catch (Exception $e) {
    Log::ERROR(sprintf('run exceptin:%s|file:%s|line:%s', $e->getMessage(), $e->getFile(), $e->getLine()));
    echo $e->getMessage();
}

