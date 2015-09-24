<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * bi订单统计 合并大厨大果版
 * @author zhangxiao@dachuwang.com
 * @version 2015-07-01
 */
class Order_bi extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
            'MOrder',
            'MPotential_customer',
            'MOrder_detail',
            'MCategory',
            'MCustomer',
            'MUser',
            'MLine'
        ));
    }

    /**
     * 统计页面中每日相关数据（订单数、毛流水、潜在客户、注册客户、下单客户数、首单客户数及金额、复购客户数及金额）统计 
     *  @author zhangxiao@dachuwang.com
     */
    public function statics_by_day() {
        try {
            //订单数、毛流水
            $order_counts_day = $this->_get_order_counts_day();

            $static_data = [];
            if(!empty($order_counts_day)) {
                $date = array_column($order_counts_day, 'date');
                $etime = strtotime($order_counts_day[0]['date']);
                $stime = strtotime($order_counts_day[count($order_counts_day) - 1]['date']);

                //潜在客户数
                $potential_cus_cnt = $this->_get_new_potential_cus_cnt_day($stime, $etime);
                $potential_cus_cnt = $this->_array_trans($potential_cus_cnt,'date');

                //注册客户数
                $resign_cus_cnt = $this->_get_new_resign_cus_cnt_day($stime, $etime);
                $resign_cus_cnt = $this->_array_trans($resign_cus_cnt, 'date');

                //首单客户数及金额、复购客户数及金额
                $first_reordered__cus_cnt = $this->_get_first_reordered_cus_cnt_day($stime, $etime);

                //下单用户数
                $order_cus_cnt = $this->_get_order_cus_cnt_day($stime, $etime);
                $order_cus_cnt = $this->_array_trans($order_cus_cnt, 'date');
                $order_counts_day = $this->_array_trans($order_counts_day, 'date');
                
                foreach($order_counts_day as $key => $value) {
                    
                    switch(date('N',$value['time_stamp'])) {
                        case 1: $week = "周一";break;
                        case 2: $week = "周二";break;
                        case 3: $week = "周三";break;
                        case 4: $week = "周四";break;
                        case 5: $week = "周五";break;
                        case 6: $week = "周六";break;
                        case 7: $week = "周日";break;
                    }
                    $static_data[$key]                          = $value;
                    $static_data[$key]['week']                  = $week;
                    $static_data[$key]['potential_cus_cnt']     = isset($potential_cus_cnt[$key]['potential_cus_cnt'])              ? $potential_cus_cnt[$key]['potential_cus_cnt'] : 0;
                    $static_data[$key]['resign_cus_cnt']        = isset($resign_cus_cnt[$key]['resign_cus_cnt'])                    ? $resign_cus_cnt[$key]['resign_cus_cnt'] : 0;
                    $static_data[$key]['order_cus_cnt']         = isset($order_cus_cnt[$key]['order_cus_cnt'])                      ? $order_cus_cnt[$key]['order_cus_cnt'] : 0;
                    $static_data[$key]['first_ordered_count']   = isset($first_reordered__cus_cnt[$key]['first_ordered_count'])? $first_reordered__cus_cnt[$key]['first_ordered_count'] : 0;
                    $static_data[$key]['first_amount']          = isset($first_reordered__cus_cnt[$key]['first_amount'])? $first_reordered__cus_cnt[$key]['first_amount'] : 0;
                    $static_data[$key]['again_ordered_count']   = isset($first_reordered__cus_cnt[$key]['again_ordered_count'])? $first_reordered__cus_cnt[$key]['again_ordered_count'] : 0;
                    $static_data[$key]['again_amount']          = isset($first_reordered__cus_cnt[$key]['again_amount'])? $first_reordered__cus_cnt[$key]['again_amount'] : 0;

                }
            }

            return $this->_assemble_res('success', $static_data);
        }catch(Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }

    /**
     *  统计页面中一段时间内统计数据
     *  @author zhangxiao@dachuwang.com
     */
    public function statics_period() {
        try {
            $period_data = [];
            //订单数、订单金额
            $order_counts_period = $this->_get_order_counts_period();
            //潜在客户数
            $potential_cus_period = $this->_get_new_potential_cus_cnt_period();
            //注册客户数
            $resign_cus_period = $this->_get_new_resign_cus_cnt_period();
            //下单客户数据、首够、复购客户数
            $first_reordered_cus_period = $this->_get_first_reordered_cus_cnt_period();
            //订单数
            $period_data['period_valid_order_cnt'] = isset($order_counts_period[0]['valid_order_cnt']) ? $order_counts_period[0]['valid_order_cnt'] : 0;
            //订单金额
            $period_data['period_valid_order_amount'] = isset($order_counts_period[0]['valid_order_amount']) ? $order_counts_period[0]['valid_order_amount'] : 0;
            //潜在客户数
            $period_data['period_potential_cus'] = isset($potential_cus_period ) ? $potential_cus_period  : 0;
            //注册客户数
            $period_data['period_resign_cus'] = isset($resign_cus_period ) ? $resign_cus_period : 0;
            //首够、复购客户数
            $period_data = array_merge($period_data, $first_reordered_cus_period );

            return $this->_assemble_res('success', $period_data);
        }catch(Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     *  统计订单来源及其单数
     *  @author wangzejun@dachuwang.com
     */
    public function get_order_resource_count() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $yesterday     = strtotime("-1 day",strtotime(date("Y-m-d")));
        $stime = $stime ? $stime : strtotime(date("Y-m-d", $yesterday) . " 00:00:00");
        $etime = $etime ? $etime : strtotime(date("Y-m-d", $yesterday) . " 23:59:59");
        
        $where = array();
        if ($city_id) {
            $where['city_id'] = $city_id;
        }
        $where['created_time >='] = $stime;
        $where['created_time <='] = $etime;
        $where['status !=']       = 0;

        $data = $this->MOrder->get_lists(array('city_id', 'count(*) as cnt', 'order_resource'), $where, array('city_id' => 'ASC', 'order_resource' => 'ASC'), array('city_id', 'order_resource'));
        return $this->_return_json($data);;
    }

    /**
     *  一段时间内首够用户数、复购用户数
     *  (stime＝0 && etime ＝0) 为统计历史数据
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_first_reordered_cus_cnt_period() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');
        $stime = $stime ? $stime : 0;
        $etime = $etime ? $etime : 0;
        $city_id = $city_id ? $city_id : 0;

        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }

        $data = $this->_get_cus_cnt_period($stime, $etime, $customer_type);

        return $data;
    }
    
    /**
     * 得到一段时间内下单顾客数、首单顾客数、复购客户数
     * @author zhangxiao@dachuawng.com
     */
    
    private function _get_cus_cnt_period($stime, $etime, $customer_type) {

        //一段时间内 用户对应的合并订单数
        $period_data = $this->_cus_order_cnt_period($stime, $etime, $customer_type);
        $period_uids = array_column($period_data, 'user_id');

        $ordered_customers = count($period_data);
        $again_customers   = 0;
        $first_customers   = 0;

        //用户 之前下单情况统计
        $before_period_data = $this->MOrder->get_order_count_by_uids($period_uids, array('stime' => $stime));
        $before_period_uids = array_column($before_period_data, 'user_id');

        foreach($period_data as $value){
            // 当前时间段内用户订单大于1 且之前无下单记录，则是首次下单用户也是复购用户都+1，否则复购用户数+1
            if ($value['cnt'] > 1 ) {
                if (!in_array($value['user_id'], $before_period_uids)) {
                    $again_customers ++;
                    $first_customers ++;
                } else {
                    $again_customers ++;
                }
            }
            // 当前时间段内用户订单等于1 且之前无下单记录，则首次用户数+1，否则复购用户数+1
            if ($value['cnt'] == 1 ) {
                if (!in_array($value['user_id'], $before_period_uids)){
                    $first_customers ++;
                } else {
                    $again_customers ++;
                }
            }
        }
        // 时间段内下单用户总数
        $data['period_ordered_customers_total'] = $ordered_customers;
        // 时间段内重复下单用户数
        $data['period_again_customers_total'] = $again_customers;
        // 时间段内首次下单用户数
        $data['period_first_customer_total'] = $first_customers;
        return $data;
    }

    /**
     * 获取时间段内用户合并订单数
     * @author: zhangxiao@dachuwang.com
     */
    private function _cus_order_cnt_period ($stime = 0, $etime = 0, $customer_type = 0){

        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        if($city_id) {
            $where['city_id'] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        $where['status !='] = C('order.status.closed.code');
        if(!empty($stime) && !empty($etime)){
            $where['created_time >'] = $stime;
            $where['created_time <='] = $etime;
        }
        $cus_order_cnt_period = $this->MOrder->get_lists(
            'count(distinct user_id, deliver_date, deliver_time ) as cnt , user_id',
            $where,
            array(),
            array('user_id')
        );
        return !empty($cus_order_cnt_period) ? $cus_order_cnt_period : array();
    }

    /**
     *  一段时间内新注册用户数
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_new_resign_cus_cnt_period() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        $where['status !='] = 0;
        if($city_id) {
            $where['province_id = '] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }

        $data = $this->MCustomer->count($where);

        return !empty($data) ? $data : 0;
    }

    /**
     *  一段时间内潜在客户数
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_new_potential_cus_cnt_period() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        if($city_id) {
            $where['province_id = '] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }

        $data = $this->MPotential_customer->count($where);

        return !empty($data) ? $data : 0;
    }

    /**
     *  一段时间内订单数、订单金额
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_order_counts_period() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }

        $where = [];
        //排除取消的订单
        $where['status != '] = C('order.status.closed.code');
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }
        if($city_id) {
            $where['city_id = '] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }

        $data = $this->MOrder->get_lists('count(*) as valid_order_cnt, sum(total_price) as valid_order_amount', $where);

        return !empty($data) ? $data : array();
    }

    
    /**
     *  统计下单客户数
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_order_cus_cnt_day($stime = 0, $etime = 0) {
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        $where['status != '] = C('order.status.closed.code');
        if($city_id) {
            $where['city_id = '] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }
        $group_by = array('date');

        $data = $this->MOrder->get_lists('count(distinct(user_id)) as order_cus_cnt, DATE_FORMAT(FROM_UNIXTIME(created_time),"%Y-%m-%d") as date', $where, array(), $group_by);
        
        return !empty($data) ? $data : array();
    }

    /**
     *  统计首购用户、复购用户数、首购流水、复购流水
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_first_reordered_cus_cnt_day($stime = 0, $etime = 0) {
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }

        $data = $this->MOrder->get_ordered_customers($city_id, $customer_type, $stime, $etime);
        return !empty($data) ? $data : array();
    }

    /**
     *  注册客户数
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_new_resign_cus_cnt_day($stime = 0, $etime = 0) {
        $city_id = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        $where['status !='] = 0;
        if($city_id) {
            $where['province_id = '] = $city_id;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }

        $group_by = array('date');

        $data = $this->MCustomer->get_lists('count(*) as resign_cus_cnt, DATE_FORMAT(FROM_UNIXTIME(created_time),"%Y-%m-%d") as date', $where, array(), $group_by);

        return !empty($data) ? $data : array();
    }



    /**
     *  获取每日新增潜在客户数
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_new_potential_cus_cnt_day($stime = 0, $etime = 0) {
        $city_id = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        if($city_id) {
            $where['province_id = '] = $city_id;
        }
        //转化时间，自然日划分的时间
        if($stime || $etime) {
            $time_data = $this->_time_trans($stime, $etime);
            $stime = $time_data['stime'];
            $etime = $time_data['etime'];
        }
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }
        if($customer_type){
            $where['customer_type'] = $customer_type;
        }
        $group_by = array('date');

        $data = $this->MPotential_customer->get_lists('count(*) as potential_cus_cnt, DATE_FORMAT(FROM_UNIXTIME(created_time),"%Y-%m-%d") as date', $where, array(), $group_by);

        return !empty($data) ? $data : array();
    }

    /**
     *  获取每日订单数和订单金额
     *  @author:zhangxiao@dachuwang.com
     */
    private function _get_order_counts_day() {
        $stime         = $this->input->post('stime');
        $etime         = $this->input->post('etime');
        $city_id       = $this->input->post('city_id');
        $customer_type = $this->input->post('customer_type');

        $where = [];
        //排除取消的订单
        $where['status != '] = C('order.status.closed.code');
        if($stime) {
            $where['created_time >='] = $stime;
        }
        if($etime) {
            $where['created_time <']  = $etime;
        }
        if($city_id) {
            $where['city_id = '] = $city_id;
        }
        if($customer_type) {
            $where['customer_type'] = $customer_type;
        }
        $group_by = array('date');
        $order_by = array('time_stamp' => 'desc');

        $data = $this->MOrder->get_lists('count(*) as valid_order_cnt, sum(total_price) as valid_order_amount, DATE_FORMAT(FROM_UNIXTIME(created_time),"%Y-%m-%d") as date, UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(created_time), "%Y-%m-%d")) as time_stamp', $where, $order_by, $group_by);

        //生成连续时间的数据
        $data = $this->_trans_continous_data($data, $stime, $etime, $customer_type);
        return !empty($data) ? $data : array();
    }

    /**
     * 生成连续时间的数据
     * @author zhangxiao@dachuwang.com
     */
    private function _trans_continous_data($data, $stime, $etime, $customer_type) {
        $dates = $this->_get_continous_date($stime, $etime);
        $data = $this->_array_trans($data, 'date');

        foreach ($dates as $date) {
            if(!array_key_exists($date, $data) && strtotime($date) < strtotime("now")) {
                $data[$date] = array(
                    'date' => $date,
                    'time_stamp' => strtotime($date),
                    'valid_order_amount' => 0,
                    'valid_order_cnt' => 0
                );
            }
        }
        krsort($data);
        $final_result = array();
        foreach ($data as $value) {
            array_push($final_result, $value);
        }
        return $final_result;
    }

    /**
     * 生成连续日期
     * @author zhangxiao@dachuwang.com
     */
    private function _get_continous_date($stime, $etime) {
        $dates = array();
        $stime = strtotime(date('Y-m-d', $stime));
        for($i = $stime; $i < $etime; $i += 86400) {
            array_push($dates, date('Y-m-d', $i));
        }
        return $dates;
    }
    
    /**
     * 得到用户的下单的所有订单id
     * @author zhangxiao@dachuwang.com
     */
    private function _get_orderids($where) {
        $fields = array('id', 'user_id');
        $result = $this->MOrder->get_lists($fields, $where);
        return $result;
    }
    

    /**
     * 所有客户，即KA客户和普通客户一共的订单数和订单流水和消耗流水
     * @author zhangxiao@dachuwang.com
     */
    private function _get_all_order_by_day($ka_order, $normal_order) {
        $ka_order_cnt_by_date = array_column($ka_order, 'valid_order_cnt', 'date');
        $ka_order_amount_by_date = array_column($ka_order, 'valid_order_amount', 'date');
        foreach ($normal_order as &$value) {
            $value['consumed_amount'] = $value['valid_order_amount'] + $ka_order[0]['consumed_amount'];
            if(array_key_exists($value['date'], $ka_order_cnt_by_date)) {
                $value['valid_order_cnt']    += $ka_order_cnt_by_date[$value['date']];
                $value['valid_order_amount'] += $ka_order_amount_by_date[$value['date']];
            }
            $value['user_ids'] = $this->_get_ka_uids_by_date($ka_order,$value['date']);
        }
        return $normal_order;
    }
    
    private function _get_ka_uids_by_date($ka_order,$date){
        if(!empty($ka_order)){
            foreach ($ka_order as $key => $value){
                if ($value['date'] == $date) {
                    return !empty($value['user_ids']) ? $value['user_ids'] : array();
                }
            }
        }
        return array();
    }
    
    /**
     *  获取有订单的天数
     *  $_POST['stime'] 00:00:00（零点的时间）
     *  $_POST['etime'] 00:00:00（零点的时间）
     *  @author:wangyang@dachuwang.com
     */
    public function get_order_days() {
        try {
            $stime = $this->input->post('stime');
            $etime = $this->input->post('etime');
            $city_id = $this->input->post('city_id');

            //转化时间，自然日划分的时间
            if($stime || $etime) {
                $time_data = $this->_time_trans($stime, $etime);
                $stime = $time_data['stime'];
                $etime = $time_data['etime'];
            }

            $where = [];
            //排除取消的订单
            $where['status != '] = C('order.status.closed.code');
            if($stime) {
                $where['created_time >='] = $stime;
            }
            if($etime) {
                $where['created_time <']  = $etime;
            }
            if($city_id) {
                $where['city_id = '] = $city_id;
            }

            $data = $this->MOrder->get_lists('count(distinct (DATE_FORMAT(FROM_UNIXTIME(created_time),"%Y-%m-%d"))) as total', $where);
            return $this->_assemble_res('success', $data);
        }catch(Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }


    /**
     *  转化自然日的起止时间
     *  @param:$stime 00:00:00（零点的时间）
     *  @param:$etime 00:00:00（零点的时间）
     *  @author:wangyang@dachuwang.com
     */
    private function _time_trans($stime = 0, $etime = 0){
        $data['stime'] = $stime;
        $data['etime'] = 0;
        
        if($etime != 0) {
            $data['etime'] = $etime + 86400;
        }
        
        return $data;
    }
    
    /**
     *  获取下单用户信息用语客户分布
     *  @author wangzejun@dachuwang.com
     */
    public function get_order_customer() {
        $sku_cate_ids  = $this->input->post('sku_cate_ids')?: array(269,303);
        $sku_cate_ids  = $this->get_all_cate_ids($sku_cate_ids);
        $city_id       = $this->input->post('city_id')?: 804;
        $sdate         = $this->input->post('sdate')?: '2015-09-02';
        $edate         = $this->input->post('edate')?: '2015-09-02';
        $customer_info = array();
        //取出下单客户订单，客户，下单金额，下单天数
        $order_customer_lists = $this->MOrder->get_lists(
            array('id', 'user_id'),
            array('date(FROM_UNIXTIME(created_time)) >=' => $sdate, 'date(FROM_UNIXTIME(created_time)) <=' => $edate, 'city_id' => $city_id, 'status !=' => 0)
        );
        if (empty($sku_cate_ids) || empty($order_customer_lists)) {
            $this->_return_json(array('status' => C('status.req.failed'), 'customer_info'=>$customer_info));
        }
        
        //根据品类筛选客户订单
        $order_by_cate = $this->MOrder_detail->get_lists(
            array('order_id','sum(`sum_price`) as amounts', ),
            "`category_id` in (".implode(',', $sku_cate_ids).") and `order_id` in (". implode(',', array_column($order_customer_lists, 'id')).")",
            array(),
            array('order_id')
        );
        if (empty($order_by_cate)) {
            $this->_return_json(array('status' => C('status.req.failed'), 'customer_info'=>$customer_info));
        }
        //取出下单客户订单，客户，下单金额，下单天数
        $day_sum = $this->MOrder->get_lists(
            array('user_id','count(distinct date(FROM_UNIXTIME(created_time))) as day_sum'),
            array('in' => array('id' => array_column($order_by_cate, 'order_id'))),
            array(),
            array('user_id')
        );
        if (empty($day_sum)) {
            $this->_return_json(array('status' => C('status.req.failed'), 'customer_info'=>$customer_info));
        }
        $day_sum = array_column($day_sum, 'day_sum', 'user_id');
        $amounts_lists = array_column($order_by_cate, 'amounts', 'order_id');
        
        $new_order_customer_lists = array();
        $customers = array_unique(array_column($order_customer_lists,'user_id'));
        foreach (array_keys($day_sum) as $index => $customer) {
            $new_order_customer_lists[$index]['user_id'] = $customer;
            $new_order_customer_lists[$index]['amounts'] = 0;
            $new_order_customer_lists[$index]['customer_unit_price'] = 0;
            //获取用户总金额
            foreach ($order_customer_lists as $order_index => $order_customer) {
                if($customer == $order_customer['user_id'] && isset($amounts_lists[$order_customer['id']])) {
                    $new_order_customer_lists[$index]['amounts'] += $amounts_lists[$order_customer['id']];
                    $new_order_customer_lists[$index]['customer_unit_price'] = number_format($new_order_customer_lists[$index]['amounts']/($day_sum[$customer]*100), 2, '.', '');
                }
            }
        }
        if (empty($new_order_customer_lists)) {
            $this->_return_json(array('status' => C('status.req.failed'), 'customer_info'=>$customer_info));
        }
        //获取用户的信息
        $customer_lists = $this->MCustomer->get_lists(
            array('id', 'shop_name', 'mobile', 'line_id', 'invite_id', 'lng', 'lat'), 
            array('in' => array('id' => array_keys($day_sum)), 'invite_id !=' => -1)
        );
        //获取对应用户的销售人员
        $salesman_lists = $this->MUser->get_lists(
            array('id', 'name'), 
            array('in' => array('id' => array_column($customer_lists, 'invite_id')))
        );
        //获取用户路线
        $line_lists = $this->MLine->get_lists(
            array('id', 'name'), 
            array('in' => array('id' => array_column($customer_lists, 'line_id')))
        );
        
        //整理数据结构
        $customer_unit_price = array_column($new_order_customer_lists, 'customer_unit_price', 'user_id');
        $amounts_lists       = array_column($new_order_customer_lists, 'amounts', 'user_id');
        $salesman_lists      = array_column($salesman_lists, 'name', 'id');
        $line_lists          = array_column($line_lists, 'name', 'id');
        
        foreach ($customer_lists as $key => $value) {
            $customer_info[$key]['customer_id']         = $value['id'];
            $customer_info[$key]['lng']                 = $value['lng'];
            $customer_info[$key]['lat']                 = $value['lat'];
            $customer_info[$key]['mobile']              = $value['mobile'];
            $customer_info[$key]['shop_name']           = $value['shop_name'];
            $customer_info[$key]['salesman']            = $salesman_lists[$value['invite_id']];
            $customer_info[$key]['line']                = $line_lists[$value['line_id']];
            $customer_info[$key]['customer_unit_price'] = $customer_unit_price[$value['id']];
            $customer_info[$key]['amounts']             = $amounts_lists[$value['id']] / 100;
        }
        $this->_return_json(array('status' => C('status.req.success'), 'customer_info'=>$customer_info));
    }
    
    /**
     *  获取一级品类下的所有品类
     *  @author wangzejun@dachuwang.com
     */
    private function get_all_cate_ids($sku_cate_ids) {
        if (empty($sku_cate_ids)) {
            return $sku_cate_ids;
        }
        $where = '`status` !=0 and ';
        foreach ($sku_cate_ids as $key => $value) {
            $where .= '`path` like "%.'.$value.'.%" or ';
        }
        $where = trim($where, ' or ');
        $cate_ids = $this->MCategory->get_lists(array('id', 'path'), $where);
        return array_column($cate_ids, 'id');
    }
    
    /**
     *  数组键值对转换
     *  @author:wangyang@dachuwang.com
     */
    private function _array_trans($array, $key) {
        $data = [];
        if(!empty($array)) {
            foreach($array as $v) {
                $data[$v[$key]] = $v;
            }
        }
        return $data;
    }

    private function _assemble_res($msg, $data) {
        $arr = array (
            'status' => C('status.req.success'),
            'msg' => $msg,
            'data' => $data
        );
        $this->_return_json($arr);
    }

    private function _assemble_err($msg) {
        $arr = array (
            'status' => C('status.req.failed'),
            'msg' => $msg 
        );
        $this->_return_json($arr);
    }

}//class Order_bi
