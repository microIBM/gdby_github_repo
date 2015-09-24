<?php
require_once '../../logics/order.php';
require_once '../../logics/customer.php';
require_once '../../logics/lib/log.php';
require_once "lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";

try {
    // 初始化日志
    $logHandler = new CLogFileHandler(Config::PAY_LOG_PATH . date('Y-m-d') . '.log');
    $log = Log::Init($logHandler, Config::PAY_LOG_LEVER);

    // 第一步：获取用户openid，此步骤需要OAuth2.0认证授权
    $tools = new JsApiPay();
    $openId = $tools->GetOpenid();
    if(empty($openId)){
        throw new Exception('openId required, but empty be given');
    }
    
    // 第二步：获取订单信息
    if (empty($_GET['order_number'])) {
        throw new Exception('order_number required,but empty be given');
    } else {
        $order_info = Order::get_order_by_order_number($_GET['order_number']);
    }
    //第三步：获取当前登陆用户信息用于校验支付身份
    $user_info = Customer::get_login_customer();
    if ($order_info['user_id'] !== $user_info['id']) {
        throw new Exception('illegal pay request');
    }
    
    // 第四步：创建预付订单
    if (!empty($order_info)) {
        $pay_toatal_price = $order_info['total_price']+$order_info['deliver_fee']-$order_info['minus_amount']-$order_info['pay_reduce']+$order_info['service_fee'];
        $event_reduce = $order_info['minus_amount'] + $order_info['pay_reduce'];
        $input = new WxPayUnifiedOrder();
        $input->SetBody(Config::WX_ORDER_TITLE); // 商品或支付单简要描述
        $input->SetAttach(PAY_ENV); // 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $input->SetOut_trade_no( $order_info['id']); // 商户系统内部的订单号,32个字符内
        $input->SetTotal_fee($pay_toatal_price); // 订单支付总金额，整数，以分为单位
        //$input->SetTotal_fee(1);
        $input->SetTime_start(date("YmdHis")); // 订单生成时间，格式为yyyyMMddHHmmss
        $input->SetTime_expire(date("YmdHis", time() + Config::WX_ORDER_TIMEOUT)); // 订单失效时间，格式为yyyyMMddHHmmss
        $input->SetGoods_tag(Config::WX_ORDER_TAG); // 设置商品标记
        $input->SetNotify_url(Config::API_NOTIFY_URL); // 接收微信支付异步通知回调地址
        $input->SetTrade_type("JSAPI"); // 交易类型
        $input->SetOpenid($openId); // 微信用户的Openid
        $order = WxPayApi::unifiedOrder($input,Config::API_TIMEOUT,Config::API_NOTIFY_URL); // 创建预付订单
        $jsApiParameters = $tools->GetJsApiParameters($order);

        // 获取共享收货地址js函数参数
        //$editAddress = $tools->GetEditAddressParameters();

    } else {
        throw new Exception('order_info requrired,but empty be given');
    }
    //第四步：在支付成功回调通知中处理成功之后的事宜，见 notify.php

} catch (Exception $e) {
    Log::ERROR(sprintf('run exceptin:%s|file:%s|line:%s', $e->getMessage(), $e->getFile(), $e->getLine()));
}
?>
<!DOCTYPE html>
<html>
<head lang="zh">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0">
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title>微信安全支付</title>
    <link rel="stylesheet" type="text/css" href="/statics/css/weixinpay.css?v=<?php echo Config::WX_VERSION; ?>">
    <script type="text/javascript">
        var pay_success_redirect = "<?php echo Config::PAY_SUCCESS_URL?>";
        //调用微信JS api 支付
        function jsApiCall()
        {
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                <?php echo $jsApiParameters; ?>,
                function(res){
                    //如果支付成功，跳转到支付成功页面
                    if(res.err_msg == 'get_brand_wcpay_request:ok'){
                        setTimeout(function(){
                            window.location =pay_success_redirect || 'http://chu.dachuwang.com/home';
                        },100);

                    } else if(res.err_msg == 'get_brand_wcpay_request:fail'){
                        if(!confirm('支付失败,是否继续尝试支付?')){
                            setTimeout(function(){
                                window.location.href=pay_success_redirect || 'http://chu.dachuwang.com/home';
                            },100);
                        }
                    }
                    //WeixinJSBridge.log(res.err_msg);
                }
            );
        }

        function callpay()
        {
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            }else{
                jsApiCall();
            }
        }

    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <ul>
                <li class="f-left"><a href="<?php echo Config::BASE_URL ?>"><span class="glyphicon glyphicon-menu-left"></span><span>首页</span></a></li>
                <li class="ab-center"><span href="javascript::void(0);" >确认支付</span></li>
                <li class="f-right"><a href="tel:400-8199-491"><span class="glyphicon glyphicon-earphone m-4"></span><span>客服</span></a></li>
            </ul>
        </div>
        <div class="body mb-60">
            <?php if(!empty($order_info)) :?>
                <h4>请使用微信付款</h4>
                <ul class="font-14">
                    <li>商品总价：<em>¥<?php echo number_format($order_info['total_price']/100,2)?></em></li>
                    <?php if($order_info['service_fee'] > 0) :?>
                    <li>服务费(<?php echo $order_info['service_fee_rate'] ?>%)：<em>¥<?php echo number_format($order_info['service_fee']/100,2)?></em></li>
                    <?php endif;?>
                    <li>活动优惠：<em>¥<?php echo number_format($event_reduce/100,2)?></em></li>
                    <li>运&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;费：<em>¥<?php echo number_format($order_info['deliver_fee']/100,2)?></em></li>
                </ul>
                <ul class="text-right font-16">
                    <li>支付总额：<em>￥<?php echo number_format($pay_toatal_price/100,2);?></em></li>
                </ul>
                <a class="btn-a" href="javascript::void(0)" onclick="callpay()">微信支付</a>
            <?php endif;?>
        </div>
        <div class="footer">
            <ul>
                <li><a href="<?php echo Config::BASE_URL ?>"><h4 class="m-0 text-center hei-24"><span class="footsprite sprite-home scale5"></span></h4><div>首页</div></a></li>
                <li><a href="<?php echo Config::BASE_URL ?>/category//"><h4 class="m-0 text-center hei-24"><span class="footsprite sprite-cate scale5"></span></h4><div>分类</div></a></li>
                <li><a href="<?php echo Config::BASE_URL ?>/cart"><h4 class="m-0 text-center hei-24"><span class="footsprite sprite-shop scale5"></span></h4><div>购物车</div></a></li>
                <li><a href="<?php echo Config::BASE_URL ?>/user/center"><h4 class="m-0 text-center hei-24"><span class="footsprite sprite-user scale5"></span></h4><div>个人中心</div></a></li>
            </ul>
        </div>
    </div>
</body>
</html>
