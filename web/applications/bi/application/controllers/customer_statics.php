<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 客户分析二级页面
 * @author : zhangxiao@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-04-25
 */
class Customer_statics extends MY_Controller {

    const TABLE_CUSTOMER        = 5; // 客户分析表
    const CUSTOMER_ALL          = 7; //所有客户
    const RECENT_THIRTYDAYS     = 30; // 默认选最近30天的下单金额
    const ONE_DAY_UNIXTIME      = 86400; //24小时时间戳

    public function __construct () {
        parent::__construct();
        $this->load->helper('date', 'url');
        $this->load->library('pagination');
        $this->load->helper('pagination');
    }

    /**
     * 获取get或post数据
     * @author:zhangxiao@dachuwang.com
     */
    private function _initial_data() {
        $site_id = $this->input->get('site_id');
        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->get('city_id');
        $this->data['city_id'] = $city_id ? $city_id : C('open_cities.beijing.id');

        $this->data['current_url'] = current_url(); $this->data['tab_id'] = self::CUSTOMER_ALL;
        $this->data['site_id'] = $this->_get_getdata_num('site_id', C('app_sites.chu.id'));
    }

    /**
     * 客户信息
     * @author:zhangxiao@dachuwang.com
     */
    public function show_cus_detail() {
        $this->_initial_data();
        $tab_id = $this->input->get('tab_id');
        $this->data['table_id'] = self::TABLE_CUSTOMER;
        $this->data['cus_id'] = $this->_get_getdata_num('cus_id', -1);
        $this->data['cus_info'] = $this->_get_one_cus_info();
        $this->data['load_js'] = ['bi_customer_detail.js'];
        $this->load->view('customer_detail', $this->data);

    }

    /**
     * 获取单个用户信息
     * @author:zhangxiao@dachuwang.com
     */
    private function _get_one_cus_info() {
        $cus_id = $this->data['cus_id'];
        $site_id = $this->data['site_id'];
        $city_id = $this->data['city_id'];
        $data_post_info = array(
            'site_id' => $site_id,
            'customer_ids' => $cus_id,
            'city_id'  => $city_id
        );

        $customer_info = array();

        //获取用户基本信息：用户id 用户名 商店名 电话 注册时间 录入时间 规模
        $customer_baseinfo = array();
        $customer_baseinfo = $this->_get_customer_analysis('get_customer_info', $data_post_info);
        $customer_baseinfo = isset($customer_baseinfo[$cus_id]) ? $customer_baseinfo[$cus_id] : array();
        $customer_record_time = $this->_get_customer_analysis('get_one_cus_record_time', array('cus_id' => $cus_id));

        if(!empty($customer_baseinfo)) {
            $customer_baseinfo['register_time'] = date('Y-m-d', $customer_baseinfo['created_time']);
            if(!empty($customer_record_time)) {
                $customer_baseinfo['record_time'] = date('Y-m-d', $customer_record_time['created_time']);
            } else {
                $customer_baseinfo['record_time'] = '';
            }
        }

        //获取用户订单信息： 订单数 已合并订单数 订单金额 客单价
        $customer_orderinfo = array();
        $customer_orderinfo = $this->_get_customer_analysis('get_order_info', $data_post_info);
        $customer_orderinfo = isset($customer_orderinfo[$cus_id]) ? $customer_orderinfo[$cus_id] : array();

        //获取下单频率
        $customer_orderate = array();
        $customer_orderate = $this->_get_customer_analysis('get_order_feaquency', $data_post_info);
        $customer_orderate = array_map(function($n){
            return number_format($n, 2);
        }, $customer_orderate);
        $customer_orderate = isset($customer_orderate[$cus_id]) ? $customer_orderate[$cus_id] : array();

        //组装数据
        $customer_info['base_info'] = $customer_baseinfo;
        $customer_info['order_info'] = $customer_orderinfo;
        $customer_info['order_info']['orderate'] = $customer_orderate;
        return $customer_info;
    }

    /**
     * 拼装页面所需格式的下单金额数据（单个用户下单金额+所有用户平均下单金额：某段时间某天）
     * @author:zhangxiao@dachuwang.com
     */
    public function get_cus_period_amount() {
        try {
            $mydata = array();

            $cus_id = $this->_get_getdata_num('cus_id', -1);
            $site_id = $this->_get_getdata_num('site_id', C('app_sites.chu.id'));

            //城市筛选:wangyang@dachuwang.com
            $city_id  = $this->input->get('city_id');
            $city_id  = $city_id ? $city_id : C('open_cities.beijing.id');

            $stime = $this->_convert_orderdate_to_unix()['stime'];
            $etime = $this->_convert_orderdate_to_unix()['etime'];
            if ($this->_get_getdata('time', false)) {
                $time = $this->_get_getdata('time', false);
                $stime = $this->_convert_orderdate_to_unix($time)['stime'];
                $etime = $this->_convert_orderdate_to_unix($time)['etime'];
            }
            
            $data_post_info = array(
                'stime' => $stime,
                'etime' => $etime,
                'cus_id' => $cus_id,
                'site_id' => $site_id,
                'city_id' => $city_id
            );

            $one_cus_period_amount = $this->_get_one_cus_period_amount($data_post_info);
            $all_cus_period_amount = $this->_get_all_cus_period_amount($data_post_info);

            $date = array();
            $diff = C('order_time.order_end_time.beijing.diff_timestamp');
            for($i = $stime+$diff; $i <= $etime; $i += self::ONE_DAY_UNIXTIME) {
                array_push($date, date('Y-m-d', $i));
            }
			
            $amount = array();
            $average = array();
            foreach ($date as $key => $value) {
                if(array_key_exists($value, $one_cus_period_amount)) {
                    $amount[$key] = $one_cus_period_amount[$value];
                } else {
                    $amount[$key] = 0;
                }

                if(array_key_exists($value, $all_cus_period_amount)) {
                    $average[$key] = $all_cus_period_amount[$value];
                } else {
                    $average[$key] = 0;
                }

            }

            $mydata = array(
                'date' => $date,
                'amount' => $amount,
                'average' => $average
            );

            return $this->_assemble_res('success', $mydata);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }


    /**
     * 获取单个用户某段时间内的下单金额（按天分）
     * @author:zhangxiao@dachuwang.com
     */
    private function _get_one_cus_period_amount($data_post_info) {
        $customer_period_amount = $this->_get_customer_analysis('get_one_cus_period_amount', $data_post_info);
        $one_cus_period_amount_by_date = array_column($customer_period_amount, 'total_price', 'date');
        foreach ($one_cus_period_amount_by_date as $key => $value) {
            if(number_format($value/100, 2, '.', '')-number_format($value/100, 0, '.', '') == 0) {
                $one_cus_period_amount_by_date[$key] = number_format($value/100, 0, '.', '');
            } else {
                $one_cus_period_amount_by_date[$key] = number_format($value/100, 2, '.', '');
            }
        }
        return $one_cus_period_amount_by_date;
    }

    /**
     * 查询时间段内所有客户(分大厨大果)的下单总金额(按天分组)
     * @author:zhangxiao@dachuwang.com
     */
    private function _get_all_cus_period_amount($data_post_info) {
        $all_cus_period_amount = $this->_get_customer_analysis('get_all_cus_period_amount', $data_post_info);

        $cus_num_by_date = array_column($all_cus_period_amount, 'cusnum', 'date');
        $cus_amount_by_date = array_column($all_cus_period_amount, 'total_price', 'date');
        $cus_average_amount = array();
        foreach ($cus_amount_by_date as $key => $value) {
             $average = number_format($value/($cus_num_by_date[$key]*100), 2);
             $cus_average_amount[$key] = $average;
        }

        return $cus_average_amount;
    }

    /**
     * 查询单个用户某段时间内的订单详情
     * @author:zhangxiao@dachuwang.com
     */
    public function get_one_cus_order_detail() {
        try {
            $cus_id = $this->_get_getdata_num('cus_id', -1);

            $stime = $this->_convert_orderdate_to_unix()['stime'];
            $etime = $this->_convert_orderdate_to_unix()['etime'];
            if ($this->_get_getdata('time', false)) {
                $time = $this->_get_getdata('time', false);
                $stime = $this->_convert_orderdate_to_unix($time)['stime'];
                $etime = $this->_convert_orderdate_to_unix($time)['etime'];
            }

            //获取单个用户某段时间内的所有订单ids
            $data_post_query_orderids = array(
                'stime' => $stime,
                'etime' => $etime,
                'cus_id' => $cus_id
            );
            $order_ids_src = $this->_get_customer_analysis('get_one_cus_period_orderids', $data_post_query_orderids);
            $order_ids_by_num = array_column($order_ids_src, 'order_number', 'id');
            $order_ids = array_column($order_ids_src, 'id');
            $order_ids_str = implode('-', $order_ids);

            //根据所有订单ids获取这些订单的details
            $data_post_query_details = array('order_ids' => $order_ids_str);
            $order_details = $this->_get_customer_analysis('get_order_details', $data_post_query_details);

            //添加订单号到用户下单详情，并计算总数量和总价格
            $total_quantity = 0;
            $total_sum_price = 0;
            foreach ($order_details as $key => $value) {
                $order_details[$key]['order_number'] = $order_ids_by_num[$order_details[$key]['order_id']];
                $order_details[$key]['price'] = number_format($value['price']/100, 2);
                $order_details[$key]['sum_price'] = number_format($value['sum_price']/100, 2);
                $total_quantity += $value['quantity'];
                $total_sum_price += $value['sum_price'];
            }

            //整理order_details数据以订单号为key
            $order_nums = array_column($order_ids_src, 'order_number');
            $order_details_by_order_number = array();
            foreach ($order_nums as $num) {
                $item_count = 0;
                foreach ($order_details as $value) {
                    if($value['order_number'] == $num) {
                        $order_details_by_order_number[$num][$item_count] = $value;
                        $item_count++;
                    }
                }
            }

            $one_cus_order_details['order_details'] = $order_details_by_order_number;
            $one_cus_order_details['total'] = array(
                'total_quantity' => $total_quantity,
                'total_sum_price' => number_format($total_sum_price/100, 2)
            );

            return $this->_assemble_res('succes', $one_cus_order_details);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }

    /**
     * 订单时间转换成时间戳
     * $date参数格式：一天（如：2015-04-01）一个月（如：2015-04）不传参数默认为最近30天
     * @author:zhangxiao@dachuwang.com
     */
    private function _convert_orderdate_to_unix($time = self::RECENT_THIRTYDAYS) {
        $period = array();
        $order_end_time = C('order_time.order_end_time.beijing.order_end_time');
        $diff_time = C('order_time.order_end_time.beijing.diff_timestamp');

        if(preg_match('/\d{4}-\d{2}-\d{2}/', $time)) {
            //一天内的时间戳
            $stime = strtotime($time)-$diff_time;
            $etime = strtotime($time.' '.$order_end_time)-1;
            $period = array(
                'stime' => $stime,
                'etime' => $etime
            );
        } elseif (preg_match('/\d{4}-\d{2}/', $time)) {
            //一个月内的时间戳
            $stime = strtotime($time)-$diff_time;
            $days = date('t', strtotime($time));
            $etime = strtotime($time.'-'.$days.' '.$order_end_time)-1;
            $period = array(
                'stime' => $stime,
                'etime' => $etime
            );
        }

        //默认最近30天内的时间戳
        if($time == self::RECENT_THIRTYDAYS) {
            $current_time = strtotime("now");
            $before_thirty_days = strtotime("-".($time-1)." days", strtotime(date('Y-m-d')))-$diff_time;
            $period = array(
                'stime' => $before_thirty_days,
                'etime' => $current_time
            );
        }
        return $period;
    }


    /**
     * 获取客户分析接口数据
     * @author:zhangxiao@dachuwang.com
     */
    private function _get_customer_analysis($api = '', $data_post = array()) {
        $data_api = $this->format_query('customer_analysis/'.$api, $data_post);
        if($data_api['status'] == C('status.req.success')) {
            $res = $data_api['res'];
        }
        return ! empty($res) ? $res : array();
    }

    /**
     * 通用得到get数据并验证其是数字
     * 参数$key:get的数据名称
     * 参数$default：=-1表示$key为必须得到的字段；其他值表示得到数据的默认值
     * @author zhangxiao@dachuwang.com
     */
    private function _get_getdata_num($key, $default) {
        $data = isset($_GET[$key]) ? $_GET[$key] : $default;
        if ($data == -1 || !is_numeric($data)) {
            return $this->_assemble_err("no get data or wrong get type");
        }
        return $data;
    }

    /**
     * 通用得到get数据
     * 参数$key:get的数据名称
     * 参数$default：=-1表示$key为必须得到的字段；其他值表示得到数据的默认值
     * @author zhangxiao@dachuwang.com
     */
    private function _get_getdata ($key, $default) {
        $data = isset($_GET[$key]) ? $_GET[$key] : $default;
        if ($data == -1) {
            return $this->_assemble_err("no get data");
        }
        return $data;
    }

    private function _assemble_res ($msg, $res) {
        $arr = array (
            'status' => C('status.req.success'),
            'msg' => $msg,
            'res' => $res
        );
        $this->_return_json($arr);
    }

    private function _assemble_err ($msg) {
        $arr = array (
            'status' => C('status.req.failed'),
            'msg' => $msg
        );
        $this->_return_json($arr);
    }

}
