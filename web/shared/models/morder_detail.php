<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author caochunhui@dachuwang.com
 * @description 订单详情表
 */

class MOrder_detail extends MY_Model {
    use MemAuto;
    private $_table = 't_order_detail';
    public function __construct() {
        parent::__construct($this->_table);
    }
    /**
     * 得到某段时间内商品sku排行的数据
     * @author zhangxiao@dachuwang.com
     * @param number $stime
     * @param number $etime
     * @param number $offset
     * @param number $pagesize
     * @return array
     */
    public function get_period_sku_rank_data($status, $stime = 0, $etime = 0, $offset = 0, $pagesize = 0, $search = array()) {
        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');
        $where['city_id'] = $city_id;

        if($stime != 0 && $etime != 0) {
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        if($status == C('order.status.closed.code')) {
            $where['status !='] = C('order.status.closed.code');
            $this->db->select_sum('quantity');   
        } elseif($status == C('order.status.success.code')) {
            //$where['status ='] = C('order.status.success.code');
            $this->db->where('(status ='.C('order.status.success.code').' OR status ='.C('order.status.wait_comment.code').')', NULL, FALSE);
            $this->db->select_sum('actual_quantity');
        }
        $this->db->select('sku_number');
        if(isset($where)) {
            $this->db->where($where);
        }
        $this->db->select_sum('actual_sum_price');
        $this->db->group_by('sku_number');
        $this->db->order_by('actual_sum_price','desc');
        if($pagesize >= 0) {
            $this->db->limit($offset, $pagesize);
        }
        if(!empty($search)){
            $this->db->where_in('sku_number', $search);
        }else{
            return array();
        }
        $query = $this->db->get($this->_table); 
        $res = $query->result_array();

        return $res;
    }


    /**
     * 得到某段时间内商品sku排行的order_id
     * @author wangyang@dachuwang.com
     */
    public function get_period_sku_order_id($status, $stime = 0, $etime = 0, $offset = 0, $pagesize = 0, $search = array()) {
        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');
        $where['city_id'] = $city_id;

        if($stime != 0 && $etime != 0) {
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        if($status == C('order.status.closed.code')) {
            $where['status !='] = C('order.status.closed.code');
        } elseif($status == C('order.status.success.code')) {
            $where['status ='] = C('order.status.success.code');
        }
        $this->db->select('sku_number, order_id, status');
        if(isset($where)) {
            $this->db->where($where);
        }
        if($pagesize >= 0) {
            $this->db->limit($offset, $pagesize);
        }
        if(!empty($search)){
            $this->db->where_in('sku_number', $search);
        }else{
            return array();
        }
        $query = $this->db->get($this->_table); 
        $res = $query->result_array();
        return $res;
    }

    /*
     *描述：获取sku排行
     *@author：wangyang@dachuwang.com
     * */
    public function get_sku_ranks($params = array()){
        $extra ['status !='] = C('order.status.closed.code'); //无效订单
        
        if(isset($params['stime']) && !empty($params['stime'])){
            $extra['created_time >='] = $params['stime'];
        }
        if(isset($params['etime']) && !empty($params['etime'])){
            $extra['created_time <='] = $params['etime'];
        }

        $sku_info =  $this->get_sku_info($extra);
        $orders_id_sku_numbers = $this->get_orders_id($sku_info);
        $data['sku_info'] = $sku_info;
        $data['orders_id'] = $orders_id_sku_numbers['orders_id'];
        $data['sku_numbers'] = $orders_id_sku_numbers['sku_numbers'];
        return $data;
    }

    /*
     *描述：根据sku信息进行order_id提取
     * @author:wangyang@dachuwang.com
     * */ 
    public function get_orders_id($data = array()){
        if (empty($data)) return FALSE;
        $orders_id = array();
        $sku_numbers    = array();
        $return_data    = array();
        foreach($data as $key => $value){
            if(!in_array($value['order_id'], $orders_id)){
                $orders_id[] = $value['order_id'];
            }
            if(!in_array($value['sku_number'], $sku_numbers)){
                $sku_numbers[] = $value['sku_number'];
            }
        }
        $return_data['orders_id'] = $orders_id;
        $return_data['sku_numbers'] = $sku_numbers;
        return $return_data;
    }

    /*
     *描述：获取sku 信息
     *@author:wangyang@dachuwang.com
     * */
    public function get_sku_info($extra = array()){
        $this->db->select('order_id,quantity,sum_price,sku_number,status')->from($this->_table);
        if(is_array($extra) && !empty($extra)){
            $this->db->where($extra);
        }
        $query = $this->db->get();
        return  $query->result_array();
    }

    /**
     * 根据sku、下单时间、城市id获取该sku的订单数据
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param array $sku_numbers_arr sku数组
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $city_id 城市id
     * @param string $by_type 以哪个时间来分析订单.默认按照created_time,也可以是订单完成时间complete_time
     * @param array $field 需要取的字段
     * @return bool|array 参数没传返回false，成功返回结果数组
     */
    public function get_order_by_skus($sku_numbers_arr, $startTime, $endTime, $city_id, $by_type= 'created_time', $field = array()){
        if(empty($sku_numbers_arr)) return false;
        $startTime = $startTime ?: strtotime(date('Y-m-d 00:00:01'));
        $endTime = $endTime ?: strtotime(date('Y-m-d 23:59:59'));
        $city_id = $city_id ?: C('open_cities.beijing.id');

        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        if(!empty($city_id)){
            $this->db->where('city_id', $city_id);
        }
        $this->db->where_in('sku_number', $sku_numbers_arr);
        if($by_type == 'created_time'){
            $this->db->where('created_time >=', $startTime);
            $this->db->where('created_time <=', $endTime);
        }else{
            $this->db->where('complete_time >=', $startTime);
            $this->db->where('complete_time <=', $endTime);
        }
        return $this->db->from($this->_table)->get()->result_array();
    }

    /**
     * 根据时间获取order_detail信息
     * @param $start_time
     * @param $end_time
     * @param array $fields
     * @return bool|array
     */
    public function get_order_by_time($start_time, $end_time, $fields = array()){
        if(empty($start_time) || empty($end_time)){
            return false;
        }
        if(!empty($fields)){
            $this->db->select(implode(',', $fields));
        }
        $this->db->where('status !=', 0);
        $this->db->where('created_time >=', $start_time);
        $this->db->where('created_time <=', $end_time);
        return $this->db->from($this->_table)->get()->result_array();
    }
}

/* End of file morder_detail.php */
/* Location: :./application/models/morder_detail.php */
