<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/** 
 * 订单总表
 * @author caochunhui@dachuwang.com
 */
class MOrder extends MY_Model {
    use MemAuto;

    private $_table = 't_order';
    public function __construct () {
        parent::__construct($this->_table);
    }

    /**
     * 根据订单id获取订单信息
     * @author yelongyi@dachuwang.com
     * @since 2015-07-22 17:49:04
     * @param array $order_ids_arr 订单id组成的数组
     * @param array $field 需要取的字段
     * @return bool|array 参数没传返回false，成功返回结果数组
     */
    public function get_orderInfo_by_orderIds($order_ids_arr, $field = array()){
        if(empty($order_ids_arr)) return false;
        if(is_array($field) && !empty($field)){
            $this->db->select(implode(',', $field));
        }else{
            $this->db->select('*');
        }
        $this->db->where_in('id', $order_ids_arr);
        return $this->db->from($this->_table)->get()->result_array();
    }

    /**
     * 获取历史订单总数
     *
     * @param int $who 1大厨 2大果
     * @return boolean int
     * @author zhangxiao@dachuwang.com
     */
    public function count_total_order () {
        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->post('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        return $this->count(array (
                "status !=" => 0,
                'city_id' => $city_id
        ));
    }

    /**
     * 获取订单成交率
     * @return boolean string
     * @author zhangxiao@dachuwang.com
     */
    public function get_order_close_rate () {

        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->post('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        $total_order = $this->count_total_order();
        $close_order = $this->count(array (
                "status" => 1,
                'city_id' => $city_id   //新增城市筛选 wangyang@dachuwang.com
        ));

        $close_order_rate = 0;
        if ($total_order != 0) {
            $close_order_rate = $close_order / $total_order;
        }
        return (round($close_order_rate, 4) * 100);
    }

    /**
     * 获取订单签收率
     *
     * @param int $who 1大厨 2大果
     * @return boolean string
     * @author zhangxiao@dachuwang.com
     */
    public function get_order_signed_rate () {
        $city_id  = $this->input->post('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        $total_order = $this->count_total_order();
        $signed_order = $this->count(array (
                "status" => 6,
                'city_id' => $city_id   //新增城市筛选 wangyang@dachuwang.com
        ));
        $order_signed_rate = 0;
        if ($total_order != 0) {
            $order_signed_rate = $signed_order / $total_order;
        }

        return (round($order_signed_rate, 4) * 100);
    }

    /**
     * 获取总交易额
     * @return boolean int
     * @author zhangxiao@dachuwang.com
     */
    public function get_total_transaction_amount () {

        $city_id  = $this->input->post('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        $where['status !='] = C('order.status.closed.code');
        $where['city_id'] = $city_id;

        $res = $this->get_lists('sum(total_price) as total',$where);

        $total_transaction_amount = $res;
        return $total_transaction_amount[0]['total'];
    }

    /**
     * 获取下单用户数
     * @return boolean int
     * @author zhangxiao@dachuwang.com
     */
    public function get_total_ordered_customer ($param = array(), $check_status = FALSE) {
        if (! empty($param)) {
            $where['created_time >='] = $param['stime'];
            $where['created_time <='] = $param['etime'];
        }
        $this->db->distinct();
        $this->db->select("user_id");
        $this->db->where($where);
        if($check_status){
            $this->db->where('status !=', C('order.status.closed.code'));
        }
        $res = $this->db->get($this->_table);
        return $res->num_rows();
    }

    /**
     * 获取时间段内下单用户UIds
     * @param number $stime
     * @param number $etime
     * @return array
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_ordered_customers ($city_id = 0, $customer_type=0, $stime = 0, $etime = 0) {

        if ($stime != 0 && $etime != 0) {
            $where['created_time >'] = $stime;
            $where['created_time <='] = $etime;
        }
        if($city_id) {
            $where['city_id ='] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }

        $where['status !='] = C('order.status.closed.code');
        $this->db->select('id,order_number,user_id,status,created_time,deliver_date,deliver_time,total_price,customer_type')->from($this->_table);
        $this->db->where($where)->order_by('created_time', 'desc');

        $query = $this->db->get();
        $customers = $this->filer_users($query->result_array());
        $dates = $this->create_sequence_day($stime, $etime);
        $result = $this->_merge_first_ordered_sequence($dates, $customers, $display_list = true);

        return ! empty($result) ? $result : array ();
    }

    /**
     * @param number $stime
     * @param number $etime
     * @return array
     * @author wangyang@dachuwang.com
     */
    public function get_period_ordered_customers ($stime = 0, $etime = 0) {

        if ($stime != 0 && $etime != 0) {
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        $this->db->select('order_number,user_id,status,created_time')->from($this->_table);
        $this->db->where($where)->order_by('created_time', 'desc');

        $query = $this->db->get();
        $customers = $this->filer_users($query->result_array()); // 获得在时间段内下过单的客户

        $period_order_statics = $this->_get_period_first_order_customer($customers, $stime, $etime); // 获取在时间段内下单用户订单数统计
        $period_first_order_customer = 0;
        $period_repurchasae_order_customer = 0;
        foreach ( $period_order_statics as $key => $value ) {
            if ($value['in_period'] != 0 && $value['left_period'] == 0) { // 第一次购买用户统计
                $period_first_order_customer ++;
            } elseif ($value['in_period'] != 0 && $value['left_period'] != 0) { // 复购用户统计
                $period_repurchasae_order_customer ++;
            }
        }
        $return_data['period_order_customers'] = count($customers);
        $return_data['period_first_order_customer'] = $period_first_order_customer;
        $return_data['period_repurchasae_order_customer'] = $period_repurchasae_order_customer;
        return $return_data;
    }

    /**
     * 查询一段时间下单用户在对应时间内订单数
     *
     * @param unknown $uids
     * @return number Ambigous unknown>
     * @author wangyang@dachuwang.com
     */
    public function _get_period_first_order_customer ($customers = array(), $stime, $etime) {

        $return = array ();
        if (is_array($customers) && count($customers) > 0) {
            foreach ( $customers as $key => $value ) {
                $orders_by_uid = $this->_get_oders_data_by_uid($value['user_id']);
                $return[$value['user_id']] = $orders_by_uid;
            }
            return $this->statics_period_order_uid($return, $stime, $etime);
        }
        return FALSE;
    }

    /*
     * 与UID对应的总订单中是这段时间以及这段时间外的订单 @author wangyang@dachuwang.com
     */
    public function statics_period_order_uid ($uid = array(), $stime = 0, $etime = 0) {
        foreach ( $uid as $uid_key => $uid_order ) {
            $data[$uid_key]['in_period'] = 0;
            $data[$uid_key]['left_period'] = 0;
            $data[$uid_key]['right_period'] = 0;
            foreach ( $uid_order as $key => $value ) {
                if ($value['created_time'] > $stime && $value['created_time'] < $etime) {
                    $data[$uid_key]['in_period'] ++;
                } elseif ($value['created_time'] < $stime) {
                    $data[$uid_key]['left_period'] ++;
                } else {
                    $data[$uid_key]['right_period'] ++;
                }
            }
        }
        return $data;
    }

    /*
     * 根据uid来查询订单详情 
     * @author wangyang @dachuwang.com
     */
    public function _get_oders_data_by_uid ($uid = NULL) {
        if (! $uid)
            return FALSE;
        $where['user_id'] = $uid;
        $this->db->select(array (
                'order_number',
                'user_id',
                'status',
                'created_time'
        ));
        $query = $this->db->get_where($this->_table, array (
                'user_id' => $uid,
                'status !=' => 0
        ));
        $orders = $query->result_array();
        return $orders;
    }

    /**
     * 通过批量用户id，返回订单数
     * @author zhangxiao@dachuawng.com
     * @param unknown $uids
     * @param unknown $params
     * @return multitype:
     */
    public function get_order_count_by_uids ($uids = array(), $params = array()) {

        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        if($city_id) {
            $where['city_id = '] = $city_id;
        }

        $where['status !='] = C('order.status.closed.code');
        if (is_array($uids) && ! empty($uids)) {

            $where['created_time < '] = $params['stime'];
            $this->db->select("user_id,count('order_number') as order_count", FALSE)->from($this->_table);

            $this->db->where_in('user_id', $uids)->group_by('user_id');
            $this->db->where($where);
            $query = $this->db->get();

            return $query->result_array();
        }
        return array ();
    }


    /**
     * 获取订单list
     * @param number $stime
     * @param number $etime
     * @param unknown $extra
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_created_orders ($extra = array()) {

        $this->db->select('order_number,user_id,status,created_time,deliver_date,deliver_time,line_id')->from($this->_table);

        if (is_array($extra) && ! empty($extra)) {
            $this->db->where($extra);
        }

        $this->db->order_by('created_time', 'desc');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 查询首次下单用户uids
     *
     * @param unknown $uids
     * @return number Ambigous unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function _get_first_order_customer ($customers = array()) {

        $return = array ();
        if (is_array($customers) && count($customers) > 0) {
            foreach ( $customers as $key => $value ) {
                // 历史数据统计，下单次数=1则首次下单用户数+1
                if (count($value) == 1) {
                    $day = date('Y-m-d', $value[0]['created_time']);
                    $return[$day][] = $value[0];
                }

                // $uid = $this->_get_oders_by_uid($key) ;
                // if (! empty($uid) && isset($value['created_time']) && ! empty($value['created_time'])) {
                // $day = date('Y-m-d', $value['created_time']) ;
                // $return[$day][] = $value ;
                // }
            }
        }
        return ! empty($return) ? $return : array ();
    }



    /**
     * ription 时间段内用户订单总数
     *
     * @param array $condition
     * @param string $display_list
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_period_orders ($condition = array(), $display_list = false) {

        $this->db->select('order_number,created_time,user_id,status,total_price')->from($this->_table);

        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        if($city_id) {
            $where['city_id = '] = $city_id;
        }
        if (isset($condition['stime']) && isset($condition['etime']) ) {
            $where['created_time >='] = $condition['stime'];
            $where['created_time <='] = $condition['etime'];
            $where['status !='] = 0;
            $this->db->where($where);
        }

        $this->db->order_by('created_time', 'desc');
        $query = $this->db->get();

        $result = $this->_filter_orders($query->result_array());
        $data = $this->_merge_order_count_sequence($condition, $result, $display_list);

        return ! empty($data) ? $data : array ();
    }

    /**
     * 将订单按照user_id重组
     *
     * @param unknown $data 订单数据list
     * @return array
     * @author yuanxiaolin@dachuwang.com
     */
    public function filer_users ($data = array()) {

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
     * 根据用户ID列表统计订单数量
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function count_by_cid ($cid_arr) {
        $this->db->select('user_id, count(*) as count');
        $this->db->from('t_order');
        $this->db->where_in('user_id', $cid_arr);
        $this->db->where_in('status', array (
                '1',
        ));
        $this->db->group_by('user_id');
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * 统计一段时间内用户下单总数及交易总额
     * @param unknown $stime
     * @param unknown $etime
     * @return return_type @date : 2015-3-27 下午3:12:42
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function count_customers_by_time ($stime, $etime) {
        $where['status !='] = C('order.status.closed.code');
        if (! empty($etime) && ! empty($stime)) {
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        $this->db->select('user_id,order_number,count(order_number) as order_count,sum(total_price) as total_price,created_time');
        $this->db->from($this->_table);
        $this->db->where($where);
        $this->db->group_by('user_id');
        $result = $this->db->get()->result_array();
        return ! empty($result) ? $result : array ();
    }

    /**
     * 统计去重订单的客户id及订单总数
     * @param unknown $customers uids
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function count_distinct_order_customers($customers){

        $where['status !='] = C('order.status.closed.code');
        $this->db->select('user_id,count(distinct(concat(user_id, "_", deliver_date, "_", deliver_time))) cnt',FALSE);
        $this->db->from($this->_table)->where_in('user_id',$customers)->where($where);
        $query=$this->db->group_by('user_id')->get();
        $result = $query->result_array();

        return  !empty($result) ? $result : array();
    }

    /**
     * 根据customer_uids查询客户订单信息
     * @param number $stime 起始点时间戳
     * @param number $etime 结束点时间戳
     * @param unknown $customer_uids 下单客户uids
     * @return Ambigous <multitype:, unknown>
     */
    public function get_orders_by_uids($stime = 0, $etime = 0, $customer_uids = array()){
        $where['status !='] = C('order.status.closed.code');
        $fields = array();
        if(!empty($stime) && !empty($etime)){
            $where['created_time >='] = $stime;
            $where['created_time <='] = $etime;
        }
        if(is_array($customer_uids) && !empty($customer_uids)){
            $where['in']['user_id'] = $customer_uids;
        }
        $lists = $this->get_lists($fields,$where);
        return !empty($lists) ? $lists : array();
    }

    /**
     * 批量更新订单状态
     * @param unknown $order_ids 订单ID list
     * @param unknown $status 要更新的状态
     * @return number
     * @author yuanxiaolin@dachuwang.com
     */
    public function update_batch_orders_status($order_ids,$status){

        $update_data = array();
        if(is_array($order_ids) && !empty($order_ids)){
            foreach ($order_ids as $key => $value){

                $update_data[$key]['id'] = $value;
                $update_data[$key]['status'] = $status;
                $update_data[$key]['updated_time'] = time();
            }
            $this->db->trans_begin();

            $this->db->update_batch($this->_table,$update_data,'id');

            if ($this->db->trans_status() === FALSE)
            {
                $this->db->trans_rollback();
                return 0;
            }
            else
            {
                $this->db->trans_commit();
                return count($order_ids);
            }
        }

    }

    /**
     * 统计用户首单
     * @author yugang@dachuwang.com
     * @since 2015-06-12
     * @return 每个用户的首单日期以及距离现在的天数
     */
    public function count_first_order($user_id_arr) {
        $cur_time = time();
        $first_orders = $this->get_lists('user_id, min(created_time) min_time', ['in' => ['user_id' => $user_id_arr], 'status !=' => C('order.status.closed.code')], [], ['user_id']);
        foreach ($first_orders as &$order){
            $day = intval(($cur_time - $order['min_time']) / 86400);
            $order['first_order_day'] = $day;
        }
        return $first_orders;
    }

    /**
     * 获取首单超过X天的客户列表
     * @author yugang@dachuwang.com
     * @since 2015-06-12
     */
    public function get_user_by_first_order($is_over = TRUE, $day = 7) {
        $min_time = time() - $day * 24 * 3600;
        $having = $is_over ? ['min_time <=' => $min_time] : ['min_time >' => $min_time];
        $user_list = $this->db->select('user_id, min(created_time) min_time')
            ->from('t_order')
            ->where(['status !=' => C('order.status.closed.code')])
            ->group_by('user_id')
            ->having($having)
            ->get()
            ->result_array();
        $sql = $this->db->last_query();
        return empty($user_list) ? [-1] : array_column($user_list, 'user_id');
    }

    /**
     * 获取用户订单
     *
     * @param string $uid
     * @return number Ambigous unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_oders_by_uid ($uid = NULL) {
        if (! $uid)
            return FALSE;
        $where['user_id'] = $uid;
        $this->db->select(array (
                'order_number',
                'user_id',
                'created_time'
        ));
        $query = $this->db->get_where($this->_table, array (
                'user_id' => $uid
        ), 10, 0);
        $orders = $query->result_array();
        return count($orders) > 1 ? FALSE : $orders;
    }


    /**
     * 获取连续日期时间的首次下单用户
     *
     * @param unknown $dates
     * @param unknown $data
     * @param string $display_list
     * @return multitype: Ambigous number>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _merge_first_ordered_sequence ($dates = array(), $data = array()) {

        if (empty($dates)) {
            return array ();
        }
        //将所有订单按日期分组
        $day_customers = array ();
        if (is_array($data) && ! empty($data)) {
            foreach ( $data as $key => $value ) {
                foreach ( $value as $k => $v ) {
                    $date = date('Y-m-d', $v['created_time']);
                    $day_customers[$date][$key][] = $v;
                }
            }
        }
        for ($i = 0; $i < count($dates); $i ++) {
            if (isset($day_customers[$dates[$i]])) {
                $current_day_uids = array_keys($day_customers[$dates[$i]]);
                $param['stime'] = strtotime($dates[$i]);
                $before_day = $this->get_order_count_by_uids($current_day_uids, $param);
                $ago_reorderd_count = array ();
                if (! empty($before_day)) {
                    foreach ( $before_day as $key => $value ) {
                        $ago_reorderd_count[$value['user_id']] = $value['order_count'];
                    }
                }

            }
            $again_customers = array ();
            $first_customers = array ();

            $again_amount = 0;
            $first_amount = 0;
            if (! empty($day_customers[$dates[$i]])) {
                foreach ( $day_customers[$dates[$i]] as $key => $value ) {
                    if (! empty($ago_reorderd_count[$key])) {
                        $again_customers[$key] = $value;//当天之前下过单，是复购用户
                        foreach($value as $v){
                            $again_amount += $v['total_price'];
                        }
                    } else if (count($value) == 1) {
                        $first_customers[$key] = $value;//当天之前没有下过单，并且只有一个订单，是首次下单用户
                        foreach($value as $v){
                            $first_amount += $v['total_price'];
                        }
                    } else if ($this->_if_same_deliver($value)) {
                        $first_customers[$key] = $value;//当天之前没有下过单，而当天有多个订单，且都是相同的配送时段，是首次下单用户
                        foreach($value as $v){
                            $first_amount += $v['total_price'];
                        }
                    } else {
                        $again_customers[$key] = $value;//当天之前没有下过单，而当天有多个订单，且属于不同配送时段，是首购用户也是复购用户,
                        $first_customers[$key] = $value;
                        foreach($value as $v){
                            $again_amount += $v['total_price'];
                            $first_amount += $v['total_price'];
                        }
                    }
                }
            }

            //复购订单数据
            $merge_data[$dates[$i]]['again_ordered_count'] = 0;
            $merge_data[$dates[$i]]['again_amount'] = $again_amount;
            if (! empty($again_customers)) {
                $merge_data[$dates[$i]]['again_ordered_count'] = count($again_customers);
            } 

            //首购订单数据
            $merge_data[$dates[$i]]['first_ordered_count'] = 0;
            $merge_data[$dates[$i]]['first_amount'] = $first_amount;
            if (! empty($first_customers)) {
                $merge_data[$dates[$i]]['first_ordered_count'] = count($first_customers);
            }
        }

        return ! empty($merge_data) ? $merge_data : array ();
    }

    /**
     * merge连续天数的数据
     *
     * @param unknown $param 时间段参数(stime,etime)
     * @param unknown $data 每天订单list
     * @param boolean $display_list 是否显示订单列表
     * @return array('order_day_count'=>每日下单总数，)
     * @author yuanxiaolin@dachuwang.com
     */
    private function _merge_order_count_sequence ($param = array(), $data, $diplay_list = false) {

        $merge_data = array ();
        if (isset($param['stime']) && isset($param['etime'])) {
            $sdate = date('Y-m-d', $param['stime']);
            $edate = date('Y-m-d', $param['etime']);

            if ($sdate == $edate) {
                $merge_data[$sdate]['order_day_count'] = isset($data[$sdate]) ? count($data[$sdate]) : 0;
                $merge_data[$sdate]['ordered_customer_count'] = count($this->filer_users($data));
                $merge_data[$sdate]['ordered_trade_total'] = isset($data[$sdate]) ? $data[$sdate]['total_price'] : 0;
                $merge_data[$sdate]['order_success_count'] = $this->_get_order_status_count('success', $data[$sdate]);
                $merge_data[$sdate]['order_singed_count'] = $this->_get_order_status_count('wait_comment', $data[$dates[$i]]);
                if ($diplay_list === true) {
                    $merge_data[$sdate]['order_day_lists'] = isset($data[$sdate]) ? $data[$sdate] : array ();
                }

            } else {
                $dates = $this->_create_sequence_day($param['stime'], $param['etime']);
                for ($i = 0; $i < count($dates); $i ++) {
                    if (isset($data[$dates[$i]]) && ! empty($data[$dates[$i]])) {
                        $merge_data[$dates[$i]]['order_day_count'] = count($data[$dates[$i]]);
                        $merge_data[$dates[$i]]['ordered_customer_count'] = count($this->filer_users($data[$dates[$i]]));
                        $merge_data[$dates[$i]]['ordered_trade_total'] = $this->_get_day_trade_total($data[$dates[$i]]);
                        $merge_data[$dates[$i]]['order_success_count'] = $this->_get_order_status_count('success', $data[$dates[$i]]);
                        $merge_data[$dates[$i]]['order_singed_count'] = $this->_get_order_status_count('wait_comment', $data[$dates[$i]]);
                        if ($diplay_list === true) {
                            $merge_data[$dates[$i]]['order_day_lists'] = $data[$dates[$i]];
                        }

                    } else {
                        $merge_data[$dates[$i]]['order_day_count'] = 0;
                        $merge_data[$dates[$i]]['ordered_customer_count'] = 0;
                        $merge_data[$dates[$i]]['ordered_trade_total'] = 0;
                        $merge_data[$dates[$i]]['ordered_trade_total'] = 0;
                        $merge_data[$dates[$i]]['order_success_count'] = 0;
                        $merge_data[$dates[$i]]['order_singed_count'] = 0;
                        if ($diplay_list === true) {
                            $merge_data[$dates[$i]]['order_day_lists'] = array ();
                        }
                    }
                }
            }
        }

        return ! empty($merge_data) ? $merge_data : array ();
    }

    /**
     * 统计每日订单签收数和订单成功数
     *
     * @param string $tag
     * @param unknown $data
     * @return number
     */
    private function _get_order_status_count ($tag = 'success', $data = array()) {

        $status_orders = 0;
        if (is_array($data) && count($data) > 0) {
            foreach ( $data as $key => $value ) {

                if (isset($value['status']) && $value['status'] == C('order.status.' . $tag . '.code')) {
                    $status_orders ++;
                }
            }
        }

        return $status_orders;
    }

    /**
     * 计算交易总额
     *
     * @param unknown $data
     * @return Ambigous <number, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_day_trade_total ($data = array()) {

        $total_price = 0;
        if (is_array($data) && count($data) > 0) {
            foreach ( $data as $key => $value ) {
                if (isset($value['total_price']) && ! empty($value['total_price'])) {
                    $total_price += $value['total_price'];
                }
            }
        }

        return $total_price;
    }

    /**
     * 生成连续日期
     *
     * @param number $stime 时间戳起点
     * @param number $etime 时间戳终点
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _create_sequence_day ($stime = 0, $etime = 0) {

        if ($stime && $etime && $stime !== $etime) {
            $start_date = date('Y-m-d', $stime);
            $end_date = date('Y-m-d', $etime);
            $date = $start_date;
            $dates[] = $start_date;
            $time = $stime;
            while ( $date != $end_date ) {
                $date = date('Y-m-d', strtotime('+1 days', $time));
                $time += 3600 * 24;
                array_push($dates, $date);
            }
        }

        return ! empty($dates) ? $dates : array ();
    }



    /**
     * 过滤无效订单并按年-月-日组装订单数据
     *
     * @param array $data 订单数据list
     * @return multitype: Ambigous unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _filter_orders ($data = array()) {

        $orders = array ();
        if (count($data) < 1)
            return array ();
        foreach ( $data as $key => $value ) {

            if (isset($value['created_time']) && ! empty($value['created_time'])) {
                $day = date('Y-m-d', $value['created_time']);
                $orders[$day][] = $value;
            }
        }
        return $orders;
    }

    /**
     * 判断当天用户订单是否相同配送时段
     *
     * @param unknown $data 用户订单list
     * @return boolean @date : 2015-3-30 下午8:35:03
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    private function _if_same_deliver ($data) {

        $deliver = [];
        if (is_array($data) && count($data) > 1) {
            foreach ( $data as $value ) {
                $deliver[] = $value['deliver_date'] .'-'.$value['deliver_time'];
            }
        }
        $deliver = array_unique($deliver);

        if (count($deliver) > 1) {
            return false;
        } else {
            return true;
        }
    }

}
/* End of file morder.php */
/* Location: :./application/models/morder.php */
