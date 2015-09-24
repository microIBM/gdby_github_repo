<?php
require_once '../../config/header.php';    // 公共头文件
require_once '../../logics/lib/log.php'; // 日志处理
require_once '../../logics/order.php'; // 订单相关逻辑处理
require_once '../../logics/trade.php'; // 交易相关逻辑处理
require_once "lib/WxPay.Api.php"; // 微信支付基础接口
require_once 'lib/WxPay.Notify.php'; // 微信回调通知接口

class PayNotifyCallBack extends WxPayNotify {
    public $query_data = array (); // 从微信查询到已支付成功的订单信息
    public $request_data = array (); // 微信请求传递的已支付成功的订单信息

    public function Queryorder ($data) {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($data['transaction_id']);
        $input->SetTradeType($data['trade_type']);
        $result = WxPayApi::orderQuery($input,Config::API_TIMEOUT);
        $this->query_data = $result;
        Log::DEBUG("query:" . json_encode($result));
        if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }
    
    // 重写回调处理函数
    public function NotifyProcess ($data, &$msg) {
        Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array ();
        $this->request_data = $data;
        if (! array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确，transaction_id不存在";
            return false;
        }
        
        // 查询订单，判断订单真实性
        if (! $this->Queryorder($data)) {
            $msg = "订单查询失败";
            return false;
        }
        
        // 纪录错误信息
        if (! empty($msg) && $msg !== 'OK') {
            Log::ERROR(sprintf('call NotifyProcess error::%s|file:%s|line:%s', $msg, __FILE__, __LINE__));
        }
        return true;
    }
}

try {
    // 初始化日志
    $logHandler = new CLogFileHandler(Config::PAY_LOG_PATH . date('Y-m-d') . '.log');
    $log = Log::Init($logHandler, Config::PAY_LOG_LEVER);
    
    $log::DEBUG("=============begin notify==============");
    $notify = new PayNotifyCallBack();
    $notify->Handle(true);
    
    // 先判断协议状态return_code,再判断业务状态result_code
    if ($notify->query_data['return_code'] == 'SUCCESS') {
        
        //$data['id'] = $orderId = substr($notify->query_data['out_trade_no'], 14);
        $data['id'] = $orderId = $notify->query_data['out_trade_no'];
        $data['pay_type'] = Config::ORDER_PAY_TYPE;
        $data['pay_status'] = Config::ORDER_PAY_FAILED_CODE;
        if ($notify->query_data['result_code'] == 'SUCCESS') {
            $data['pay_status'] = Config::ORDER_PAY_SUCCESS_CODE;
        }
        //$data['pay_discount'] = Config::ORDER_PAY_DISCOUNT;
        
        // 微信会轮训回调通知接口，更新订单支付状态前需要先查询订单是否已经更改
        $order_info = Order::get_order_by_id($data['id']);
        if ((int) $order_info['pay_status'] !== Config::ORDER_PAY_WAITTINT_CODE) {
            return;
        }
        
        if($notify->request_data['attach'] !== PAY_ENV){
            return ;
        }
        
        $update_result = Order::update_order_status($data, $orderId);
        if ($update_result === true) { // 如果订单支付状态更新成功，则记录支付流水
            $update_suborder = Order::update_suborder_status($data['pay_status'], $orderId);
            $add_result = Trade::add_trade_bill($notify->request_data, $data);
        } else {
            throw new Exception('order pay status update failed');
        }
    } else {
        Log::DEBUG(sprintf('callback error|file:%s|line:%s|require_data:%s', __FILE__, __LINE__, json_encode($notify->request_data)));
    }

} catch (Exception $e) {
    Log::ERROR(sprintf('run exceptin:%s|file:%s|line:%s', $e->getMessage(), $e->getFile(), $e->getLine()));
}

