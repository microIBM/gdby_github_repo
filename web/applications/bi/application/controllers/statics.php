<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 大厨网大果数据统计
 *
 * @author : yuanxiaolin@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-03-18
 */
class Statics extends MY_Controller {

    const TODAY                 = 4; // 今天,按自然日
    const HISTORY               = 5; // 历史总计
    const BY_MONTH              = 6; // 按月统计,按自然月

    const TODAY_BY_TWENTY_THREE         = 1; // 今天，按23:00为截止时间
    const YESTODAY_BY_TWENTY_THREE      = 2; // 昨日，按23:00为截止时间
    const SEVEN_DAYS_BY_TWENTY_THREE    = 3; // 最近7天，按23:00为截止时间

    const PAGE = 1; // 默认显示第一页
    const OFFSET = 10; // 默认每页10条纪录
    const OFFSET_15 = 15; // 默认每页10条纪录

    //客户分析tabs
    const CUSTOMER_ALL      = 1; //所有客户
    const CUSTOMER_LOSTING  = 2; //将流失客户
    const CUSTOMER_NEW      = 3; //新客户
    const CUSTOMER_SAVE     = 4; //留存客户
    const CUSTOMER_LOYAL    = 5; //忠实客户
    const CUSTOMER_VALID    = 6; //有效客户
    const CUSTOMER_LOSS     = 7; //流失客户
    const CUSTOMER_TYPE     = 0; //全部客户 
    
    const ONE_DAY_UNIXTIME  = 86400;
    
    const WHITE_TJ_MODULE = 2; //数据统计白名单模块ID
    const WHITE_XL_MODULE = 3; //货物销量白名单模块ID
    const WHITE_TD_MODULE = 4; //订单分时白名单模块ID
    const WHITE_KH_MODULE = 5; //客户分析白名单模块ID

    public $site_id;
    public $tab_id;

    public function __construct () {
        parent::__construct();

        $this->load->helper('date', 'url');
        $this->load->library('pagination');
        $this->load->helper('pagination');
        $this->load->model(array (
                'MOrder',
                'MCustomer' 
        ));
    }

    /**
     * 大厨网统计首页
     * @author yuanxiaolin@dachuwang.com
     */
    public function index () {
        $data = $this->data;
        $city_id       = $this->input->get('city_id');
        $date          = $this->input->get('date');
        $customer_type = $this->input->get('customer_type');

        //默认全国统计
        $data['city_id']       = $city_id ? $city_id : C('open_cities.quanguo.id');
        //默认全部客户统计
        $data['customer_type'] = ($customer_type === FALSE) ? 0 : $customer_type;
        $data['date']          = $date ? $date : date('Y-m', strtotime("now"));
        $data['current_url']   = current_url();

        //历史统计
        $data['time'] = $this->_get_static_date(TRUE);
        $data['history_data'] = $this->_get_history_statics($data);

        //阶段统计
        $data['time'] = $this->_get_static_date();
        $data['period_data'] = $this->_get_month_statics($data);

        //每日统计
        $data['days_list'] = $this->_get_days_statics($data);
        
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_TJ_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $this->load->view('index', $data);
        }
    }

    /**
     *  获取总的下单天数
     *  @author:wangyang@dachuwang.com
     */
    private function _get_order_total_days($init_data) {
        $data = $this->format_query('order_bi/get_order_days',
            array(
                'site_id' => $init_data['site_id'],
                'city_id' => $init_data['city_id'],
                'stime'   => $init_data['time']['stime'],
                'etime'   => $init_data['time']['etime'],
            )
        );
        return $data['data'][0]['total'];
    }

    /**
     * 获取一段时间总计统计数据
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_history_statics($init_data) {
//         $data = $this->format_query('order_bi/statics_period',
//             array(
//                 'customer_type' => $init_data['customer_type'],
//                 'city_id' => $init_data['city_id'],
//                 'stime'   => $init_data['time']['stime'],
//                 'etime'   => $init_data['time']['etime'],
//             )
//         );
//         return $data['data'];
        $data = $this->format_query('staticize/get_history_statics_data',
        array(
                'customer_type' => $init_data['customer_type'],
                'city_id' => $init_data['city_id'],
                'stime'   => $init_data['time']['stime'],
                'etime'   => $init_data['time']['etime'],
            )
        );
        return !empty($data['data'][0]) ? $data['data'][0] : array();
    }

    /**
     * 获取月总计统计数据
     *  @author zhangxiao@dachuwang.com
     */
    private function _get_month_statics($init_data) {
        $data = $this->format_query('staticize/get_month_statics_data',
            array(
                'customer_type' => $init_data['customer_type'],
                'city_id' => $init_data['city_id'],
                'stime'   => $init_data['time']['stime'],
                'etime'   => $init_data['time']['etime'] - 86400,
            )
        );
        return !empty($data['data'][0]) ? $data['data'][0] : array();
    }

    /**
     *  获取每天统计数据
     *  @author:zhangxiao@dachuwang.com
     */
    private function _get_days_statics($init_data) {
//         $data = $this->format_query('order_bi/statics_by_day',
//             array(
//                 'customer_type' => $init_data['customer_type'],
//                 'city_id' => $init_data['city_id'],
//                 'stime'   => $init_data['time']['stime'],
//                 'etime'   => $init_data['time']['etime']
//             )
//         );
//         return $data['data'];
        $data = $this->format_query('staticize/get_daylist_statics_data',
            array(
                'customer_type' => $init_data['customer_type'],
                'city_id' => $init_data['city_id'],
                'stime'   => $init_data['time']['stime'],
                'etime'   => $init_data['time']['etime'] - 86400,
            )
        );
        $result = array();
        if (!empty($data['data'])) {
            foreach ($data['data'] as $value) {
                $value['week'] = $this->_week_name($value['time_stamp']);
                $result[$value['date']] = $value;
            }
        }
        return $result;
    }

    /**
     * 判断是星期几
     */
    private function _week_name($time_stamp) {
        switch(date('N', $time_stamp)) {
            case 1: $week = "周一";break;
            case 2: $week = "周二";break;
            case 3: $week = "周三";break;
            case 4: $week = "周四";break;
            case 5: $week = "周五";break;
            case 6: $week = "周六";break;
            case 7: $week = "周日";break;
        }
        return $week;
    }
    
    /**
     * 生成连续日期
     * @author zhangxiao@dachuwang.com
     */
    private function _get_continous_date($stime, $etime) {
        $dates = array();
        $stime = strtotime(date('Y-m-d', $stime));
        for($i = $stime; $i < $etime; $i += self :: ONE_DAY_UNIXTIME) {
            array_push($dates, date('Y-m-d', $i));
        }
        return $dates;
    }

    /**
     *  起止时间转换
     *  @author:wangyang@dachuwang.com
     */
    private function _get_static_date($history = FALSE) {
        $date = $this->input->get('date');
        $month_count = date('t', strtotime($date));
        if (empty($date)) {
            $time['sdate'] = date('Y-m').'-01';
            $time['edate'] = date('Y-m').'-'.$month_count;
            
        } else {
            $time['sdate'] = $date.'-01';
            $time['edate'] = $date.'-'.$month_count;
        }
        $time['stime'] = strtotime($time['sdate']);
        $time['etime'] = strtotime($time['edate']) + self :: ONE_DAY_UNIXTIME;
        if(($history == TRUE)) {
            $time['stime'] = 0;
            $time['etime'] = 0;
            $time['sdate'] = '';
            $time['edate'] = '';
        }
        return $time;
    }

    /**
     * 统计只下过一次单的用户
     *
     * @return return_type @date : 2015-3-30 下午1:37:26
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function one_order () {
        $site_id = $this->input->get('site_id');
        $stime = $this->input->get('stime');
        $stime = strtotime($stime);
        $etime = time();
        $url = sprintf('statics/one_order_customers/%s/%s/%s', $site_id, $stime, $etime);
        $return_data = $this->format_query($url, array ());

        $data = $this->data;
        $data['one_orders'] = $return_data['data'];
        $this->load->view('one_order', $data);
    }

    /**
     * 统计复购用户数据
     *
     * @return return_type @date : 2015-3-30 下午1:37:59
     * @version : v1.0.0
     * @author : yuanxiaolin@dachuwang.com
     */
    public function again_order () {

        $site_id = $this->input->get('site_id');
        $stime = $this->input->get('stime');
        $stime = strtotime($stime);
        $etime = time();
        $url = sprintf('statics/again_order_customers/%s/%s/%s', $site_id, $stime, $etime);
        $return_data = $this->format_query($url, array ());
        $data = $this->data;
        $data['again_orders'] = $return_data['data'];

        $this->load->view('again_order', $data);
    }

    public function not_order () {
        $data = $this->data;
        $site_id = $this->input->get('site_id');
        $cus_data = $this->format_query('statics/resigned_not_ordered/' . $site_id);
        $cus_data['base_url'] = $data['base_url'];
        $this->load->view('not_order', $cus_data);
    }

    /**
     * 大厨大果网商品sku排名统计页
     * @author zhangxiao@dachuwang.com
     */
    public function sku_rank () {
        $data                 = $this->data;
        $city_id              = $this->input->get('city_id');
        $tab_id               = $this->input->get('tab_id');
        $is_tab_id            = $this->input->get('is_tab_id');
        $offest               = $this->input->get('offset');
        $page                 = $this->input->get('page');
        $search_key           = $this->input->get('searchKey');
        $search_value         = $this->input->get('searchValue');
        $sdate                = $this->input->get('sdate');
        $edate                = $this->input->get('edate');
        $data['menue_id']     = $this->input->get('menue_id');
        $data['city_id']      = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京
        $data['tab_id']       = $tab_id ? $tab_id : self::TODAY_BY_TWENTY_THREE; // 默认最近24小时
        $data['is_tab_id']    = $is_tab_id ? $is_tab_id : 'true';
        $data['page']         = $page ? $page : 1; // 默认显示第一页
        $data['offset']       = $offest ? $offest : 10; // 默认每页10条纪录
        $data['current_url']  = current_url();
        $data['search_Type']  = $search_key;
        $data['search_value'] = $search_value;

        if($is_tab_id === 'false') {
            $data['tab_id'] = 0;
            $data['is_tab_id'] = 'false';
        }
        //获取时间间隔 wangyang@dachuwang.com
        $time_period = $this->_get_sku_time_period ($data);
        $data['stime'] = $time_period['stime'];
        $data['etime'] = $time_period['etime'];
        $data['sdate'] = $time_period['sdate'];
        $data['edate'] = $time_period['edate'];

        $all_records = $this->_order_rank($data);
        $data['total_records'] = $this->get_sku_count($data);
        $data['rank'] = $all_records; // 按照分页取数据
        $data['pagination'] = $this->get_pagination_tags($data); // 创建分页
        
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_XL_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $this->load->view('rank', $data);
        }
    }

    private function _initial_data () {
        $data = $this->data;
        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->get('city_id');
        $data['city_id'] = $city_id ? $city_id : C('open_cities.beijing.id');

        $site_id = $this->input->get('site_id');
        $tab_id = $this->input->get('tab_id');
        $offest = $this->input->get('offset');
        $page = $this->input->get('page');
        $search_key = $this->input->get('searchKey');
        $search_value = $this->input->get('searchValue');
        $data['site_id'] = $site_id ? $site_id : self::DACHU_SITE; // 默认大厨网
        $data['tab_id'] = $tab_id ? $tab_id : self::ONE_DAY; // 默认最近24小时
        $data['page'] = $page ? $page : self::PAGE; // 默认显示第一页
        $data['offset'] = $offest ? $offest : self::OFFSET; // 默认每页10条纪录
        $data['current_url'] = current_url();
        $data['search_Type'] = $search_key ? $search_key : "";
        $data['search_value'] = $search_value ? $search_value : "";
        $data['total_records'] = 0;
        return $data;
    }

    /**
     * @@description: BD业绩统计
     *
     * @author : wangyang@dachuwang.com
     */
    public function bd_statics () {
        die();
        $data = $this->_initial_data();
        $data['tab_id'] = isset($_GET['tab_id']) ? $_GET['tab_id'] : self::TODAY; // 默认今天
        $data['month'] = isset($_GET['month']) ? $_GET['month'] : date('m', time()); // 默认本月
        $data['year'] = isset($_GET['year']) ? $_GET['year'] : date('Y', time()); // 默认今年
        $time_period = $this->_get_time_period($data); // 获取时间间隔
        $data['stime'] = $time_period['stime'];
        $data['etime'] = $time_period['etime'];

        $bd_info = $this->_get_bd_info($data);
        // 获取bd_ids信息

        $bd_ids = [ ];
        foreach ( $bd_info as $value ) {
            array_push($bd_ids, $value['id']);
        }

        if (! empty($bd_ids)) {
            // 获取bd_id对应的customer_ids
            $bd_customer_info = $this->_get_bd_customer_ids($bd_ids);
        } else {
            $bd_customer_info = array ();
        }

        $customer_ids = [ ];//BD_id为key，customer_ids 为value
        $customer_bd_id = [ ];
        $bd_customer_ids= []; //BD_ids 与customer_ids 对应的而为数组；
        foreach ( $bd_customer_info as $key => $value ) {
            foreach ( $value as $k => $v ) {
                array_push($customer_ids, $k);
                $customer_bd_id[$k] = $key;
                $bd_customer_ids[$key][] = $k;
            }
        }

        //历史统计重新计算
        if(empty($data['stime']) && empty($data['etime'])) {
            $order_statics = [];
            $order_statics_success = [];
            foreach ($bd_customer_ids as $key => $value) {
                $customer_ids_str = implode('-', $value);
                $bd_order_statics = $this->_get_history_order_statics($key, $customer_ids_str, $data['site_id']);
                $bd_order_statics_success = $this->_get_history_order_statics($key, $customer_ids_str, $data['site_id'],C('order.status.success.code'));
                $order_statics = ($order_statics + $bd_order_statics);
                $order_statics_success = ($order_statics_success + $bd_order_statics_success);
            }
        }else{
            // 通过customer_ids来统计order表
            $order_statics = $this->_get_order_statics($customer_ids, $customer_bd_id, $data);
            // 通过customer_ids来统计order表,得到成功订单统计
            $order_statics_success = $this->_get_order_statics($customer_ids, $customer_bd_id, $data, C('order.status.success.code'));
        }

        // 通过uids来得到顾客统计信息（顾客数、下单顾客数、首购顾客数、复购顾客数）
        $customer_statics = $this->_get_customer_statics($bd_ids, $data);

        // 获取bd总数
        $bd_num = $this->_get_bd_count($data);

        $data['bd_info'] = $bd_info;
        $data['customer_statics'] = $customer_statics;
        $data['order_statics'] = $order_statics;
        $data['order_statics_success'] = $order_statics_success;

        // 添加分页
        $pagination = pagination('/statics/bd_statics', array (
                'site_id' => $data['site_id'],
                'tab_id' => $data['tab_id'],
                'searchKey' => $data['search_Type'],
                'searchValue' => $data['search_value'],
                'month' => $data['month'], 
                'offset' => $data['offset'] 
        ), $bd_num, $data['offset'], 4);
        $data['pagination'] = $pagination;
        $this->load->view('bd', $data);

    }

    /**
     * 订单分时统计
     * @author :wangyang@dachuwang.com
     */
    public function order_td () {
        $data                   = $this->data;
        $city_id                = $this->input->get('city_id');
        $data['tab_id']         = $this->input->get('tab_id');
        $data['menue_id']       = $this->input->get('menue_id');
        $data['city_id']        = $city_id ? $city_id : C('open_cities.beijing.id');//默认北京
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_TD_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $data = $this->order_timedivision();
            $data['load_js'] = ['bi_order_td.js'];
            $this->load->view('order_td',$data);
        }
    }

    /**
     * 订单分时统计前端异步获取
     * @return json
     * @author :wangyang@dachuwang.com
     */
    public function order_td_ajax () {
        $data = $this->order_timedivision();
        $res = [];
        $res['category'] = [];
        $res['series'] = [];
        foreach($data['order_td'] as $value){
            array_push($res['category'], $value['sdate'].'--'.$value['edate']);
            array_push($res['series'], $value['all']);
        }
        return $this->_return_json($res);
    }

    /**
     * 订单分时统计数据获取
     * @author :wangyang@dachuwang.com
     */
    public function order_timedivision () {
        $data                = $this->data;
        $city_id             = $this->input->get('city_id');
        $data['city_id']     = $city_id ? $city_id : C('open_cities.beijing.id');
        $data['menue_id']    = $this->input->get('menue_id');
        $data['current_url'] = current_url();

        $time_period = $this->_get_order_td_time_period();

        $data['stime'] = $time_period['stime'];
        $data['etime'] = $time_period['etime'];
        $data['date']  = $time_period['date'];

        //得到指定日期的订单状态信息
        $order_status_lists = $this->_get_order_status_lists($data);

        $order_status_statics = $this->_order_status_statics_by_hours($order_status_lists, $data);
        $data['order_td'] = $order_status_statics;
        return $data;
    }

    /**
     * 按小时来统计各个状态订单数
     * 这里的各个状态都是对应创建时间的状态
     * @author:wangyang@dachuwang.com
     */
    private function _order_status_statics_by_hours($order_lists = array(), $data_init = array()) {

        if(isset($data_init['stime']) && !empty($data_init['stime']) && isset($data_init['etime']) && !empty($data_init['etime']) ) {
            //分小时统计
            for($i = $data_init['stime'], $j = $data_init['stime'] + 60 * 60, $k = 0 ; $j <= $data_init['etime'] ; $i = $j, $j = $i + 60 * 60, $k++) {

                //初始化每个小时不同状态的订单数
                $res[$k]['all']             = 0; // 有效订单数；

                $res[$k]['stime']           = $i;//这个小时的开始时间戳
                $res[$k]['etime']           = $j;//这个小时的结束时间戳

                $res[$k]['sdate'] = date('H:i', $i); // 对应的开始date time
                $res[$k]['edate'] = date('H:i', $j); // 对应的结束date time

            }

            //统计每个小时区间内，各种状态的订单数
            if(isset($order_lists) && !empty($order_lists)) {
                foreach ($order_lists as $value) {

                    //24个小时循环
                    foreach ($res as $k => $v ) {

                        //判断是否在这个小时段内；
                        if($value['created_time'] > $v['stime'] && $value['created_time'] < $v['etime'] && $value['status'] != C('order.status.closed.code') ) {

                            $res[$k]['all']++;  // 全部订单数；
                        }//if 判断是否在这个小时段内；
                    } //foreach 24个小时循环
                }//foreach订单循环

            } //if order存在
        }   //if 时间存在；
        return isset($res) ? $res : array();

    } //class _order_status_statics_by_hours;

    /**
     * 订单分时系统 通过时间及站点来获取订单详情
     * @author:wangyang@dachuwang.com
     */
    private function _get_order_status_lists($data_init) {
        $order_status_lists = $this->format_query(
            'order_td/get_order_status' . '/' . $data_init['stime'] . '/'.$data_init['etime'] . '/' . $data_init['city_id']//增加城市筛选
        );
        $res = $order_status_lists['res'];
        return ! empty($res) ? $res : array();
    }

    /**
     * 订单分时系统获取时间
     */
    private function _get_order_td_time_period () {
        $current_date = date('Y-m-d', time());
        $date = isset($_GET['date']) ? $_GET['date'] : $current_date;
        $date = preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/',$date) ? $date : $current_date;

        // 计算时间是前一天的23点起到今天的23点截止
        $res['stime'] = strtotime($date) - 60 * 60;
        $res['etime'] = strtotime($date) + 23 * 60 * 60;
        $res['date']  = $date;

        return $res;
    }

    /**
     * @description: 获取历史统计的订单数
     * @author : wangyang@dachuwang.com
     */
    private function _get_history_order_statics ($bd_id = 0,$customer_ids = array(), $site_id = 1, $order_status = 0) {
        $history_order_statics = $this->format_query('bdstatics/history_order_statics_by_customer_ids', array (
                'order_status' => $order_status,
                'customer_ids' => $customer_ids,
                'bd_id' => $bd_id,
                'site_id' => $site_id
        ));
        $res = $history_order_statics['res'];
        return ! empty($res) ? $res : array();
    }

    /**
     * @description: 获取总的bd数
     * @author : wangyang@dachuwang.com
     */
    private function _get_bd_count ($data_init = array()) {
        $bd_count = $this->format_query('bdstatics/bd_count', array (
                'search_value' => $data_init['search_value'],
                'search_key' => $data_init['search_Type'] 
        ));
        $res = $bd_count['res'];
        return ! empty($res) ? $res : 0;
    }
    
    /**
     * @description :获取BD信息
     * 
     * @author :wangyang@dachuwang.com
     */
    private function _get_bd_info ($data_init) {
        $bd_info = $this->format_query('bdstatics/get_bd_bdm_info', array (
                'search_value' => $data_init['search_value'],
                'search_key' => $data_init['search_Type'],
                'currentPage' => $data_init['page'],
                'itemsPerPage' => $data_init['offset'] 
        ));
        $res = $bd_info['res'];
        return ! empty($res) ? $res : array ();
    }

    /**
     * @description :获取BD对应的customer_ids
     *
     * @author :wangyang@dachuwang.com
     */
    private function _get_bd_customer_ids ($bd_id_data = array()) {
        $bd_ids = implode('-', $bd_id_data);
        $site_id = isset($_GET['site_id']) ? $_GET['site_id'] : self::DACHU_SITE;

        $bd_customer_ids = $this->format_query('statics/bd_customers/', array (
                'bd_uids' => $bd_ids
        ));
        $res = $bd_customer_ids['data'];
        return ! empty($res) ? $res : array ();
    }

    /**
     * @description :通过传入的customer_ids来统计订单
     * 
     * @author :wangyang@dachuwang.com
     */
    private function _get_order_statics ($customer_ids = array(), $customer_bd_ids = array(), $data_init = array(), $status = 0) {
        if (empty($customer_ids)) {
            return array ();
        }
        // 调用接口，给定的customer_ids获取订单详情
        $order_statics = $this->format_query('bdstatics/order_info/' . $data_init['site_id'] . '/' . $data_init['stime'] . '/' . $data_init['etime'], array (
                'bd_customer_ids' => $customer_ids 
        ));
        // 得到订单信息
        $order_info = $order_statics['res'];
        // 根据订单信息进行统计,按bdID为key
        $bd = array ();
        foreach ( $order_info as $value ) {
            if ($status == C('order.status.closed.code')) {
                if ($value['status'] != C('order.status.closed.code')) {
                    $bd[$customer_bd_ids[$value['user_id']]][] = $value;
                }
            } else if ($status == C('order.status.success.code')) {
                if ($value['status'] == C('order.status.success.code')) {
                    $bd[$customer_bd_ids[$value['user_id']]][] = $value;
                }
            }
        }
        $bd_statics = [ ];
        foreach ( $bd as $key => $value ) {
            $bd_statics[$key]['order_num'] = count($value);
            $bd_statics[$key]['order_amout'] = 0;
            $bd_statics[$key]['order_num_distinct'] = 0;
            $order_distinct[$key]['user_id'] = [ ];
            $order_distinct[$key]['deliver_date'] = [ ];
            $order_distinct[$key]['deliver_time'] = [ ];
            foreach ( $value as $v ) {
                $bd_statics[$key]['order_amout'] += $v['total_price'];
                // 取出 user_id、deliver_date、deliver_time信息，以便后面进行去重
                array_push($order_distinct[$key]['user_id'], $v['user_id']);
                array_push($order_distinct[$key]['deliver_date'], $v['deliver_date']);
                array_push($order_distinct[$key]['deliver_time'], $v['deliver_time']);
            }

            // 统计同一个用户、只有一个配送时间的订单,去重
            $array_length = count($order_distinct[$key]['user_id']);
            for ($i = 0; $i < $array_length; $i ++) {
                for ($j = $i + 1; $j < $array_length; $j ++) {
                    if ($order_distinct[$key]['user_id'][$i] == $order_distinct[$key]['user_id'][$j] && $order_distinct[$key]['deliver_date'][$i] == $order_distinct[$key]['deliver_date'][$j] && $order_distinct[$key]['deliver_time'][$i] == $order_distinct[$key]['deliver_time'][$j]) {
                        unset($order_distinct[$key]['user_id'][$i]);
                        unset($order_distinct[$key]['deliver_date'][$i]);
                        unset($order_distinct[$key]['deliver_time'][$i]);
                        continue (2);
                    }
                }
            }
            $bd_statics[$key]['order_num_distinct'] = count($order_distinct[$key]['user_id']);
        }
        return ! empty($bd_statics) ? $bd_statics : array ();
    }
    
    /**
     * @description: 根据uid来获取用户统计信息
     * 
     * @author : wangyang@dachuwang.com
     */
    private function _get_customer_statics ($bd_ids = array(), $data_init = array()) {
        $bd_uids = implode('-', $bd_ids);
        $customer_statics = $this->format_query('statics/bd_customers_performance', array (
                'bd_uids' => $bd_uids,
                'site_id' => $data_init['site_id'],
                'stime' => $data_init['stime'],
                'etime' => $data_init['etime'] 
        ));
        $customer_statics_data = $customer_statics['data'];
        return $customer_statics_data;
    }

    /**
     * @description: 获取时间参数
     *
     * @author : wangyang@dachuwang.com
     */
    private function _get_time_period ($data_init = array()) {
        $res = [ ];
        // 默认今天
        $res['stime'] = strtotime(date('Y-m-d', time()));
        $res['etime'] = time();

        // tab_id 区分时间
        if ($data_init['tab_id'] == self::TODAY) {
            $res['stime'] = strtotime(date('Y-m-d', time()));
            $res['etime'] = time();
        } else if ($data_init['tab_id'] == self::HISTORY) {
            $res['stime'] = 0;
            $res['etime'] = 0;
        } else if ($data_init['tab_id'] == self::BY_MONTH) {
            $month = $data_init['month']; // 选择月份
            $year = $data_init['year'];
            $res['stime'] = strtotime($year . '-' . $month . '-1');
            $res['etime'] = strtotime($year . '-' . ($month + 1) . '-1') - 1;
        }

        return $res;
    }

    /**
     * @description:sku二期时间选择
     * @author wangyang@dachuwang.com
     */
    private function _get_sku_time_period ($data = array()) {
        //判断当前日期在系统时间中的日期
        //以23点为截止时间，超过23点算第二天
        $current_time = time();
        $current_date = strtotime(date('Y-m-d', $current_time));
        $current_date_time_point = $current_date + 23 * 60 * 60;

        if($current_time < $current_date_time_point) {
            $date_in_system = date('Y-m-d', $current_time);     // 系统中的日期：以23点为截止时间
        }else {
            $date_in_system = date('Y-m-d', $current_time + 60 * 60 + 1);
        }
        $date_in_system_time = strtotime($date_in_system);

        //默认今天
        $res['stime'] = $date_in_system_time - 60 * 60;
        $res['etime'] = $date_in_system_time + 23 * 60 * 60;

        //根据不同的tab选择传入的计算时间参数
        if ($data['tab_id'] == self::TODAY_BY_TWENTY_THREE) {
            $res['stime'] = $date_in_system_time - 60 * 60;
            $res['etime'] = $date_in_system_time + 23 * 60 * 60;
        } elseif ($data['tab_id'] == self::YESTODAY_BY_TWENTY_THREE) {
            $res['stime'] = $date_in_system_time - 24 * 60 *60 - 60 * 60;
            $res['etime'] = $date_in_system_time - 60 *60;
        } elseif ($data['tab_id'] == self::SEVEN_DAYS_BY_TWENTY_THREE){
            $res['stime'] = $date_in_system_time - 6 * 24 * 60 *60 - 60 * 60;
            $res['etime'] = $date_in_system_time + 23 * 60 * 60;
        }

        // 获取GET过来的日期
        // 若只有一个开始或者只有一个截止日期，则显示那一天的数据
        $start_date = $this->input->get('sdate');
        $end_date   = $this->input->get('edate');

        if(isset($start_date) && !empty($start_date) && isset($end_date) && !empty($end_date)) {
            if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$start_date) &&
                preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$end_date)) {
                $res['stime'] = strtotime($start_date) - 60 * 60;
                $res['etime'] = strtotime($end_date)   + 23* 60 * 60;
            }
        }else if(isset($start_date) && !empty($start_date) && empty($end_date)) {
            if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$start_date)) {
                $res['stime'] = strtotime($start_date)   - 60 * 60;
                $res['etime'] = strtotime($start_date)   + 23* 60 * 60;
            }
        }else if(isset($end_date) && !empty($end_date) && empty($start_date)) {
            if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$end_date)) {
                $res['stime'] = strtotime($end_date)   - 60 * 60;
                $res['etime'] = strtotime($end_date)   + 23* 60 * 60;
            }
        }

        $res['sdate'] = date('Y-m-d',$res['stime']+ 60*60);
        $res['edate'] = date('Y-m-d',$res['etime']);
        return $res;
    }

    /**
     * 得到sku的总个数
     * @author zhangxiao@dachuwang.com
     * @param array $info 
     * @return number
     */
    private function get_sku_count ($info) {
        $search_key   = isset($_GET['searchKey']) ? $_GET['searchKey'] : "";
        $search_value = isset($_GET['searchValue']) ? $_GET['searchValue'] : "";
        $stime        = $info['stime'];
        $etime        = $info['etime'];
        $offset       = $info['offset'];
        $pagesize     = ($info['page'] - 1) * $offset; // 从哪条数据开始取数据
        $data = $this->format_query(
            'statics/get_period_sku_rank/' . $stime . '/' . $etime,
            array (
                'search_value' => $search_value,
                'search_key' => $search_key,
                'city_id'    => $info['city_id'],
                'timeout'    => 1000
        ));
        $rank = $data['data'];
        return count($rank['sku_number']);
    }

    /**
     * 大厨大果网商品sku排名统计
     * @author zhangxiao@dachuwang.com
     */
    private function _order_rank ($info) {
        $search_key   = isset($_GET['searchKey']) ? $_GET['searchKey'] : "";
        $search_value = isset($_GET['searchValue']) ? $_GET['searchValue'] : "";
        $stime        = $info['stime'];
        $etime        = $info['etime'];
        $offset       = $info['offset'];
        $pagesize     = ($info['page'] - 1) * $offset;

        $data = $this->format_query(
            'statics/get_period_sku_rank/' . $stime . '/' . $etime,
            array (
                'search_value' => $search_value,
                'search_key'   => $search_key,
                'city_id'      => $info['city_id'],
                'timeout'      => 1000
        ));

        $rank = $data['data'];
        // 处理分页数据
        $rank_pagination = array ();
        $sku_number = array ();
        $sku_info = array ();
        $sku_transaction_amount = array ();
        $ordered_num = array ();
        $sucess_ordered_num = array ();

        $count = count($rank['sku_number']);
        if ($count > $pagesize + $offset) {
            $last_num = $pagesize + $offset;
        } else {
            $last_num = $count;
        }

        for ($i = $pagesize; $i < $last_num; $i ++) {
            array_push($sku_number, $rank['sku_number'][$i]);
            array_push($sku_info, $rank['sku_info'][$i]);
            array_push($sku_transaction_amount, $rank['transaction_amount'][$i]/100);
            array_push($ordered_num, $rank['ordered_num'][$i]);
            array_push($sucess_ordered_num, $rank['sucess_ordered_num'][$i]);
        }

        $rank_pagination['sku_number'] = $sku_number;
        $rank_pagination['sku_info'] = $sku_info;
        $rank_pagination['transaction_amount'] = $sku_transaction_amount;
        $rank_pagination['ordered_num'] = $ordered_num;
        $rank_pagination['sucess_ordered_num'] = $sucess_ordered_num;

        return $rank_pagination;
    }
    
    /**
     * 合并每天数据
     *
     * @return array 返回合并后的数据
     * @author yuanxiaolin@dachuwang.com
     */
    private function merge_days_data () {
        //城市筛选:wangyang@dachuwang.com //完毕
        $city_id  = $this->input->get('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        $trade_data = $this->_get_api_result('statics/order_count', array('city_id' => $city_id)); // 获取每日订单总数
        $customer_data = $this->_get_api_result('statics/customer_total', array('city_id' => $city_id)); // 获取每日新增用户及总用户
        $trans_data = $this->_get_api_result('statics/ordered_customers', array('city_id' => $city_id)); // 获取首次下单用户及复购用户
        foreach ( $trade_data as $key => $value ) {
            $trade_data[$key] = array_merge($trade_data[$key], $customer_data[$key], $trans_data[$key]);
        }
        return $trade_data;
    }
    
    /**
     *
     * @param int $site_id
     *            站点Id，1：大厨网，2：大果网
     * @return array
     * @author yuanxiaolin@dachuwang.com
     *         历史总计
     */
    private function _get_history_data ($site_id) {
        //城市筛选:wangyang@dachuwang.com 
        $city_id  = $this->input->get('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        $history_data = $this->_get_api_result('statics/get_total_history_statistics', array('city_id' => $city_id));
        $history_data_statics = $this->format_query('statics/trade_diversion_history/' . $site_id, array('city_id' => $city_id));
        $history_data = array_merge($history_data, $history_data_statics['data']);
        
        // 首次下单用户数据
        if (isset($history_data['period_ordered_customers_total']) && $history_data['period_ordered_customers_total'] != 0) {
            $history_data['first_ordered_customer_rate'] = $history_data['period_first_customer_total'] / $history_data['period_ordered_customers_total'] * 100;
        } else {
            $history_data['first_ordered_customer_rate'] = 0;
        }
        
        // 下单用户数数据
        if (isset($history_data['total_customer']) && $history_data['total_customer'] != 0) {
            $history_data['ordered_customers_rate'] = $history_data['period_ordered_customers_total'] / $history_data['total_customer'] * 100;
        } else {
            $history_data['ordered_customers_rate'] = 0;
        }
        
        // 重复下单用户数据
        if (isset($history_data['period_ordered_customers_total']) && $history_data['period_ordered_customers_total'] != 0) {
            $history_data['order_again_custmoer_rate'] = $history_data['period_again_customers_total'] / $history_data['period_ordered_customers_total'] * 100;
        } else {
            $history_data['order_again_custmoer_rate'] = 0;
        }
        
        return $history_data;
    }
    
    /**
     * 数据汇总求和
     *
     * @author wangyang@dachuwang.com
     */
    private function _get_period_data (array $data) {
        if (isset($data['days_list']) && ! empty($data['days_list'])) {
            $order_day_count_sum = 0;
            $ordered_trade_total_sum = 0;
            $customer_new_count_sum = 0;
            $customer_day_count_sum = 0;
            $order_success_count_sum = 0;
            $order_singed_count_sum = 0;
            foreach ( $data['days_list'] as $key => $value ) {
                $order_day_count_sum = $order_day_count_sum + $value['order_day_count'];
                $ordered_trade_total_sum = $ordered_trade_total_sum + $value['ordered_trade_total'];
                $customer_new_count_sum = $customer_new_count_sum + $value['customer_new_count'];
                $customer_day_count_sum = $customer_day_count_sum + $value['customer_day_count'];
                $order_success_count_sum = $order_success_count_sum + $value['order_success_count'];
                $order_singed_count_sum = $order_singed_count_sum + $value['order_singed_count'];
            }
            $data_sum['order_day_count_sum'] = $order_day_count_sum;
            $data_sum['ordered_trade_total_sum'] = $ordered_trade_total_sum;
            $data_sum['customer_new_count_sum'] = $customer_new_count_sum;
            $data_sum['customer_day_count_sum'] = $customer_day_count_sum;
            $data_sum['order_singed_count_sum'] = $order_singed_count_sum;
            $data_sum['order_success_count_sum'] = $order_success_count_sum;
            $data_sum['period_data'] = $this->_get_api_result('statics/trade_diversion_period', array('city_id' => $data['city_id']));
            return $data_sum;
        } else {
            return (FALSE);
        }
    }
    
    /**
     * 调取接口获取数据统一封装
     *
     * @param string $url            
     * @param unknown $param            
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_api_result ($url = '', $param = array()) {
        
        $short_url = $this->_get_api_uri($url);
        $return_data = $this->format_query($short_url, $param);
        if ($return_data['status'] == C('status.req.success')) {
            $data = isset($return_data['data']) ? $return_data['data'] : array ();
        }
        return ! empty($data) ? $data : array ();
    }
    
    /**
     * 获取接口短标记封装
     *
     * @param string $tag            
     * @return string
     * @author yuanxiaolin@dachuwang.com
     */
    private function _get_api_uri ($tag = '') {
        
        if ($tag == '')
            return '';
        $params = $this->get_time_period();
        $params_str = implode('/', array_values($params));

        return sprintf('%s/%s', $tag, $params_str);
    }

    /**
     * 获取时间段起始标记时间戳及site_id
     *
     * @return array
     * @author yuanxiaolin@dachuwang.com
     */
    private function get_time_period () {

        $date['site_id'] = $this->site_id;
        if ($this->tab_id == self::SEVEN_DAYS_TAB_ID) {
            $sdate = date('Y-m-d', strtotime('-6 days'));
            $date['stime'] = strtotime($sdate . ' 00:00:00');
            $date['etime'] = time();
        }
        if ($this->tab_id == self::THIRTY_DAYS_TAB_ID) {
            $sdate = date('Y-m-d', strtotime('-29 days'));
            $date['stime'] = strtotime($sdate . ' 00:00:00');
            $date['etime'] = time();
        }

        return $date;
    }

    /**
     * 创建SKU分页链接
     *
     * @author zhangxiao@dachuwang.com
     * @param unknown $total_rows
     * @param unknown $per_page
     * @return unknown
     */
    private function get_pagination_tags ($data) {
        $config['base_url'] = $this->data['base_url'] . '/statics/sku_rank?menue_id='.$data['menue_id'].'&tab_id=' . $data['tab_id'] . '&offset=' . $data['offset'] . '&searchKey=' . $data['search_Type'] . '&searchValue=' . $data['search_value'] . '&sdate=' . $data['sdate'] . '&edate=' . $data['edate'] . '&city_id=' . $data['city_id'];
        $config['total_rows'] = $data['total_records'];
        $config['per_page'] = $data['offset'];
        $config['num_links'] = 4;
        $config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['first_link'] = "首页";
        $config['last_link'] = "末页";
        $config['full_tag_open'] = '<ul class="pagination">';
        $config['full_tag_close'] = '</ul>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a>';
        $config['cur_tag_close'] = '</li></a>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $this->pagination->initialize($config);
        $links = $this->pagination->create_links();
        return $links;
    }

    /**
     * 客户分析数据整合
     * @author zhangxiao@dachuwang.com
     */
    public function customer_info() {
        $data = $this->_initial_data();
        $tab_id = $this->input->get('tab_id');
        $data['tab_id'] = $tab_id ? $tab_id : self::CUSTOMER_ALL;
        $data['load_js'] = ['bi_customer_detail.js'];
        $data['customer_type'] = $this->input->get('customer_type') ?: self::CUSTOMER_TYPE;
        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->get('city_id');
        $data['city_id'] = $city_id ? $city_id : C('open_cities.beijing.id');
        $data['menue_id'] = $this->input->get('menue_id');
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_KH_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $ids = array();
            $data_post = array(
                'search_key'    => $data['search_Type'],
                'search_value'  => $data['search_value'],
                'city_id'       => $data['city_id'],
                'customer_type' => $data['customer_type']
            );
            switch ($data['tab_id']) {
                case self::CUSTOMER_ALL :
                    $ids = $this->_get_customer_analysis('get_all_customer_ids', $data_post);
                    break;
                case self::CUSTOMER_LOSTING :
                    $ids = $this->_get_customer_analysis('get_customer_ids_going_lost', $data_post);
                    break;
                case self::CUSTOMER_NEW :
                    $ids = $this->_get_customer_analysis('get_customer_ids_new', $data_post);
                    break;
                case self::CUSTOMER_SAVE :
                    $ids = $this->_get_customer_analysis('get_customer_ids_remain', $data_post);
                    break;
                case self::CUSTOMER_LOYAL:
                    $ids = $this->_get_customer_analysis('get_customer_ids_loyal', $data_post);
                    break;
                case self::CUSTOMER_VALID :
                    $data_post['customer_type'] = $data['customer_type'];
                    $ids = $this->_get_customer_analysis('get_customer_ids_valid', $data_post);
                    break;
                case self::CUSTOMER_LOSS :
                    $data_post['customer_type'] = $data['customer_type'];
                    $ids = $this->_get_customer_analysis('get_customer_ids_loss', $data_post);
                    break;
                default:
                    break;
            }

            //获取总记录数
            $total_records_num = count($ids);

            //分页取客户id
            $pagesize = ($data['page']-1)*$data['offset'];
            $ids_perpage = array_slice($ids, $pagesize, $data['offset']);
            $data['ids'] = $ids_perpage;
            $ids_string = implode('-', $ids_perpage);

            //根据客户id取出相关客户以及其订单信息
            $customer_baseinfo = array();
            $data_post_info = array(
                'site_id' => $data['site_id'],
                'customer_ids' => $ids_string,
                'city_id' => $data['city_id']
            );
            $customer_baseinfo = $this->_get_customer_analysis('get_customer_info', $data_post_info);
            $data['customer_baseinfo'] = $customer_baseinfo;

            //根据客户id取出客户订单信息
            $customer_orderinfo = array();
            $customer_orderinfo = $this->_get_customer_analysis('get_order_info', $data_post_info);
            $data['customer_orderinfo'] = $customer_orderinfo;

            //根据客户id取出下单频率
            $customer_orderate = array();
            $customer_orderate = $this->_get_customer_analysis('get_order_feaquency', $data_post_info);
            $customer_orderate = array_map(function($n){
                return number_format($n, 2);
            }, $customer_orderate);
            $data['customer_orderate'] = $customer_orderate;

            // 添加分页
            $pagination = pagination('/statics/customer_info', array (
                'site_id' => $data['site_id'],
                'tab_id' => $data['tab_id'],
                'searchKey' => $data['search_Type'],
                'searchValue' => $data['search_value'],
                'offset' => $data['offset'],
                'city_id' => $data['city_id'],
                'customer_type' => $data['customer_type'],
                'menue_id' => $data['menue_id']
            ), $total_records_num, $data['offset'], 4);
            $data['pagination'] = $pagination;
            //加载需要的js文件

            $this->load->view('customer_info', $data);
        }
    }

    /**
     * 获取客户分析接口数据
     * @author:zhangxiao@dachuwang.com
     */
    private function _get_customer_analysis($api = '', $data_post = array()) {
        $data_api = $this->format_query('customer_analysis/'.$api, $data_post);
        $res = $data_api['res'];
        return ! empty($res) ? $res : array();
    }

    /**
     *  获取最近7天统计数据 for mobile 端
     *  @author: zhangxiao@dachuwang.com
     */
    public function get_seven_days_statics() {
        $customer_type = isset($_POST['customer_type']) ? $_POST['customer_type'] : 0;
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $stime = strtotime(date('Y-m-d 00:00:00',strtotime("-6 days")));
        $etime = $this->input->server('REQUEST_TIME');
        $arr_date = $this->_create_sequence_day($stime, $etime);

        $data = $this->format_query('order_bi/statics_by_day',
            array(
                'customer_type' => $customer_type,
                'city_id'       => $city_id,
                'stime'         => $stime,
                'etime'         => $etime
            )
        );

        $empty = [
            "valid_order_cnt" => 0,
            "valid_order_amount" => 0,
            "date" => '',
            "time_stamp" => '',
            "week" => '',
            "potential_cus_cnt" => 0,
            "resign_cus_cnt" => 0,
            "order_cus_cnt" => 0,
            "first_ordered_count" => 0,
            "first_amount" => 0,
            "again_ordered_count" => 0,
            "again_amount" => 0
        ];
        foreach ($arr_date as $value) {
            if(!array_key_exists($value, $data['data'])) {
                $data['data'][$value] = $empty;
            }
        }
        krsort($data['data']);
        return $this->_return_json($data);
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

}
