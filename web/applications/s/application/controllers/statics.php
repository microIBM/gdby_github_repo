<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * ription 数据统计service
 *
 * @author yuanxiaolin@dachuwang.com
 */
class Statics extends MY_Controller {
    
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
                'MCustomer',
                'MOrder',
                'MOrder_detail',
                'MUser',
                'MSku',
                'MCategory' 
        ));
    }
    
    /*
     * @description: 统计注册没下单用户 @author: wangyang@dachuwang.com
     */
    public function resigned_not_ordered ($site_id = 1) {
        try {
            /*
             * if($stime != 0 && $etime != 0) { $params['stime'] = $stime; $params['etime'] = $etime; }
             */
            // 获取每个站点的已下单用户uid
            $params['site_src'] = $site_id;
            $params['status !='] = C('order.status.closed.code');
            $result = $this->MOrder->get_lists('user_id', $params, array (), array (
                    'user_id' => 'user_id' 
            ));
            
            // 把uid写到一个数组中，已便再customer表中查询相关的客户信息；
            foreach ( $result as $value ) {
                $user_id_ordered[] = $value['user_id'];
            }
            $where['site_id'] = $site_id;
            $where['not_in'] = array (
                    'id' => $user_id_ordered 
            );
            $user_info_not_ordered = $this->MCustomer->get_lists('id, name, shop_name, mobile, address, created_time, invite_id, site_id', $where);
            
            // 通过uid到得invite_id，组合到数组中，查询相关DB信息
            $invite_id_info = array ();
            foreach ( $user_info_not_ordered as $value ) {
                if ($value['invite_id'] != '-1' && ! in_array($value['invite_id'], $invite_id_info)) {
                    $invite_id_info[] = $value['invite_id'];
                }
            }
            $where_in_user_table['in'] = array (
                    'id' => $invite_id_info 
            );
            $invite_info = $this->MUser->get_lists('id, name, mobile', $where_in_user_table);
            // 整理user表中的信息，使key为user的id；
            $invite_info_by_id_key = $this->sort_BD_info_by_id($invite_info);
            $user_info = array ();
            foreach ( $user_info_not_ordered as $value ) {
                if ($value['invite_id'] > 0 && isset($invite_info_by_id_key[$value['invite_id']])) {
                    $value = array_merge($value, $invite_info_by_id_key[$value['invite_id']]);
                }
                $user_info[] = $value;
            }
            $this->success($user_info);
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    /*
     * @description: 整合user表中的信息，key为BD的id 值为对应的信息 @author:wangyang@dachuwang.com
     */
    public function sort_BD_info_by_id ($data = array()) {
        if (empty($data)) {
            return FALSE;
        }
        foreach ( $data as $value ) {
            $return_data[$value['id']]['DB_name'] = $value['name'];
            $return_data[$value['id']]['DB_mobile'] = $value['mobile'];
        }
        return $return_data;
    
    }
    
    /**
     * 统计每天的订单及每天下单人数
     *
     * @param unknown $stime
     *            开始时间
     * @param unknown $etime
     *            结束时间
     * @param unknown $tag
     *            数据源 1默认大厨网，2大果网
     * @return array order_count每日下单总数，每日下单用户数customer_count @date : 2015-3-25 下午6:03:47
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function order_count ($tag = 0, $stime = 0, $etime = 0, $display_list = false) {
        
        try {
            //区分城市 wangyang@dachuwang.com
            $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
            if($city_id) {
                $where['city_id'] = $city_id;
            }
            if($tag) {
                $where['site_id'] = $tag;
            }
            $count = 0;
            // 未传入时间，默认取当天数据
            if (! $stime && ! $etime) {
                $stime = strtotime(date('Y-m-d', time()) . ' 00:00:00');
                $etime = time();
            }
            $where = array_merge(array (
                    'stime' => $stime,
                    'etime' => $etime 
            ), $where);
            $orders = $this->MOrder->get_period_orders($where, $display_list);
            $this->success($orders);
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    
    /**
     * 查询时间段内用户数据（每日新增用户和用户总数）
     *
     * @param number $site_id
     *            1：大厨网，2：大果网
     * @param number $stime
     *            时间戳起点
     * @param number $etime
     *            时间戳终点
     * @return array
     * @author yaunxiaolin@dachuwang.com
     */
    public function customer_total ($site_id = 0, $stime = 0, $etime = 0) {
        
        try {
            //区分城市 wangyang@dachuwang.com
            $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
            if($city_id) {
                $where['province_id'] = $city_id;
            }
            if($site_id){
                $where['site_id'] = $site_id;
            }
            if ($stime && $etime) {
                $where['stime'] = $stime;
                $where['etime'] = $etime;
            }
            $day_customers = $this->MCustomer->get_customer_lists($where, $display_list = false);
            $this->success($day_customers);
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    
    /**
     * 查询时间段内每日首次下单用户数
     *
     * @param number $site_id
     *            0大厨网 1大果网
     * @param number $stime
     *            起始时间
     * @param number $etime
     *            结束时间
     * @return json 成功或失败数据返回
     * @author yuanxiaolin@dachuwang.com
     */
    public function ordered_customers ($site_id = 0, $stime = 0, $etime = 0) {
        
        try {
            // 默认查询当天
            if ($stime == 0 && $etime == 0) {
                $date = date('Y-m-d', time());
                $stime = strtotime($date . ' 00:00:00');
                $etime = strtotime($date . ' 23:59:59');
            }
            $result = $this->MOrder->get_ordered_customers($site_id, $stime, $etime);
            $this->success($result);
        
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    
    }

    /**
     * 统计到这个时间段的总客户数
     * @author: wangyang@dachuwang.com
     */
    public function get_period_customers_total($site_id = 1, $stime = 0, $etime = 0) {
            //区分城市 wangyang@dachuwang.com
            $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');

            $params['site_id'] = $site_id;
            if ($stime != 0 && $etime != 0) {
                $params['stime'] = $stime;
                $params['etime'] = $etime;
                $customers = $this->MCustomer->count(array (
                        'site_id' => $site_id,
                        'created_time <=' => $etime,
                        'province_id' => $city_id  //区分城市 wangyang@dachuwang.com
                    ));
            }

        return isset($customers) ? $customers : 0;
    }
    /**
     * 获取历史数据
     * @author: wangyang@dachuwang.com
     */
    public function history_order_data ($site_id = 1,$stime = 0, $etime = 0){
        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');
        $where['city_id'] = $city_id;

        $where['site_src'] = $site_id;
        $where['status !='] = C('order.status.closed.code');
        if(!empty($stime) && !empty($etime)){
            $where['created_time >'] = $stime;
            $where['created_time <='] = $etime;
        }
        $history_orders = $this->MOrder->get_lists(
            'count(distinct user_id, deliver_date, deliver_time ) as cnt , user_id',
            $where,
            array(),
            array('user_id')
        );
        return !empty($history_orders) ? $history_orders :array();
    }

    /**
     * 一段时间首购，复购统计
     * @author: wangyang@dachuwang.com
     */
    public function trade_diversion_period($site_id = 1,$stime = 0, $etime = 0){
        $period_customers_total = $this->get_period_customers_total($site_id , $stime, $etime);
        $period_data = $this->history_order_data($site_id, $stime, $etime);
        $period_uids = array_column($period_data, 'user_id');
        
        $ordered_customers = count($period_data);
        $again_customers   = 0;
        $first_customers = 0;
        $in_time_orders = 0;

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
        // 历史或时间段内下单用户总数
        $data['period_ordered_customers_total'] = $ordered_customers;
        // 历史或时间段内重复下单用户数
        $data['period_again_customers_total'] = $again_customers;
        // 时间段内首次下单用户数
        $data['period_first_customer_total'] = $first_customers;
        // 时间段内订单数
        $data['period_in_customer_total'] = $in_time_orders;
        
        $data['period_customers_total'] = $period_customers_total;
        return  $this->success($data);

    }

    /**
     * 历史统计
     * @author: wangyang@dachuwang.com
     */
    public function trade_diversion_history($site_id =1){
        $history_data = $this->history_order_data($site_id);
        $ordered_customers = count($history_data);
        $again_customers   = 0;

        foreach($history_data as $value ) {
            if($value['cnt'] > 1 ){
                    $again_customers ++;
            }
        }
        // 历史下单用户总数
        $data['period_ordered_customers_total'] = $ordered_customers;
        // 历史复下单用户数
        $data['period_again_customers_total'] = $again_customers;
        $data['period_first_customer_total'] = $ordered_customers;
        return $this->success($data);
    }

    
    public function product_rank ($index = 1, $size = 20) {
        
        // $data = $this->MOrder_detail->get_order_detail_lists($index,$size);
    
    }
    
    /**
     * 接口成功返回函数封装
     *
     * @param array $data            
     * @return json @date : 2015-3-25 下午6:00:58
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function success ($data) {
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'msg' => 'success',
                'data' => $data 
        ));
    }
    
    /**
     * 接口失败返回函数封装
     *
     * @param array $data            
     * @return json @date : 2015-3-25 下午6:01:19
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function failed ($data) {
        $this->_return_json(array (
                'status' => C('status.req.failed'),
                'msg' => 'failed',
                'data' => $data 
        ));
    }
    /*
     * 获取一段时间统计数据 @author wangyang@dachuwang.com
     */
    public function get_period_statistics ($site_id = 1, $stime = 0, $etime = 0) {
        try {
            // 默认查询当天
            if ($stime == 0 && $etime == 0) {
                $date = date('Y-m-d', time());
                $stime = strtotime($date . ' 00:00:00');
                $etime = strtotime($date . ' 23:59:59');
            }
            $result = $this->MOrder->get_period_ordered_customers($site_id, $stime, $etime);
            $this->success($result);
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    
    /**
     * 获取历史总计统计数据
     *
     * @param int $who
     *            1大厨 2大果
     * @return boolean array
     * @author zhangxiao@dachuwang.com
     */
    public function get_total_history_statistics ($who) {
        
        if ($who != 1 && $who != 2) {
            return false;
        }
        
        $statistics = array (
                "total_order" => $this->MOrder->count_total_order($who),
                "order_close_rate" => $this->MOrder->get_order_close_rate($who),
                "order_signed_rate" => $this->MOrder->get_order_signed_rate($who),
                "total_transaction_amount" => $this->MOrder->get_total_transaction_amount($who),
                "new_users" => "--",
                "logined_users" => "--",
                "total_customer" => $this->MCustomer->count_total_customer($who) 
        );
        return $this->success($statistics);
    }
    
    /**
     * 接口：查询一段时间内只下过一个订单的用户及详细信息
     *
     * @param number $site_id            
     * @param number $stime            
     * @param number $etime            
     * @return return_type @date : 2015-3-27 下午3:17:53
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function one_order_customers ($site_id = 1, $stime = 0, $etime = 0) {
        
        $ordered_customers = $this->MOrder->count_customers_by_time($site_id, $stime, $etime);
        
        // 获取只下一次的用户
        $one_order_customers = array ();
        if (count($ordered_customers) > 0) {
            foreach ( $ordered_customers as $value ) {
                if ($value['order_count'] == 1) {
                    $value['order_time'] = date('Y-m-d H:i:s', $value['created_time']);
                    $one_order_customers[$value['user_id']] = $value;
                }
            }
        }
        // 查询用户详情
        $param = array ();
        if (! empty($one_order_customers)) {
            $param['in']['id'] = array_keys($one_order_customers);
        }
        
        $customer_infos = $this->MCustomer->get_lists('id as c_id,name,shop_name,username,mobile,address,invite_id,site_id,created_time,line_id', $param);
        
        // 提取BD user_id
        $invite_users = array ();
        if (count($customer_infos) > 0) {
            foreach ( $customer_infos as $value ) {
                if (! in_array($value['invite_id'], $invite_users)) {
                    array_push($invite_users, $value['invite_id']);
                }
            }
        }
        
        // 查询BD信息
        $param['in']['id'] = $invite_users;
        $invite_infos = $this->MUser->get_lists('id as bd_id,name as bd_name,mobile as bd_mobile', $param);
        $invite_bd = array ();
        if (count($invite_infos) > 0) {
            foreach ( $invite_infos as $value ) {
                $invite_bd[$value['bd_id']] = $value;
            }
        }
        
        $new_customers = array ();
        if (count($customer_infos) > 0) {
            foreach ( $customer_infos as $key => $value ) {
                if (! empty($invite_bd[$value['invite_id']])) {
                    $new_customers[$value['c_id']] = array_merge($value, $invite_bd[$value['invite_id']]);
                }
            }
        }
        
        if (count($one_order_customers) > 0) {
            foreach ( $one_order_customers as $key => $value ) {
                if (! empty($new_customers[$value['user_id']])) {
                    $one_order_customers[$key] = array_merge($value, $new_customers[$value['user_id']]);
                }
            }
        }
        
        $this->success($one_order_customers);
    }
    
    /**
     * 查询复购用户数据
     * 
     * @param unknown $site_id            
     * @param unknown $stime            
     * @param unknown $etime            
     * @author yuanxiaolin@dachuwang.com
     */
    public function again_order_customers ($site_id, $stime, $etime) {
        
        $extra['status !='] = C('order.status.closed.code'); // 无效订单
        
        if (! empty($stime)) {
            $extra['created_time >='] = $stime;
        }
        if (! empty($etime)) {
            $extra['created_time <='] = $etime;
        }
        if (! empty($site_id)) {
            $extra['site_src'] = $site_id;
        }
        $orders = $this->MOrder->get_created_orders($extra);
        // $agin_customers_infos = $this->get_again_orders_uids($orders, $extra);
        $agin_customers_infos = $this->MOrder->filer_users($orders);
        $again_cunstomers = $this->_get_cutomers_trans_data($agin_customers_infos, array (
                'stime' => $stime 
        ));
        
        // 复购用户及复购次数
        $again_customers_uids = array ();
        $customers = $again_cunstomers['again_customers'] ? $again_cunstomers['again_customers'] : array ();
        if (! empty($customers)) {
            foreach ( $customers as $key => $value ) {
                $again_customers_uids[$key]['again_count'] = count($value);
            }
        }
        // 按照复购数降序排序
        arsort($again_customers_uids);
        
        // 查询用户详情
        $param['in']['id'] = array_keys($again_customers_uids);
        $customer_infos = $this->MCustomer->get_lists('id as c_id,name,shop_name,username,mobile,address,invite_id,site_id,created_time,line_id', $param);
        
        $invite_users = array ();
        if (count($customer_infos) > 0) {
            foreach ( $customer_infos as $value ) {
                if (! in_array($value['invite_id'], $invite_users)) {
                    array_push($invite_users, $value['invite_id']);
                }
            }
        }
        
        $bd_user_infos = $this->_get_bd_infos_by_uids($invite_users);
        
        $new_customers = array ();
        if (count($customer_infos) > 0) {
            foreach ( $customer_infos as $key => $value ) {
                $new_customers[$key] = array_merge($value, $bd_user_infos[$value['invite_id']]);
                if (isset($again_customers_uids[$value['c_id']])) {
                    $count = $again_customers_uids[$value['c_id']];
                    $again_customers_uids[$value['c_id']] = array_merge($count, $new_customers[$key]);
                }
            }
        }
        
        $sort_customers = array ();
        if (count($again_customers_uids) > 0) {
            foreach ( $again_customers_uids as $key => $value ) {
                // $new_customers[$key] = array_merge($value,$bd_user_infos[$value['invite_id']]);
            }
        }
        
        $this->success($again_customers_uids);
    }
    
    public function bd_customers_performance () {
        try {
            $stime = $this->input->post('stime');
            $etime = $this->input->post('etime');

            $this->load->model('mbdmodel');
            $site_id = $this->mbdmodel->get_site_id();
            $bd_uids = $this->mbdmodel->get_bd_uids();
            $bd_customers = $this->bd_customers($site_id, $bd_uids, $api_type = false); // 获取BD名下的客户信息
            $customer_uids = $this->mbdmodel->get_customer_uids($bd_customers); // 获取该BD名下客户uid

            // 历史统计单独计算
            if (empty($stime) && empty($etime)) {
                $customers = $this->_get_history_customers_num($site_id, $bd_customers);
            } else {
                $orders = $this->MOrder->get_orders_by_uids($stime, $etime, $site_id, $customer_uids); // 批量查询客户订单
                $customers = $this->mbdmodel->get_custoers_num($stime, $etime, $orders, $bd_customers); // 获取时间段内BD对应顾客转化数据
            }

            $customer_data = $this->_collect_customer_data($bd_uids, $customers);

            $this->success($customer_data);

        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    
    /**
     * interface:查询BD名下的客户信息
     * 
     * @param number $stime
     *            开始时间戳
     * @param number $etime
     *            结束时间戳
     * @param unknown $bd_uids
     *            BD user_id信息
     * @param boolean $api_type
     *            接口返回方式：true(默认）接口json方式返回，false 数组方式返回
     */
    public function bd_customers ($site_id = 0, $bd_uids = array(), $api_type = true) {
        try {
            $where = array ();
            $fields = array ();
            $customers = array ();
            
            $where['status !='] = C('customer.status.invalid.code');
            if (empty($bd_uids)) {
                $this->load->model('mbdmodel');
                $bd_uids = $this->mbdmodel->get_bd_uids();
            }
            
            if (! empty($site_id)) {
                $where['site_id'] = $site_id;
            }
            
            if (is_array($bd_uids) && ! empty($bd_uids)) {
                $where['in']['invite_id'] = $bd_uids;
            }
            
            $result = $this->MCustomer->get_lists($fields, $where);
            
            if (! empty($result)) {
                foreach ( $result as $key => $value ) {
                    if (in_array($value['invite_id'], $bd_uids)) {
                        $customers[$value['invite_id']][$value['id']] = $value;
                    } else {
                        $customers[$value['invite_id']] = array ();
                    }
                }
            }
            if ($api_type === false) {
                return $customers;
            } else {
                $this->success($customers, $api_type);
            }
        } catch (Exception $e) {
            if ($api_type === false) {
                return array ();
            } else {
                $this->failed($e->getMessage(), $api_type);
            }
        }
    }
    
    /**
     * 计算复购用户数
     * 
     * @param unknown $customer_order_count
     *            客户ID几客户去重订单数据
     * @return number
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_again_customers ($customer_order_count) {
        
        $again_customers_count = 0;
        if (is_array($customer_order_count) && ! empty($customer_order_count)) {
            foreach ( $customer_order_count as $value ) {
                if ($value['cnt'] > 1) {
                    $again_customers_count ++;
                }
            }
        }
        return $again_customers_count;
    }
    
    private function _collect_customer_data ($bd_uid, $customers_data) {
        
        if (is_array($bd_uid) && ! empty($bd_uid)) {
            foreach ( $bd_uid as $key => $value ) {
                if (! isset($customers_data[$value])) {
                    $customers_data[$value]['bd_customers_total_history_count'] = 0; // 历史顾客数
                    $customers_data[$value]['bd_customers_total_count'] = 0; // 顾客数
                    $customers_data[$value]['bd_customers_order_count'] = 0; // 下单顾客数
                    $customers_data[$value]['bd_customers_first_count'] = 0; // 首购顾客数
                    $customers_data[$value]['bd_customers_again_count'] = 0; // 复购顾客数
                }
            }
        }
        return ! empty($customers_data) ? $customers_data : array ();
    }

    private function _get_bd_infos_by_uids ($uids = array()) {

        $invite_bd = array ();
        if (count($uids) > 0) {
            $param['in']['id'] = $uids;
            $invite_infos = $this->MUser->get_lists('id as bd_id,name as bd_name,mobile as bd_mobile', $param);
            if (count($invite_infos) > 0) {
                foreach ( $invite_infos as $value ) {
                    $invite_bd[$value['bd_id']] = $value;
                }
            }
        }

        return $invite_bd;
    }

    /**
     * 获取下单用户uids及每个用户各个配送时间
     *
     * @param unknown $data            
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function get_again_orders_uids ($data = array(), $params) {

        $uids = array ();
        if (is_array($data) && count($data) > 0) {

            foreach ( $data as $key => $value ) {
                // 过滤订单list重复uid
                if (! in_array($value['user_id'], array_keys($uids))) {
                    $uids[$value['user_id']][] = $value;
                } else {
                    $current = $uids[$value['user_id']];
                    $deliver_dates = array ();
                    $deliver_times = array ();
                    if (is_array($current) && ! empty($current)) {
                        foreach ( $current as $k => $v ) {
                            if (! in_array($v['deliver_date'], $deliver_dates)) {
                                $deliver_dates[] = $v['deliver_date'];
                            }
                            if (! in_array($v['deliver_time'], $deliver_times)) {
                                $deliver_times[] = $v['deliver_time'];
                            }
                        }
                    }
                    // 过滤相同uid 但配送日期不同
                    if (! in_array($value['deliver_date'], $deliver_dates)) {
                        array_push($uids[$value['user_id']], $value);
                    }                     // 过滤相同uid，相同配送日期，但配送时段不同
                    elseif (! in_array($value['deliver_date'], $deliver_times)) {
                        array_push($uids[$value['user_id']], $value);
                    }
                }
            }
        }
        return $uids;

    }

    /**
     * 统计历史复购用户数
     * 
     * @param unknown $site_id
     * @param unknown $bd_to_customers
     * @return Ambigous <multitype:, number>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_history_customers_num ($site_id, $bd_to_customers = array()) {
        
        $param['site_id'] = $site_id;
        $bd_performance_data = array ();
        if (is_array($bd_to_customers) && ! empty($bd_to_customers)) {
            
            $this->load->model('mbdmodel');
            foreach ( $bd_to_customers as $key => $value ) {
                $bd_customers = array_unique(array_keys($value));
                // $bd_customer_orders = $this->MOrder->get_orders_by_uids(0,0,$site_id,$bd_customers);
                $bd_customer_order_count = $this->MOrder->get_order_count_by_uids($bd_customers, $param); // 统计每个顾客的下单量
                $bd_distinct_order_customers = $this->MOrder->count_distinct_order_customers($site_id, $bd_customers); // 统计复购顾客数
                $bd_customer_again_count = $this->get_again_customers($bd_distinct_order_customers);
                $bd_performance_data[$key]['bd_customers_total_history_count'] = count($bd_customers); // 历史顾客数
                $bd_performance_data[$key]['bd_customers_total_count'] = count($bd_customers); // 顾客数
                $bd_performance_data[$key]['bd_customers_order_count'] = count($bd_customer_order_count); // 下单顾客数
                $bd_performance_data[$key]['bd_customers_first_count'] = count($bd_customer_order_count); // 首购顾客数
                $bd_performance_data[$key]['bd_customers_again_count'] = $bd_customer_again_count; // 复购顾客数
            }
        }
        return $bd_performance_data;
    }

    private function _get_cutomers_trans_data ($customer_orders = array(), $param = array()) {

        $uids = array ();
        $data = array ();
        $again_customers = array ();
        $first_customers = array ();
        if (is_array($customer_orders) && count($customer_orders) > 0) {

            $ordered_customers_uids = array_keys($customer_orders);

            // 查询时间段之前用户下单情况
            if (isset($param['stime']) && ! empty($param['stime'])) {
                $customer_orders_list = $this->MOrder->get_order_count_by_uids($ordered_customers_uids, $param);
                $ago_reorderd_count = array ();
                if (! empty($customer_orders_list)) {
                    foreach ( $before_day as $key => $value ) {
                        $ago_reorderd_count[$value['user_id']] = $value['order_count'];
                    }
                }
            }

            foreach ( $customer_orders as $key => $value ) {
                
                // 历史数据统计，下单次数大于1则复购用户数+1
                if (count($value) > 1 && ! isset($param['stime'])) {
                    $again_customers[$key] = $value;
                }
                // 历史数据统计，下单次数=1则首次下单用户数+1
                if (count($value) == 1 && ! isset($param['stime'])) {
                    $first_customers[$key] = $value;
                }

                // 当前时间段内用户订单大于1 且之前无下单记录，则是首次下单用户也是复购用户都+1，否则复购用户数+1
                if (count($value) > 1 && isset($param['stime'])) {
                    if (empty($ago_reorderd_count[$key])) {
                        $again_customers[$key] = $value;
                        $first_customers[$key] = $value;
                    } else {
                        $again_customers[$key] = $value;
                    }
                }

                // 当前时间段内用户订单等于1 且之前无下单记录，则首次用户数+1，否则复购用户数+1
                if (count($value) == 1 && isset($param['stime'])) {
                    if (empty($ago_reorderd_count[$key])) {
                        $first_customers[$key] = $value;
                    } else {
                        $again_customers[$key] = $value;
                    }
                }
            }
        }

        return array (
                'again_customers' => $again_customers,
                'first_customers' => $first_customers 
        );
    }

    /*
     * @description:把sku_num查询得到sku_info转换成sku_num 为key，sku_info 为value, 其中包括从category表中找到相关的分类信息
     * @author: wangyang@dachuwang.com
     */
    public function sku_num_to_sku_info ($data = array()) {
        if (empty($data)) {
            return FALSE;
        }
        foreach ( $data as $key => $value ) {
            $json_data = json_decode($value['spec']);
            $spec = '';
            foreach ( $json_data as $v ) {
                if ($v->val == '') {
                    $v->val = '无';
                }
                $spec .= $v->name . ':' . $v->val . '; ';
            }
            $return_data[$value['sku_number']]['name'] = $value['name'];
            $return_data[$value['sku_number']]['spec'] = $spec;
            
            // 根据 category_id 查询分类信息
            $category_info = "";
            $category_data = $this->MCategory->get_category_info($value['category_id']);
            if (! empty($category_data)) {
                $category_info = $category_data[0]['name'];
            }
            while ( $category_data[0]['upid'] > 0 ) {
                $category_data = $this->MCategory->get_category_info($category_data[0]['upid']);
                $category_info = $category_data[0]['name'] . '/' . $category_info;
            }
            $return_data[$value['sku_number']]['category'] = $category_info;
        }
        return $return_data;
    }

    /**
     * 得到某段时间内商品sku排行统计表数据
     * @author zhangxiao@dachuwang.com
     * @param number $stime            
     * @param number $etime            
     * @param number $offset            
     * @param number $pagesize            
     */
    public function get_period_sku_rank ($stime = 0, $etime = 0, $offset = 0, $pagesize = 0) {
        if (! empty($_POST['search_value']) && ! empty($_POST['search_key'])) {
            $where['like'] = array (
                    $_POST['search_key'] => $_POST['search_value'] 
                );
        }
        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');
        $where['city_id'] = $city_id;

        // 如果有提交搜索信息，进行筛选
        $params = isset($where) ? $where : array ();
        $searched_sku_info = $this->MSku->get_sku_info_by_sku_num(array (), $params);
        $search = array_column($searched_sku_info, 'sku_number');

        // 得到所需的sku数据
        $status_ordered = C('order.status.closed.code');
        $status_success_ordered = C('order.status.success.code');
        $sku_lists_ordered = $this->MOrder_detail->get_period_sku_rank_data($status_ordered, $stime, $etime, 0, -1, $search);
        $sku_lists_success_ordered = $this->MOrder_detail->get_period_sku_rank_data($status_success_ordered, $stime, $etime, 0, - 1, $search);

        if (empty($sku_lists_ordered)) {
            $rank['sku_number'] = array ();
            $rank['sku_info'] = array ();
            $rank['transaction_amount'] = array ();
            $rank['ordered_num'] = array ();
            $rank['sucess_ordered_num'] = array ();
            return $this->success($rank);
        }
        
        // 得到sku数据中所有sku_number
        $sku_num_arr = array ();
        foreach ( $sku_lists_ordered as $value ) {
            array_push($sku_num_arr, $value['sku_number']);
        }
        // 去重操作
        $sku_num_arr = array_unique($sku_num_arr);

        // 得到sku_number和sku信息关系
        $sku_info_by_sku_num = $this->sku_num_to_sku_info($this->MSku->get_sku_info_by_sku_num($sku_num_arr));

        // 组装统计表数据
        $rank['sku_number'] = array ();
        $rank['sku_info'] = array ();
        $rank['transaction_amount'] = array ();
        $rank['ordered_num'] = array ();
        $rank['sucess_ordered_num'] = array ();
        foreach ( $sku_lists_ordered as $value ) {
            $sku_number = $value['sku_number'];
            // 组装sku_number
            array_push($rank['sku_number'], $sku_number);
            // 组装sku_info
            if (array_key_exists($sku_number, $sku_info_by_sku_num)) {
                array_push($rank['sku_info'], $sku_info_by_sku_num[$sku_number]);
            } else {
                array_push($rank['sku_info'], array (
                        'name' => '数据库中暂无此名称信息',
                        'spec' => '数据库中暂未此规格信息' 
                ));
            }
            // 组装交易额
            array_push($rank['transaction_amount'], $this->get_item_by_sku_num($sku_lists_ordered, 'actual_sum_price', $sku_number));
            // 组装下单sku量
            array_push($rank['ordered_num'], $value['quantity']);
            // 组装成交sku量
            array_push($rank['sucess_ordered_num'], $this->get_item_by_sku_num($sku_lists_success_ordered, 'actual_quantity', $sku_number));
        }
        $this->success($rank);
    
    }

    private function get_item_by_sku_num ($sku_lists_success_ordered, $item, $sku_num) {
        foreach ( $sku_lists_success_ordered as $value ) {
            if ($value['sku_number'] == $sku_num) {
                return $value[$item];
            }
        }
        return 0;
    }

}

/* End of file order.php */
/* Location: ./application/controllers/order.php */
