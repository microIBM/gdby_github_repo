<?php

require_once 'common.php';

/**
 * 订单相关的业务逻辑
 *
 * @author lenjent
 */
class Order extends Common {

    /**
     * 通过订单序列号获取订单详情
     * 
     * @param string $order_number
     * @author yuanxiaolin@dachuwang.com
     */
    public static function get_order_by_order_number ($order_number = NULL) {

        $log = Common::LogInit();
        $order_info = array ();
        if (! empty($order_number)) {
            $post_data['order_number'] = $order_number;
            $result = self::DoApi(Config::API_ORDER_INFO, $post_data, 'POST');
            if (isset($result['status']) && $result['status'] == Config::API_SUCCESS_CODE) {
                $order_info = $result['data'];
            } else {
                $log::ERROR(sprintf('call Order::get_order_by_order_number api error|api_url:%s|post_data:%s|api_return:%s', Config::API_ORDER_INFO, json_encode($post_data), json_encode($result)));
            }
        } else {
            throw new Exception('order_number required,but empty be given');
        }
        return ! empty($order_info) ? $order_info : array ();
    }

    /**
     * 更新订单支付状态相关信息
     * @param unknown $status 支付状态相关信息 array(order_id,pay_type,pay_status,pay_discount)
     * @param unknown $order_id 对应订单ID
     * @throws Exception
     * @return boolean
     * @author yuanxiaolin@dachuwang.com
     */
    public static function update_order_status ($status, $order_id) {

        $log = Common::LogInit();
        if (! empty($status) && ! empty($order_id)) {
            $post_datas['fields'] = serialize($status);
            $post_datas['where'] = serialize(array (
                'id' => $order_id 
            ));
            $result = self::DoApi(Config::API_ORDER_UPDATE, $post_datas, 'POST');

            // 记录更新订单状态debug信息
            $log::DEBUG(sprintf('call Order::update_order_status api info|api_url:%s|post_data:%s|api_return:%s', Config::API_ORDER_UPDATE, json_encode($post_datas), json_encode($result)));
            if (isset($result['status']) && $result['status'] == Config::API_SUCCESS_CODE) {
                return true;
            } else {
                $log::ERROR(sprintf('call Order::update_order_status api error|api_url:%s|post_data:%s|api_return:%s', Config::API_ADD_PAY_BILLS, json_encode($post_datas), json_encode($result)));
                return false;
            }
        } else {
            throw new Exception('statuses and order_id required ,but one of thems is empty');
        }
    }
    
    /**
     * 支付状态回写到子单
     * @param unknown $status
     * @param unknown $order_id
     * @throws Exception
     * @return boolean
     * @author yuanxiaolin@dachuwang.com
     */
    public static function update_suborder_status($status,$order_id){
        $log = Common::LogInit();
        if (! empty($status) && ! empty($order_id)) {
            $post_datas['pay_status'] = $status;
            $post_datas['order_id'] = $order_id;
            $result = self::DoApi(Config::API_SUBORDER_UPDATE, $post_datas, 'POST');
            // 记录更新订单状态debug信息
            $log::DEBUG(sprintf('call Order::update_suborder_status api info|api_url:%s|post_data:%s|api_return:%s', Config::API_SUBORDER_UPDATE, json_encode($post_datas), json_encode($result)));
            if (isset($result['status']) && $result['status'] == Config::API_SUCCESS_CODE) {
                return true;
            } else {
                $log::ERROR(sprintf('call Order::update_suborder_status api error|api_url:%s|post_data:%s|api_return:%s', Config::API_SUBORDER_UPDATE, json_encode($post_datas), json_encode($result)));
                return false;
            }
        } else {
            throw new Exception('status and order_id required ,but one of themes is empty');
        }
    }
    
    /**
     * 通过订单ID获取订单信息
     *
     * @param unknown $order_id
     * @throws Exception
     * @return Ambigous <>|multitype:
     * @author yuanxiaolin@dachuwang.com
     */
    public static function get_order_by_id ($order_id) {

        $log = Common::LogInit();
        if (empty($order_id)) {
            throw new Exception('order_id requried in query order info,but empty be given ');
        }

        $post_data['order_id'] = $order_id;
        $result = self::DoApi(Config::API_ORDER_INFO, $post_data, 'POST');

        if (isset($result['status']) && $result['status'] == Config::API_SUCCESS_CODE) {
            return $result['data'];
        } else {
            $log::ERROR(sprintf('call Order::get_order_by_id api error|api_url:%s|post_data:%s|api_return:%s', Config::API_ORDER_INFO, json_encode($post_data), json_encode($result)));
            return array ();
        }
    }
}