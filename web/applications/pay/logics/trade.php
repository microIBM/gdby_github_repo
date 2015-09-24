<?php

require_once 'common.php';

/**
 * 交易相关业务逻辑
 * 
 * @author lenjent
 *        
 */
class Trade extends Common {
    
    /**
     * 记录交易流水
     * @param unknown $bills_data 支付交易信息
     * @param unknown $pay_info 支付状态信息
     * @throws Exception
     * @return boolean
     * @author yuanxiaolin@dachuwang.com
     */
    public static function add_trade_bill ($bills_data = array(), $pay_info = array()) {
        $log = Common::LogInit();
        $data = array ();
        if (empty($bills_data) || empty($pay_info)) {
            throw new Exception('bills_data or pay_info required, but one of is empty');
        }
        
        // 支付状态信息
        $data['order_id'] = $pay_info['id'];
        $data['pay_type'] = $pay_info['pay_type'];
        $data['pay_status'] = $pay_info['pay_status'];
        // $data['pay_discount'] = $pay_info['pay_discount'];
        
        // 订单交易信息
        $data['transaction_id'] = $bills_data['transaction_id'];
        $data['trade_no'] = $bills_data['out_trade_no'];
        $data['total_fee'] = $bills_data['total_fee'];
        $data['cash_fee'] = $bills_data['cash_fee'];
        $data['created_time'] = $data['updated_time'] = time();
        $data['full_data'] = $bills_data;
        $post_data['add_fields'] = serialize($data);
        $result = Common::DoApi(Config::API_ADD_PAY_BILLS, $post_data, 'POST');
        
        // 记录新增支付流水debug日志
        $log::DEBUG(sprintf('call trade::add_trade_bill api info:|api_url:%s|post_data:%s|api_return:%s', Config::API_ADD_PAY_BILLS, json_encode($post_data), json_encode($result)));
        if (isset($result['status']) && $result['status'] == Config::API_SUCCESS_CODE) {
            return true;
        } else {
            $log::ERROR(sprintf('call trade::add_trade_bill api error|api_url:%s|post_data:%s|api_return:%s', Config::API_ADD_PAY_BILLS, json_encode($post_data), json_encode($result)));
            return false;
        }
    }
}