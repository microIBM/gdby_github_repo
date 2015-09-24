<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * BD 数据处理Model
 *
 * @author lenjent
 *        
 */
class MBDModel extends MY_Model {
    
    private $_order_table = 't_order';
    private $_customer_table = 't_customer';
    public function __construct () {
        $this->load->model('MOrder');
        parent::__construct();
    }
    
    /**
     * 获取和检查接口请求参数bd_uids
     *
     * @throws Exception
     * @return multitype:array
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_bd_uids () {
        
        $bd_uids = $this->input->post('bd_uids');
        if (empty($bd_uids)) {
            throw new Exception('bd_uids required');
        } else {
            $bd_uids = explode('-', $bd_uids);
        }
        return $bd_uids;
    }
    
    /**
     * 获取和检查接口请求参数site_id
     *
     * @throws Exception
     * @return number
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_site_id () {
        
        $site_id = $this->input->post('site_id');
        if (empty($site_id)) {
            throw new Exception('site_id required');
        }
        return $site_id;
    }
    
    /**
     * 统计时间段内BD名下顾客数等绩效
     *
     * @param number $stime
     *            开始时间
     * @param number $etime            
     * @param unknown $orders
     *            每个客户的订单数据
     * @param unknown $bd_to_customers
     *            bd名下的客户数据
     * @return Ambigous <multitype:, number>
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_custoers_num ($stime = 0, $etime = 0, $orders = array(), $bd_to_customers = array()) {
        
        $customer_orders = array ();
        $bd_customer_orders = array ();
        $data = array ();
        if (is_array($orders) && ! empty($orders)) {
            $customer_orders = $this->filter_users_data($orders);
        }
        if (is_array($bd_to_customers) && ! empty($bd_to_customers)) {
            foreach ( $bd_to_customers as $key => $value ) {
                $bd_customer_orders[$key] = $this->_get_bd_orders($customer_orders, $value); // 查询BD所有客户的订单数据
            }
        }
        
        if (! empty($bd_customer_orders)) {
            $data = $this->_get_bd_performance($bd_customer_orders, $stime, $etime);
        }
        
        return $data;
    }
    
    public function get_history_customers_num ($site_id, $bd_to_customers) {
        
        $where['site_id'] = $site_id;
        $where['status !='] = C('order.status.closed.code');
        
        // print_R($bd_to_customers);exit;
    
    }
    
    /**
     * 提取所有BD名下的customer_uids
     *
     * @param unknown $bd_customers
     *            BD对应的customer数组
     * @throws Exception
     * @return multitype:
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_customer_uids ($bd_customers = array()) {
        
        $customer_uids = array ();
        if (is_array($bd_customers) && ! empty($bd_customers)) {
            foreach ( $bd_customers as $key => $value ) {
                $uids = array_keys($value);
                $customer_uids = array_merge($customer_uids, $uids);
            }
        } else {
            throw new Exception('bd_customers required');
        }
        
        return ! empty($customer_uids) ? $customer_uids : array ();
    
    }
    /**
     * 获取每个BD的增量客户数
     *
     * @param unknown $invite_id
     *            BD UID
     * @param unknown $stime            
     * @param unknown $etime            
     * @return Ambigous <number, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_increase_customers ($invite_id, $stime = 0, $etime = 0) {
        
        $where['invite_id'] = $invite_id;
        $where['status !='] = C('customer.status.invalid.code');
        if (! empty($stime) && ! empty($etime)) {
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        $this->db->from($this->_customer_table);
        $this->db->where($where)->group_by('invite_id');
        $count = $this->db->count_all_results();
        return ! empty($count) ? $count : 0;
    }
    
    /**
     * 组装相同用户数据
     *
     * @param unknown $data
     *            订单数据list
     * @return array
     * @author yuanxiaolin@dachuwang.com
     */
    public function filter_users_data ($data = array()) {
        
        $customers = array ();
        $customers_list = array ();
        if (is_array($data) && count($data) > 0) {
            foreach ( $data as $key => $value ) {
                if (! empty($value['user_id'])) {
                    $customers[$value['user_id']][] = $value;
                }
            }
        }
        
        return ! empty($customers) ? $customers : array ();
    }
    
    /**
     * 计算BD绩效汇总
     *
     * @param unknown $bd_customers_orders            
     * @param unknown $stime            
     * @return Ambigous <multitype:, number>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_bd_performance ($bd_customers_orders, $stime, $etime) {
        
        $bd_performance_data = array ();
        if (is_array($bd_customers_orders) && ! empty($bd_customers_orders)) {
            foreach ( $bd_customers_orders as $key => $value ) {
                
                $bd_customer_order_count = 0;
                $bd_customers_first_count = 0;
                $bd_customers_again_count = 0;
                $bd_customer_total_history_count = 0;
                foreach ( $value as $k => $v ) {
                    if (count($v) > 0) {
                        $bd_customer_order_count ++;
                        $first_customer = $this->_if_first_customer($k, $v, $stime);
                        if ($first_customer === true) {
                            $bd_customers_first_count ++;
                        } elseif ($first_customer === false) {
                            $bd_customers_again_count ++;
                        } else if ($first_customer == 'double') {
                            $bd_customers_first_count ++;
                            $bd_customers_again_count ++;
                        }
                    
                    }
                }
                $bd_customer_total_count = $this->get_increase_customers($key, $stime, $etime);
                $bd_customer_total_history_count = $this->get_increase_customers($key);
                $bd_performance_data[$key]['bd_customers_total_history_count'] = $bd_customer_total_history_count; // 历史顾客总数
                $bd_performance_data[$key]['bd_customers_total_count'] = $bd_customer_total_count; // 顾客数
                $bd_performance_data[$key]['bd_customers_order_count'] = $bd_customer_order_count; // 下单顾客数
                $bd_performance_data[$key]['bd_customers_first_count'] = $bd_customers_first_count; // 首购顾客数
                $bd_performance_data[$key]['bd_customers_again_count'] = $bd_customers_again_count; // 复购顾客数
            }
        }
        return $bd_performance_data;
    }
    
    /**
     * 根据客户订单数据判断是否是首购用户
     *
     * @param unknown $user_id
     *            下单客户uid
     * @param unknown $orders
     *            客户订单数据
     * @param unknown $stime
     *            查询起始时间，用于查询改时间之前顾客下单情况
     * @return boolean
     * @author yuanxiaolin@dachuwang.com
     */
    private function _if_first_customer ($user_id, $orders, $stime) {
        
        $where['user_id'] = $user_id;
        $where['created_time <'] = $stime;
        $where['status !='] = C('order.status.closed.code');
        $count = $this->_count($this->_order_table, $where);
        $order_count = count($orders);
        
        if ($count == 0 && $order_count == 1) {
            return true;
        }
        if ($count == 0 && $order_count > 1) { // 以前没有下单纪录，当前有多单，如是相同配送时段，是首购用户，否则既是复购用户，也是首购用户
            if ($this->_if_same_deliver($orders)) {
                return true; // 如是相同配送时段，是首购用户
            } else {
                return 'double'; // 否则既是复购用户，也是首购用户
            }
        }
        if ($count > 0 && $order_count > 0) { // 以前有下单纪录，当前也有下单纪录，是复购用户
            return false;
        }
    
    }
    
    /**
     * 统计起始时间之前顾客下单的总数
     *
     * @param unknown $table            
     * @param unknown $where            
     * @return number
     * @author yuanxiaolin@dachuwang.com
     */
    private function _count ($table, $where = array()) {
        if (empty($table)) {
            return 0;
        }
        $this->db->select("count(*) as numrows", FALSE);
        $this->db->from('t_order');
        $this->db->where($where);
        return $this->db->count_all_results();
    }
    
    /**
     * 获取BD名下的客户下单数据
     *
     * @param unknown $customer_orders            
     * @param unknown $customers            
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_bd_orders ($customer_orders, $customers) {
        
        if (is_array($customers) && ! empty($customers)) {
            foreach ( $customers as $key => $value ) {
                if (isset($customer_orders[$key])) {
                    $customers[$key] = $customer_orders[$key];
                } else {
                    $customers[$key] = array ();
                }
            }
        }
        return $customers;
    }
    
    /**
     * 判断当天用户订单是否相同配送时段
     *
     * @param unknown $data
     *            用户订单list
     * @return boolean @date : 2015-3-30 下午8:35:03
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    private function _if_same_deliver ($data) {
        
        $deliver_date = array ();
        $deliver_time = array ();
        if (is_array($data) && count($data) > 1) {
            foreach ( $data as $value ) {
                if (! in_array($value['deliver_date'], $deliver_date)) {
                    $deliver_date[] = $value['deliver_date'];
                } else {
                    $deliver_time[] = $value['deliver_time'];
                }
            }
        }
        
        $date_count = count($deliver_date);
        $time_count = count($deliver_time);
        if ($date_count > 1 || $time_count > 1) {
            return false;
        } elseif ($date_count == 1 || $time_count == 1) {
            return true;
        }
    }

}
/* End of file mbdmodel.php */
/* Location: :./application/models/mbdmodel.php */
