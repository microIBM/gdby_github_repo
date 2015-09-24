<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 客户分析customer_analysis
 * 
 * @author wangyang@dachuwang.com
 */
class Customer_analysis extends MY_Controller {
    const SEVEN_DAYS_TIME_POINT = 7; // 7天
    const FOUR_DAYS_TIME_POINT = 4; // 4天
    
    const ORDER_COUNT_EQ_TWO = 2; // 2单
    const ORDER_COUNT_EQ_ONE = 1; // 1单
    
    const ORDER_FREQUENCY = 0.5; // 下单频率
    
    public function __construct () {
        parent::__construct();
        $this->load->model(array (
                'MCustomer',
                'MOrder',
                'MCustomer_potential',
                'MOrder_detail' 
        ));
    }
    
    /**
     * 下单频率
     * 
     * @author :wangyang@dachuwang.com
     */
    public function get_order_feaquency () {
        try {
            $customer_ids_str = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : ""; // 获得传来的customer_ids
            $customer_ids = explode('-', $customer_ids_str);
            
            $order_frequency = $this->_order_frequency($customer_ids);
            return $this->_assemble_res('success', $order_frequency);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    /**
     * 通过customer_ids 获取对应订单数、订单金额
     * $_POST['customer_ids'] = '1-20-22-45-67';
     * @author :wangyang@dachuwang.com
     */
    public function get_order_info () {
        try {
            $customer_ids_str = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : ""; // 获得传来的customer_ids
            $customer_ids = explode('-', $customer_ids_str);
            
            // 若客户ids为空，直接返回空；
            if (empty($customer_ids)) {
                return $this->_assemble_res('success', array ());
            }
            
            $where['in'] = array (
                    'user_id' => $customer_ids 
            );
            $where['status !='] = C('order.status.closed.code'); // 排除无效订单
            
            $group_by = [ 
                    'user_id' 
            ];
            
            $result = $this->MOrder->get_lists(array (
                    'user_id',
                    'sum(`total_price`) as order_amount',
                    'count(distinct(concat(`user_id`, "_", `deliver_date`, "_", `deliver_time`)))  as distinct_cnt',
                    'count(*) order_num',
                    '(sum(`total_price`)/count(*)) as average_price' 
            ), $where, array (), $group_by);
            $res = array();
            foreach ( $result as $value ) {
                $res[$value['user_id']] = $value;
            }
            return $this->_assemble_res('success', $res);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 通过customer_ids 获取客户信息详情
     * $_POST['customer_ids'] = '1-20-22-45-67';
     * 
     * @author :wangyang@dachuwang.com
     */
    public function get_customer_info () {
        try {
            $customer_ids_str = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : ""; // 获得传来的customer_ids
            $customer_ids = explode('-', $customer_ids_str);
            
            // 若客户ids为空，直接返回空；
            if (empty($customer_ids)) {
                return $this->_assemble_res('success', array ());
            }
            
            $where['in'] = array (
                    'id' => $customer_ids 
            );
            $where['status !='] = C('customer.status.invalid.code');
            $result = $this->MCustomer->get_lists('id, name, shop_name, mobile, created_time, dimensions', $where);
            $res = [ ];
            foreach ( $result as $value ) {
                $res[$value['id']] = $value;
            }
            return $this->_assemble_res('success', $res);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 留存客户ids
     * 7天前注册，7天内下单数>=2,且最近4天下单数>=1
     * @author :wangyang@dachuwang.com
     */
    public function get_customer_ids_remain () {
        try {
            $customer_ids_order_ge_two = $this->get_customer_ids_order_ge_two();
            $time = $this->_get_time_point(self::FOUR_DAYS_TIME_POINT); // 4天前时间戳；
                                                                        
            // 若客户ids为空，直接返回空；
            if (empty($customer_ids_order_ge_two)) {
                return $this->_assemble_res('success', array ());
            }
            $where['in'] = array (
                    'user_id' => $customer_ids_order_ge_two 
            );
            $where['created_time >'] = $time;
            $where['status !='] = C('order.status.closed.code'); // 排除无效订单
            $where['having'] = array (
                    'cnt >= ' => self::ORDER_COUNT_EQ_ONE 
            ); // 订单数大于等于1的客户ids
            
            $group_by = [ 
                    'user_id' 
            ];
            
            $result = $this->MOrder->get_lists(array (
                    'user_id',
                    'count(distinct(concat(`user_id`, "_", `deliver_date`, "_", `deliver_time`))) cnt' 
            ), $where, array (), $group_by);
            $customer_ids_remain = array_column($result, 'user_id');
            // ids按订单金额排序
            $customer_ids_remain = $this->_sort_by_ids($customer_ids_remain);
            return $this->_assemble_res('success', $customer_ids_remain);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 将流失客户ids
     * 7天前注册，7天下单数<=1，最近4天未过单
     * @author ：wangyang@dachuwang.com
     */
    public function get_customer_ids_going_lost () {
        try {
            $customer_ids_order_le_one = $this->get_customer_ids_order_le_one();
            $customer_ids_order_recent = $this->get_customer_ids_order_recent(); // 最近4天下过单的用户
            $customer_ids_going_lost = array_diff($customer_ids_order_le_one, $customer_ids_order_recent); // 排除最近4天下过单的用户
                                                                                                           // ids按订单金额排序
            $customer_ids_going_lost = $this->_sort_by_ids($customer_ids_going_lost);
            return $this->_assemble_res('success', $customer_ids_going_lost);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 7天前注册，7天下单数<=1，最近4天下过单
     * @author ：wangyang@dachuwang.com
     */
    public function get_customer_ids_order_recent () {
        $customer_ids_order_le_one = $this->get_customer_ids_order_le_one();
        $time = $this->_get_time_point(self::FOUR_DAYS_TIME_POINT); // 4天前时间戳；
                                                                    
        // 若客户ids为空，直接返回空；
        if (empty($customer_ids_order_le_one)) {
            return array ();
        }
        $where['in'] = array (
                'user_id' => $customer_ids_order_le_one 
        );
        $where['created_time >'] = $time;
        $where['status !='] = C('order.status.closed.code'); // 排除无效订单
        $where['having'] = array (
                'cnt >= ' => self::ORDER_COUNT_EQ_ONE 
        ); // 订单数大于等于1的客户ids
        
        $group_by = [ 
                'user_id' 
        ];
        
        $result = $this->MOrder->get_lists(array (
                'user_id',
                'count(distinct(concat(`user_id`, "_", `deliver_date`, "_", `deliver_time`))) cnt' 
        ), $where, array (), $group_by);
        $customer_ids_order_recent = array_column($result, 'user_id');
        return ! empty($customer_ids_order_recent) ? $customer_ids_order_recent : array ();
    }
    
    /**
     * 7天前注册，
     * 7天内下单数<=1的顾客ids
     * 
     * @author ：wangyang@dachuwang.com
     */
    public function get_customer_ids_order_le_one () {
        $customer_ids = array_column($this->_get_customer_ids_before_seven_days(), 'id'); // 获取7天前注册的用户ids
        $customer_ids_order_ge_two = $this->get_customer_ids_order_ge_two(); // 获取7天内下单数>=2的用户ids
        $customer_ids_order_le_one = array_diff($customer_ids, $customer_ids_order_ge_two);
        return $customer_ids_order_le_one;
    }
    
    /**
     * 7天前注册，
     * 7天内下单数>=2的顾客ids
     * @author ：wangyang@dachuwang.com
     */
    public function get_customer_ids_order_ge_two () {
        $customer_ids = array_column($this->_get_customer_ids_before_seven_days(), 'id'); // 获取7天前注册的用户ids
        $time = $this->_get_time_point(self::SEVEN_DAYS_TIME_POINT); // 7天前时间戳；
                                                                     
        // 若客户ids为空，直接返回空；
        if (empty($customer_ids)) {
            return array ();
        }
        $where['in'] = array (
                'user_id' => $customer_ids 
        );
        $where['created_time >'] = $time;
        $where['status !='] = C('order.status.closed.code'); // 排除无效订单
        $where['having'] = array (
                'cnt >= ' => self::ORDER_COUNT_EQ_TWO 
        ); // 订单数大于等于2的客户ids
        
        $group_by = [ 
                'user_id' 
        ];
        
        $result = $this->MOrder->get_lists(array (
                'user_id',
                'count(distinct(concat(`user_id`, "_", `deliver_date`, "_", `deliver_time`))) cnt'  // 订单去重
                ), $where, array (), $group_by);
        $customer_ids_order_ge_two = array_column($result, 'user_id');
        return ! empty($customer_ids_order_ge_two) ? $customer_ids_order_ge_two : array ();
    }
    
    /**
     * 忠实客户数
     * 7天前注册，下单频率>=0.5 （下单频率 ＝ 下单天数／注册到如今的天数）
     * 
     * @author : wangyang@dachuwang.com
     */
    public function get_customer_ids_loyal () {
        try {
            $res = $this->_order_frequency(array (), "before");
            $ids = [ ];
            foreach ( $res as $key => $value ) {
                if ($value > self::ORDER_FREQUENCY) {
                    array_push($ids, $key);
                }
            }
            
            // ids按订单金额排序
            $ids = $this->_sort_by_ids($ids);
            return $this->_assemble_res('success', $ids);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 获取所用customer_ids
     * @author :wangyang@dachuwang.com
     */
    public function get_all_customer_ids () {
        try {
            $customer_info = $this->_get_customer_info();
            $all_customer_ids = array_column($customer_info, 'id');
            
            // ids按订单金额排序
            $all_customer_ids = $this->_sort_by_ids($all_customer_ids);
            return $this->_assemble_res('success', $all_customer_ids);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 新客户数
     * 获取最近7天注册的客户ids
     * 
     * @author : wangyang@dachuwang.com
     */
    public function get_customer_ids_new () {
        try {
            $result = $this->_get_customer_info(array (), $flag = "after");
            $customer_ids = array_column($result, 'id');
            
            // ids按订单金额排序
            $customer_ids = $this->_sort_by_ids($customer_ids);
            return $this->_assemble_res('success', $customer_ids);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 有效客户数
     * 下过单的客户ids
     * 
     * @author : wangzejun@dachuwang.com
     */
    public function get_customer_ids_valid () {
        try {
            $customer_ids      = $this->_get_customer_ids_order();
            return $this->_assemble_res('success', $customer_ids);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 流失客户数
     * 下过单但最近30天内为下单的客户ids
     * 
     * @author : wangzejun@dachuwang.com
     */
    public function get_customer_ids_loss () {
        try {
            $loss_customer_ids  = array();
            $time               = strtotime("-30 days");
            $valid_customer_ids = $customer_ids = $this->_get_customer_ids_order();
            $customer_ids       = $this->_get_customer_ids_order($time);
            
            foreach ($valid_customer_ids as $key => $value) {
                if(!in_array($value, $customer_ids)) {
                    array_push($loss_customer_ids, $value);
                }
            }
            return $this->_assemble_res('success', $loss_customer_ids);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 有效客户数
     * 下过单的客户ids或者最近30天内下过单的客户
     * 
     * @author : wangzejun@dachuwang.com
     */
    private function _get_customer_ids_order ($time = 0) {
        $result = $this->_get_customer_info();
        $customer_ids = array_column($result, 'id');

        // 若客户ids为空，直接返回空；
        if (empty($customer_ids)) {
            return array ();
        }

        $where['in'] = array (
            'user_id' => $customer_ids 
        );
        $where['status !='] = C('order.status.closed.code'); // 排除无效订单
        if ($time) {
            $where['created_time >='] = $time;
        }
        $group_by = array('user_id');
        $customer_ids = $this->MOrder->get_lists(array (
            'user_id',
            'count(*) as cnt'
            ), $where, array ('cnt' => 'DESC'), $group_by);
        $customer_ids = array_column($customer_ids, 'user_id');
        return $customer_ids;
    }
    
    /**
     * customer_ids排序--按订单数
     * 
     * @author :wangyang@dachuwang.com
     */
    private function _sort_by_ids ($customer_ids = array()) {        
        $where['status !='] = C('order.status.closed.code'); // 排除无效订单
        $order_by = array (
                'cnt' => 'DESC' 
        );
        $group_by = array (
                'user_id' 
        );
        
        $result = $this->MOrder->get_lists(array (
                'user_id',
                'count(*) as cnt' 
        ), $where, $order_by, $group_by);
        
        $sort_all_ids = [ ];
        foreach ( $result as $value ) {
            if (in_array($value['user_id'], $customer_ids)) {
                array_push($sort_all_ids, $value['user_id']);
            }
        }
        // 未下过单的用户
        $no_in_customer_ids = array_diff($customer_ids, $sort_all_ids);
        // 合并
        $sort_all_ids = array_merge($sort_all_ids, $no_in_customer_ids);
        return $sort_all_ids;
    }
    
    /**
     * 下单频率统计
     * 
     * @param : $customer_ids            
     * @param $flag 判断在7天前、最近7天,
     *            值“before”、“after”、“”(忽略)
     * @author :wangyang@dachuwang.com
     */
    private function _order_frequency ($customer_ids, $flag = "") {
        $customer_info = $this->_get_customer_info($customer_ids, $flag);
        $customer_ids = array_column($customer_info, 'id');
        
        // 统计注册到如今的天数
        $customer_id_dates = [ ];
        foreach ( $customer_info as $value ) {
            $customer_id_dates[$value['id']] = ceil((time() - $value['created_time']) / 86400);
        }
        
        // 若客户ids为空，直接返回空；
        if (empty($customer_ids)) {
            return array ();
        }
        
        $where['in'] = array (
                'user_id' => $customer_ids 
        );
        $where['status !='] = C('order.status.closed.code'); // 排除无效订单
        
        $group_by = [ 
                'user_id' 
        ];
        
        // 统计 有下单的天数
        $customer_order_dates = $this->MOrder->get_lists(array (
                'user_id',
                'count(distinct(date_format(from_unixtime(created_time + 3600),"%Y-%m-%d"))) cnt'  // 计算出下单天数
                ), $where, array (), $group_by);
        $result = [ ];
        foreach ( $customer_order_dates as $value ) {
            $result[$value['user_id']] = $value['cnt'] / $customer_id_dates[$value['user_id']];
        }
        foreach ( $customer_info as $value ) {
            if (! array_key_exists($value['id'], $result)) {
                $result[$value['id']] = 0;
            }
        }
        return $result;
    }
    
    /**
     * 获取7天前注册的客户ids
     * 
     * @author : wangyang@dachuwang.com
     */
    private function _get_customer_ids_before_seven_days () {
        $result = $this->_get_customer_info(array (), $flag = "before");
        return ! empty($result) ? $result : array ();
    }
    
    /**
     * 查询用户相关信息
     * 
     * @param $flag 判断在7天前、最近7天,
     *            值“before”、“after”、“”(忽略)
     * @author :wangyang@dachuwang.com
     */
    private function _get_customer_info ($customer_ids = array(), $flag = "") {
        //城市筛选，默认北京
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id');
        // 搜索信息
        $search_key = isset($_POST['search_key']) ? $_POST['search_key'] : array ();
        $search_value = isset($_POST['search_value']) ? $_POST['search_value'] : array ();
        $time = $this->_get_time_point(self::SEVEN_DAYS_TIME_POINT); // 7天前时间戳；
        
        //wangzejun@dachuwang.com  增加客户类型的判断
        $customer_type = $this->input->post('customer_type', true);
        if ($customer_type) {
            $where['customer_type'] = $customer_type;
        }
        
        $where['province_id'] = $city_id;
        $where['status !=']   = 0;
        if (! empty($customer_ids)) {
            $where['in'] = array (
                    'id' => $customer_ids 
            );
        }
        // 获取是7天前或者后的用户信息
        if ($flag == "before") {
            $where['created_time <= '] = $time;
        } elseif ($flag == "after") {
            $where['created_time > '] = $time;
        }
        
        // search
        switch ($search_key) {
            case 'c_name' :
                $where['like'] = array (
                        'name' => $search_value 
                );
                break;
            case 'c_tel' :
                $where['like'] = array (
                        'mobile' => $search_value 
                );
                break;
            case 'c_shop' :
                $where['like'] = array (
                        'shop_name' => $search_value 
                );
                break;
            case 'c_id' :
                $where['id = '] = $search_value;
                break;
        }
        $result = $this->MCustomer->get_lists('id ,created_time', $where);
        return ! empty($result) ? $result : array ();
    }
    
    /**
     * 获取7天前(默认)、4天前时间戳，其中对应23:00为截止时间
     * 
     * @author :wangyang@dachuwang.com
     */
    private function _get_time_point ($days = self::SEVEN_DAYS_TIME_POINT) {
        $current_time = strtotime("-" . $days . "days");
        $return_date = date('Y-m-d', $current_time + 3600); // 计算按23:00为截止时间
        $return_time = strtotime($return_date) - 3600;
        return $return_time;
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
    
    /**
     * 通用得到post数据并验证其是数字
     * 参数$key:post的数据名称
     * 参数$default：=-1表示$key为必须得到的字段；其他值表示得到数据的默认值
     * 
     * @author zhangxiao@dachuwang.com
     */
    private function _get_post_num ($key, $default) {
        $data = isset($_POST[$key]) ? $_POST[$key] : $default;
        if ($data == - 1 || ! is_numeric($data)) {
            return $this->_assemble_err("no post data or wrong data type");
        }
        return $data;
    }
    
    /**
     * 通用得到post数据
     * 参数$key:post的数据名称
     * 参数$default：=-1表示$key为必须得到的字段；其他值表示得到数据的默认值
     * 
     * @author zhangxiao@dachuwang.com
     */
    private function _get_post ($key, $default) {
        $data = isset($_POST[$key]) ? $_POST[$key] : $default;
        if ($data == - 1) {
            return $this->_assemble_err("no post data");
        }
        return $data;
    }
    
    /**
     * 以用户id来获取单个用户的录入时间
     * 
     * @author zhangxiao@dachuwang.com
     */
    public function get_one_cus_record_time () {
        try {
            $cus_id = $this->_get_post_num('cus_id', - 1);
            $cus_mobile_res = $this->MCustomer->get_one(array (
                    'mobile' 
            ), array (
                    'id' => $cus_id 
            ));
            $cus_mobile = $cus_mobile_res['mobile'];
            $result = $this->MCustomer_potential->get_one_cus_recordtime_by_mobile($cus_mobile);
            if ($result) {
                return $this->_assemble_res('success', $result);
            } else {
                return $this->_assemble_err('cannot find this customer\'s record time by its mobile');
            }
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 以订单ids来获取所对应的商品信息：
     * 商品名称name、单价price、数量quantity、小计sum_price
     * 
     * @author zhangxiao@dachuwang.com
     */
    public function get_order_details () {
        try {
            $order_ids_str = $this->_get_post('order_ids', - 1);
            $order_ids_arr = explode('-', $order_ids_str);
            $fields = array (
                    'order_id',
                    'name',
                    'price',
                    'quantity',
                    'sum_price' 
            );
            $where['in'] = array (
                    'order_id' => $order_ids_arr 
            );
            $order_detail_info = $this->MOrder_detail->get_lists($fields, $where);
            return $this->_assemble_res('success', $order_detail_info);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 用单个用户id查询一个用户在某段时间内所有的有效订单id和订单号
     * 
     * @author zhangxiao@dachuwang.com
     */
    public function get_one_cus_period_orderids () {
        try {
            $cus_id = $this->_get_post_num('cus_id', - 1);
            $where['user_id'] = $cus_id;
            $where['status !='] = C('order.status.closed.code'); // 去除无效订单
            
            if ($this->_get_post('stime', false)) {
                $where['created_time >='] = $this->_get_post('stime', false);
            }
            
            if ($this->_get_post('etime', false)) {
                $where['created_time <'] = $this->_get_post('etime', false);
            }
            
            $fields = array (
                    'id',
                    'order_number' 
            );
            $cus_period_orderids = $this->MOrder->get_lists($fields, $where);
            return $this->_assemble_res('success', $cus_period_orderids);
        
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 用单个用户id查询一个用户在某段时间内的订单总额（按天分组）
     * 
     * @author zhangxiao@dachuwang.com
     */
    public function get_one_cus_period_amount () {
        try {
            $cus_id = $this->_get_post_num('cus_id', - 1);
            $where['user_id'] = $cus_id;
            $where['status !='] = C('order.status.closed.code'); // 去除无效订单
            
            if ($this->_get_post('stime', false)) {
                $where['created_time >='] = $this->_get_post('stime', false);
            }
            
            if ($this->_get_post('etime', false)) {
                $where['created_time <'] = $this->_get_post('etime', false);
            }
            
            $fields = array (
                    'sum(total_price) total_price',
                    'date_format(from_unixtime(created_time), "%Y-%m-%d") date'
            );
            $group_by = array (
                    'date' 
            );
            $cus_period_orderids = $this->MOrder->get_lists($fields, $where, array(), $group_by);

            return $this->_assemble_res('success', $cus_period_orderids);
        
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }
    
    /**
     * 查询时间段内所有客户的下单总金额(按天分组)
     * 
     * @author zhangxiao@dachuwang.com
     */
    public function get_all_cus_period_amount () {
        try {
            $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : C('open_cities.beijing.id'); //城市筛选，默认北京

            if ($this->_get_post('stime', false)) {
                $where['created_time >='] = $this->_get_post('stime', false);
            }
            
            if ($this->_get_post('etime', false)) {
                $where['created_time <'] = $this->_get_post('etime', false);
            }

            $where['city_id'] = $city_id;
            $where['status !='] = C('order.status.closed.code'); // 去除无效订单
            
            $fields = array (
                    'sum(total_price) total_price',
                    'count(distinct(user_id)) cusnum',
                    'date_format(from_unixtime(created_time), "%Y-%m-%d") date'
            );
            $group_by = array (
                    'date'
            );
            $all_cus_period_amount = $this->MOrder->get_lists($fields, $where, array (), $group_by);
            // $query = $this->db->last_query();
            return $this->_assemble_res('success', $all_cus_period_amount);
        } catch (Exception $e) {
            return $this->_assemble_err($e->getMessage());
        }
    }

}// class Order_td
