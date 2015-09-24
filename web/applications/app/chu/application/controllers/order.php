<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 订单操作
 *
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-06
 */
class Order extends MY_Controller {

    private $_user_info;
    public static $filter_words = array(
        'address','name','mobile'
    );
    public function __construct() {
        parent::__construct();
        $this->load->model(array(
            'MProduct',
            'MCategory',
            'MOrder',
            'MWorkflow_log',
            'MDeliver_fee',
            'MLine',
            'MStock'
        ));
        $this->load->library(array(
            'form_validation',
            'redisclient',
            'check_storage'
        ));
        $this->_wait_status_arr = array(
            C('order.status.confirmed.code'),
            C('order.status.wave_executed.code'),
            C('order.status.picking.code'),
            C('order.status.picked.code'),
            C('order.status.checked.code'),
            C('order.status.allocated.code'),
            C('order.status.delivering.code'),
            C('order.status.loading.code')
        );
        $this->_user_info = $this->userauth->current(TRUE);
        $this->load->helper(array('device_detect'));
    }

    /**
     * 订单列表
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function lists() {
        // 权限校验
        $this->check_validation('order', 'list');
        // 获取当前登录客户
        $cur = $this->_user_info_with_ip();
        $_POST['user_id'] = $cur['id'];
        // 待收货状态特俗处理，包括三种状态
        if (isset($_POST['status']) && $_POST['status'] == '100') {
            $_POST['status'] = $this->_wait_status_arr;
        }
        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        // 设置待收货状态的数量
        $return['total']['100'] = 0;
        foreach ($this->_wait_status_arr as $item) {
            if(!empty($return['total'][$item])) {
                $return['total']['100'] += $return['total'][$item];
            }
        }

        if(!empty($return['orderlist'])) {
            $required_fields = parent::$_app_required_fields['order']['lists'];
            parent::_get_required_fields($return, 'orderlist', $required_fields['orderlist']);
        }
        // 支付连接
        $return['pay_url'] = C('urls.weixin.pay_url');
        $this->_return_json($return);
    }

    /**
     * 查看订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function view() {
        // 调用基础服务接口
        $return = $this->format_query('/order/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * 获取用户ip信息
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-12
     */
    private function _user_info_with_ip() {
        $cur = $this->userauth->current(TRUE);
        if (empty($cur)) {
            return $cur;
        }
        $ip = $this->input->ip_address();
        $cur['ip'] = $ip;
        return $cur;
    }

    /**
     * @description 根据当前时间取能够配送的时间
     *
     * @author caochunhui@dachuawng.com
     */
    public function deliver_dropdown() {
        $_POST['site_id'] = C('site.dachu');
        // 调用基础服务接口
        $dropdown_return = $this->format_query('/order/deliver_dropdown', $_POST);
        $ordercnt_return = 0;
        $rules_return = array();
        $res = $dropdown_return;
        $res['dropdown'] = $dropdown_return;
        $res['ordercnt'] = $ordercnt_return;
        $res['promo_rules'] = $rules_return;
        $this->_return_json($res);
    }

    /**
     * 订单确认页逻辑展示
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-12
     */
    public function confirm_options() {
        $res = array(
            'status' => C("tips.code.op_failed"),
            'msg' => ''
        );

        $_POST['site_id'] = C('site.dachu');

        // 默认返回数据结构
        $cur = $this->userauth->current(TRUE);

        $products = !empty($_POST['cartlist']) ? $_POST['cartlist'] : array();
        if (empty($products)) {
            $res['msg'] = '您的购物车没有商品，请重新操作。';
            $this->_return_json($res);
        }
        if (empty($cur)) {
            $res['msg'] = '您未登录，请登录后再下单。';
            $res['status'] = C('status.auth.login_timeout');
            $this->_return_json($res);
        }

        // 检查是否可以当日下午就配送
        $is_daily_deliver = $this->_check_daily_deliver($products, $cur['province_id']);
        $_POST['daily_deliver'] = $is_daily_deliver;
        $_POST['province_id'] = $cur['province_id'];
        // 检查是否需要提示上午下单下午配送的文案
        $request_time = $this->input->server("REQUEST_TIME");
        $hour = date('H', $request_time);
        $res['deliver_notice'] = "";
        if (!$is_daily_deliver && ($hour == 23 || $hour < 11)) {
            $res['deliver_notice'] = "亲，蔬菜类/冻品/肉类暂时不支持当天配送，您可以将蔬菜/冻品/肉类单独生成其他时段的订单哦！";
        }

        // 获取配送时间列表
        $dropdown_return = $this->format_query('/order/deliver_dropdown', $_POST);
        $res['date_dropdown'] = $dropdown_return['list'];
        $res['time_dropdown'] = $dropdown_return['list'][0]['time'];

        // 获取用户基本信息
        $uinfo_return = $this->format_query('/customer/baseinfo', array(
            'user_id' => $cur['id']
        ));

        $res['user_info'] = $uinfo_return['info'];
        $this->_filter_user_info_fields($res['user_info']);
        $site_id = C('app_sites.chu.id');
        $city_id = $cur['province_id'];

        // 获取合适的规则
        $res['promotion_list'] = $this->_get_valid_promotions($cur, $products);

        // 获取活动优惠总价
        $res['minus_amount'] = $this->_cal_minus_amount($res['promotion_list']);

        // 计算购物车总价
        $res['total_price'] = $this->_cal_total_price($products);

        // 计算购物车ka 服务费
        //$res['service_fee'] = $this->_calc_service_fee($uinfo_return['info']['customer_type'], $res['total_price']);

        $minus_info = array();
        $valid = $this->_check_valid_trick_promo($products);
        if (is_bool($valid) && !$valid) {
            $minus_info = $this->_check_by_trick_rules($res['total_price'], FALSE);
        }
        $res['minus_info'] = $minus_info;
        // 获取运费信息
        $deliver_fee_rule = $this->MDeliver_fee->get_one('*', array(
            'city_id' => $city_id,
            'site_id' => $site_id
        ));
        $res['fee'] = 0;
        $res['free_amount'] = 0;
        if (!empty($deliver_fee_rule) && $res['total_price'] < $deliver_fee_rule['free_amount'] / 100) {
            $res['fee'] = $deliver_fee_rule['fee'] / 100;
            $res['free_amount'] = $deliver_fee_rule['free_amount'] / 100;
        }
        if ($minus_info) {
            $res['minus_info']['require_amount'] /= 100;
            $res['minus_info']['minus_amount'] /= 100;
            if ($res['total_price'] >= $res['minus_info']['require_amount']) {
                $res['minus_amount'] += $res['minus_info']['minus_amount'];
            }
        }
        $res['status'] = C("tips.code.op_success");
        // 计算总价
        $this->_calc_sum_price($res);
        $res['pay_url'] = C('urls.weixin.pay_url');
        $res['coupons'] = $this->_get_valid_coupon($products);
        // 支付推广活动
        //$res['pay_events'] = $this->_pay_event_options($uinfo_return);
        $res['pay_events'] = [];
        if(empty($res['pay_events_title'])) {
            $res['pay_events_title'] = '';
        }
        $this->_return_json($res);
   }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 过滤不需要的字段
     */
    private function _filter_user_info_fields(&$info) {
        foreach($info as $key => &$v) {
            if(!in_array($key, self::$filter_words)) {
                unset($info[$key]);
            }
        }
    }

    private function _calc_sum_price(&$res) {
         // 设置应付总额
        $res['sum'] = sprintf('%.2f', $res['total_price'] + $res['fee'] - $res['minus_amount']);
    }

    /**
     * 检查是否可以当日下午就配送
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-18
     */
    private function _check_daily_deliver(&$products, $city_id) {
        if (empty($products)) {
            return FALSE;
        }
        if(!is_array($products)) {
            $products = json_decode(trim($products, '"'), TRUE);
        }

        if(!is_array($products)) {
            $this->_return_json(array('status' => C('tips.code.op_failed'), 'msg' => 'json parser error!'));
        }

        // 如果当前城市不允许当日达
        if (!in_array($city_id, C("daily_deliver_city.cities"))) {
            return FALSE;
        }

        $category_ids = array_column($products, "category_id");
        $banned_category_type = array(
            C("category.category_type.frozen.code"),
            C("category.category_type.vegetable.code"),
            C("category.category_type.meat.code"),
            C("category.category_type.fruit.code")
        );
        $path = $this->MCategory->get_lists("id, path", array(
            "in" => array(
                'id' => $category_ids
            ),
            "status" => C("status.common.success")
        ));
        foreach ($path as $item) {
            foreach ($banned_category_type as $type) {
                if (strpos($item['path'], ".{$type}.") !== FALSE) {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * 获取活动优惠总额
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-14
     */
    private function _cal_minus_amount($rules) {
        if (empty($rules)) {
            return 0;
        }
        $minus_amount = 0;
        foreach ($rules as $item) {
            $rule = json_decode($item['rule_desc'], TRUE);
            $minus_amount += $rule['return_profit'] / 100;
        }
        return $minus_amount;
    }

    /**
     * 获取商品总价
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-14
     */
    private function _cal_total_price($products) {
        if (empty($products)) {
            return 0;
        }
        $total = 0;
        foreach ($products as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * 计算可参与的活动信息
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-14
     */
    private function _get_valid_promotions($cur, $products, $deliver_date = 0) {
        $cur = $this->userauth->current(TRUE);
        // 是否已下过订单
        $history_order_count = $this->format_query('/order/user_history_order_count', array('user_id' => $cur['id']));
        if($history_order_count['status'] == 0) {
            $history_order_count = $history_order_count['info'];
        } else {
            $history_order_count = array(
                'today_count' => 0,
                'all_count'   => 0
            );
        }
        // 参加过哪些活动
        $history_promo_list = $this->format_query('/order/user_history_promo_list', array('user_id' => $cur['id']));
        if($history_promo_list['status'] == 0) {
            $history_promo_list = $history_promo_list['info'];
        } else {
            $history_promo_list = array(
                'today_list' => array(),
                'all_list'   => array()
            );
        }
        // 获取此购物车可参与的活动
        $filter = array(
            'site_id'           => C("site.dachu"),
            'cartlist'          => $products,
            'location_id'       => $cur['province_id'], // 取用户所在地的活动
            'all_order_count'   => $history_order_count['all_count'],
            'today_order_count' => $history_order_count['today_count'],
            'all_promo_list'    => $history_promo_list['all_list'],
            'today_promo_list'  => $history_promo_list['today_list']
        );
        if (!empty($deliver_date)) {
            $filter['deliver_date'] = $deliver_date;
        }
        $match_rule_return = $this->format_query('/promotion/get_rule', $filter);
        return !empty($match_rule_return['list']) ? $match_rule_return['list'] : array();
    }

    /**
     * 取消订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function cancel() {
        $_POST['status'] = C('order.status.closed.code');
        // 记录日志
        $cur = $this->_user_info_with_ip();
        $info = $this->format_query('/order/info', array(
            'order_id' => $_POST['order_id']
        ));
        $products = array();
        if (!empty($info['info'])) {
            foreach ($info['info']['suborders'] as $suborder) {
                foreach($suborder['products'] as $prod) {
                    $products[] = array(
                        'id'         => $prod['product_id'],
                        'sku_number' => $prod['sku_number'],
                        'quantity'   => $prod['quantity']
                    );
                }
            }
            $_POST['products'] = $products;
        }
        $_POST['cur'] = $cur;
        // 调用基础服务接口
        $return = $this->format_query('/order/set_status_closed', $_POST);
        if(intval($return['status']) === 0) {
            $this->_set_storage(array('order_id' => $_POST['order_id']), TRUE);
        }
        $this->_return_json($return);
    }

    /**
     * 订单详情
     *
     * @author caochunhui@dachuwang.com
     * @since 2015-03-07
     */
    public function info() {
        // 获取当前登录客户
        $cur = $this->userauth->current(TRUE);
        $_POST['user_id'] = 1;
        // 调用基础服务接口
        $return = $this->format_query('/order/info', $_POST);
        $this->_return_json($return);
    }

    /**
     * @description 早于最早配送时间的，要返回错误
     *
     * @author caochunhui@dachuwang.com
     */
    private function _check_deliver_time_valid($deliver_date, $deliver_time, $can_today_deliver) {
        $request_time = $this->input->server('REQUEST_TIME');
        // 日期是否为数字
        if (!is_numeric($deliver_date) || !is_numeric($request_time)) {
            return FALSE;
        }
        $hour = intval(date('H', $request_time));
        // 把时间转换为当天的0点0分0秒方便计算
        $new_request_time = strtotime(date('Y-m-d', $request_time));
        $new_deliver_date = strtotime(date('Y-m-d', $deliver_date));
        // 送货时间是否比明天更早
        if ($new_deliver_date < $new_request_time) {
            return FALSE;
        }
        // 送货日期时间戳不能包含时分秒
        if ($new_deliver_date != $deliver_date) {
            return FALSE;
        }
        // 当下单时间为23点以后，送货时间是否为明天
        if ($can_today_deliver) { // 如果可以当日达
            if ($hour === 23 && ($new_deliver_date - $new_request_time < 86400)) { // 如果不满足时间限制
                return FALSE;
            } else if ($hour < 11 && ($new_deliver_date - $new_request_time) < 0) {
                return FALSE;
            }
        } else {
            if ($hour === 23 && ($new_deliver_date - $new_request_time <= 86400)) {
                return FALSE;
            }
        }
        // 送货时间晚于7天后
        if ($new_deliver_date - $new_request_time > 86400 * 7) {
            return FALSE;
        }
        // 校验送货时间是否正确
        $deliver_time_config = array_values(C('order.deliver_time'));
        $allow_deliver_time = array_column($deliver_time_config, 'code');
        if (!in_array($deliver_time, $allow_deliver_time)) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * @description 获取用户当日的订单数
     */
    public function user_today_order_count_by_cate() {
        $cur = $this->userauth->current(TRUE);
        $_POST['user_id'] = $cur['id'];
        $_POST['site_id'] = C('site.dachu');
        if (!empty($_POST['user_id']) && !empty($_POST['site_id'])) {
            $return = $this->format_query('/order/user_today_order_count_by_cate', $_POST);
        }
        return !empty($return['info']) ? $return['info'] : array();
    }

    /**
     * @description 获取当前可用规则
     */
    private function _get_rule() {
        return array();
        $cur = $this->userauth->current(TRUE);
        $filter = array(
            'site_id' => C("site.dachu")
        );
        if (empty($cur)) {
            $filter['location_id'] = 0; // 取不限制地区的活动
        } else {
            $filter['location_id'] = $cur['province_id']; // 取用户所在地的活动
        }
        if (isset($this->post['first_limit'])) {
            $filter['first_limit'] = $this->post['first_limit'];
        }
        $return = $this->format_query('/promotion/get_rule', $filter);
        // 只取当前时间有效的活动
        return !empty($return['list']) ? $return['list'] : array();
    }

    /**
     * 编辑订单输入页面
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function edit_input() {
    }

    /**
     * 编辑订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function edit() {
    }
    /**
     * 删除订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function delete() {
    }

    /**
     * 添加订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function create() {

        // 获取当前登录客户
        $cur = $this->_user_info_with_ip();
        $_POST['user_id'] = $cur['id'];
        $_POST['site_id'] = C('site.dachu');
        if (empty($_POST['products'])) {
            $res['status'] = -1;
            $res['msg'] = '您的购物车没有商品，请重新操作。';
            $this->_return_json($res);
        }
        $this->_filter_app_post();
        //检查是否有不同客户类型的商品
        $ptype = $this->_check_customer_type($_POST['products'], $cur);

        // 检测其中是否有异市销售的商品---add by xianwen 15-4-8
        $ids = array_unique(array_column($_POST['products'], 'location_id'));
        if (array_diff(array($cur['province_id']), $ids)) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '购买商品中有其他城市销售的商品, 请去购物车检查下再下单'
            ));
        } else {
            $_POST['location_id'] = $ids[0];
        }

        // 检查配送时间是否可以当天下午
        $can_today_deliver = $this->_check_daily_deliver($_POST['products'], $cur['province_id']);
        // 检查配送时间是否合法
        $deliver_time = isset($_POST['deliver_time']) ? $_POST['deliver_time'] : '';
        $deliver_date = isset($_POST['deliver_date']) ? $_POST['deliver_date'] : '';
        if (!$this->_check_deliver_time_valid($deliver_date, $deliver_time, $can_today_deliver)) {
            $arr = array(
                'status' => -1,
                'msg' => '根据您下单的时间和产品，该配送时间不可选，请选择稍晚的配送时间。'
            );
            $this->_return_json($arr);
        }
        // 检测限购
        $this->_check_buy_limit($cur);
        $this->_set_post_products();
        // 检测商品中是否有在redis里面,
        // 并且购买的数量是否符合要求
        $check_info = $this->format_query('/stock_service/check_storage', array('products' => $_POST['products'], 'line_id' => $cur['line_id']));
        $_POST['products'] = isset($check_info['list']) ? $check_info['list'] : [];
        $this->check_storage->check($_POST['products'], $cur);


          // 检查活动规则是否匹配
        $rules = $_POST['rules'];
        $return = $this->_check_rules($rules, $cur, $_POST['products'], $deliver_date);
        if ($return['diff']) {
            $arr = array(
                'status' => -1,
                'msg' => '您的订单不能参与部分活动，请重新提交。'
            );
            $this->_return_json($arr);
        }
        $total_price = $this->_cal_total_price($_POST['products']);
        // 首先得确认是满足优惠条件猜出
        $minus_info = array();
        $valid = $this->_check_valid_trick_promo($_POST['products']);
        if (is_bool($valid) && !$valid) {
            $minus_info = $this->_check_by_trick_rules($total_price);
            $minus_info = $this->_check_valid_deliver_date($minus_info, $deliver_date);
        }
        $_POST['minus_info'] = $minus_info;
        // 存储最终的可参与的活动列表
        $_POST['rules'] = $return['rules'];
        // 传过来的优惠券id，需要检测
        if (!empty($_POST['coupon_id'])) {
            $customer_coupon_info = $this->format_query('/customer_coupon/check_coupon_valid', array(
                'total_price' => $total_price * 100,
                'id' => $_POST['coupon_id'],
                'customer_id' => $cur['id']
            ));
            if (isset($customer_coupon_info['info'])) {
                $_POST['coupon_info'] = $customer_coupon_info['info'];
            }
        }

        if(!empty($cur['customer_type'])){
            $_POST['customer_type'] = $cur['customer_type'];
        }
        $_POST['cur'] = $cur;
        if(!is_ios()) {
            $_POST['order_resource'] = C('order.resource.android.code');
        } else {
            $_POST['order_resource'] = C('order.resource.ios.code');
        }
        // 调用基础服务接口
        $return = $this->format_query('/order/add', $_POST);
        // @todo
        if(!empty($_POST['coupon_id'])) {
            $this->format_query('/customer_coupon/set_coupon_used_nums', array('id' => $_POST['coupon_id']));
        }
        // 修改订单中的商品的库存
        if (intval($return['status']) === 0) {
            $this->_set_storage(array('products' => $_POST['products'], 'line_id' => $cur['line_id']), FALSE);
        }
        $this->_return_json($return);
    }
    private function _set_post_products() {
        $product_ids = array_column($_POST['products'], 'id');
        $products_by_id = $this->format_query('/product/get_lists_by_ids', array('ids' => $product_ids, 'fields' => array('id', 'collect_type', 'sku_number')));
        if(empty($products_by_id['list'])) {
            $products_by_id['msg'] = '下单失败(error_code:mo663)';
            $this->_return_json($products_by_id);
        }
        $products_by_id = array_column($products_by_id['list'], 'collect_type', 'sku_number');
        foreach($_POST['products'] as &$post_product) {
            $post_product['collect_type'] = $products_by_id[$post_product['sku_number']];
        }
        unset($post_product);
    }

    private function _filter_app_post() {
        if(!empty($_POST['products']) && !is_array($_POST['products'])) {
            $_POST['products'] = json_decode(trim($_POST['products'], '"'), TRUE);
        }
        if(!empty($_POST['cartlist']) && !is_array($_POST['cartlist'])) {
            $_POST['cartlist'] = json_decode(trim($_POST['cartlist'], '"'), TRUE);
        }
        if(!empty($_POST['rules']) && !is_array($_POST['rules'])) {
            $_POST['rules'] = json_decode(trim($_POST['rules'], '"'), TRUE);
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 计算服务费
     */
    private function _calc_service_fee($customer_type, $total_price) {
        $service_fee = $this->format_query('/order/get_service_fee', array('customer_type' => $customer_type));
        $fee_rate = empty($service_fee['fee_rate']) ? 0 : $service_fee['fee_rate'];
        return ceil($fee_rate * $total_price/100);
    }

    //检查实时库存，只上天津上海，不包括蔬菜和冻品
    private function _realtime_storage_enough($products) {
        //实时库存的开关
        if(C('realtime_stock.switch') != 'on') {
            return array(TRUE, '');
        }
        $cur = $this->_user_info_with_ip();
        $line_id = $cur['line_id'];
        $city_id = $cur['province_id'];
        if(!in_array($city_id, C('realtime_stock.cities'))) {
            return array(TRUE, '');
        }

        $warehouse_id = $this->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );

        if(empty($warehouse_id)) {
            $warehouse_id = 0;
        } else {
            $warehouse_id = $warehouse_id['warehouse_id'];
        }

        //$products = $_POST['products'];
        if(empty($products)) {
            return array(TRUE, '');
        }

        $sku_numbers = array_column($products, 'sku_number');
        $sku_to_prod = array_combine($sku_numbers, $products);

        $quantity_in_db = $this->MStock->get_lists(
            '*',
            array(
                'warehouse_id' => $warehouse_id,
                'in' => array(
                    'sku_number'   => $sku_numbers
                )
            )
        );

        $skus = array_column($quantity_in_db, 'sku_number');
        $sku_to_stock = array_combine($skus, $quantity_in_db);

        //$virtual_stock = $quantity_in_db['virtual_stock'];
        $res = array(TRUE, '');

        $flag = TRUE;
        $prods = [];
        foreach($sku_numbers as $item) {
            if(!isset($sku_to_stock[$item])) {
                if($no_record_limit == "on") {
                    $stock_can_be_sold = 0;
                } else {
                    continue;
                }
            } else {
                $stock = $sku_to_stock[$item];
                $stock_can_be_sold = $stock['in_stock'] - $stock['stock_locked'];
            }
            $post_quantity = $sku_to_prod[$item]['quantity'];
            $collect_type = $sku_to_prod[$item]['collect_type'];
            if(($collect_type == C('foods_collect_type.type.pre_collect.value')) && ($stock_can_be_sold < $post_quantity)) {
                $prods[] = $sku_to_prod[$item];
                $flag = FALSE;
            }
        }
        return array($flag, $prods);

    }

    /**
     * @description 增加订单锁定库存
     */
    private function _inc_stock_locked($products = array(), $line_id) {
        $warehouse = $this->MLine->get_one('warehouse_id', array(
            'id' => $line_id
        ));
        $warehouse_id = empty($warehouse) ? 0 : $warehouse['warehouse_id'];
        if ($warehouse_id != 0) {
            foreach ($products as $product) {
                $sku_number = intval($product['sku_number']);
                $quantity = intval($product['quantity']);
                $this->db->query("update t_stock " . "set stock_locked = stock_locked + {$quantity} " . "where sku_number = {$sku_number} and warehouse_id = {$warehouse_id}");
            }
        }
    }

    /**
     * @description 增加订单锁定库存
     */
    private function _decr_stock_locked($products = array(), $line_id) {
        $warehouse = $this->MLine->get_one('warehouse_id', array(
            'id' => $line_id
        ));
        $warehouse_id = empty($warehouse) ? 0 : $warehouse['warehouse_id'];
        if ($warehouse_id != 0) {
            foreach ($products as $product) {
                $sku_number = intval($product['sku_number']);
                $quantity = intval($product['quantity']);
                $this->db->query("update t_stock " . "set stock_locked = stock_locked - {$quantity} " . "where sku_number = {$sku_number} and warehouse_id = {$warehouse_id}");
            }
        }
    }

    /**
     * 检查提交的活动规则是否合法
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-14
     */
    function _check_rules($rules, $cur, $products, $deliver_date) {
        $rule_ids = array_column($rules, "id");
        $rule_ids = !empty($rule_ids) ? $rule_ids : array();
        $rule_map = array_combine($rule_ids, $rules);
        $valid_rules = $this->_get_valid_promotions($cur, $_POST['products'], $deliver_date);
        $valid_rule_ids = array_column($valid_rules, "id");
        $valid_rule_ids = !empty($valid_rule_ids) ? $valid_rule_ids : array();
        $diff = FALSE;
        $final_rules = array();
        foreach ($rule_ids as $rule_id) {
            if (!in_array($rule_id, $valid_rule_ids)) {
                $diff = TRUE;
                break;
            }
            $final_rules[] = $rule_map[$rule_id];
        }
        return array(
            'diff' => $diff,
            'rules' => $final_rules
        );
    }
    /**
     * @description 设置订单库存 $del =true 为订单库存释放，反之
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _set_storage($stock_locked_info, $del = TRUE) {
        // 检测控制的限额
        switch($del) {
        case TRUE:
            $url = '/stock_service/decr_stock_locked';
            break;

        case FALSE :
            $url = '/stock_service/incr_stock_locked';
            break;
        }
        $response = $this->format_query($url, $stock_locked_info);
        if(!isset($response['status']) || $response['status'] == -1) {
            // TODO 日志
        }
    }
    /**
     * @description 已经买了的商品，然后检测其中是否有限购
     *
     * @author : liaoxianwen@ymt360.com
     */
    public function today_bought_products($cur = NULL, $return_arr = FALSE) {
        if (empty($cur)) {
            $cur = $this->_user_info_with_ip();
        }
        $return_data = $this->format_query('/product/get_today_bought_products', array(
            'user_info' => $cur
        ));
        $new_lists = [];
        if (!empty($return_data['list'])) {
            $product_ids = array_column($return_data['list'], 'product_id');
            foreach ($return_data['list'] as $v) {
                $new_lists[$v['product_id']]['product_id'] = $v['product_id'];
                if (!isset($new_lists[$v['product_id']]['quantity'])) {
                    $new_lists[$v['product_id']]['quantity'] = 0;
                }

                $new_lists[$v['product_id']]['quantity'] += $v['quantity'];
            }
            $return_data['list'] = $new_lists;
        }
        if ($return_arr) {
            return $new_lists;
        } else {
            $this->_filter_app_post();
            if(!empty($_POST['cartlist'])) {
                $compare_arr = $_POST['cartlist'];
                $this->_set_invalid_msg($compare_arr, $new_lists);
            }
            unset($return_data['list']);
            unset($return_data['xhprof']);
            $this->_return_json($return_data);
        }
    }

    private function _check_buy_limit($cur) {
        $list = $this->today_bought_products($cur, TRUE, $_POST['products']);
        if(!empty($_POST['products'])) {
            $compare_arr = $_POST['products'];
            $this->_set_invalid_msg($compare_arr, $list);
        }
   }

    private function _set_invalid_msg($compare_arr, $list) {
        foreach ($compare_arr as $v) {
            $redis_limit = $this->redisclient->hget($v['id'], 'buy_limit');
            if (intval($redis_limit)) {
                if (isset($list[$v['id']]['quantity'])) {
                    $quantity = intval($list[$v['id']]['quantity']) + intval($v['quantity']);
                    if ($quantity > $redis_limit) {
                        $this->_return_json(array(
                            'status' => C('tips.code.op_failed'),
                            'msg' => $v['title'] . "限购{$redis_limit}"
                        ));
                    }
                }
            }
        }

    }

    /**
     * @description 根据总金额来筛选优惠券
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _get_valid_coupon($products) {
        $post['customer_id'] = $this->_user_info['id'];
        $post['site_id'] = $this->_user_info['site_id'];
        $post['products'] = $products;
        // 待收货状态特俗处理，包括三种状态
        if (!empty($this->post['status'])) {
            $post['where']['status'] = C('coupon_status.valid.value');
        }
        $response_data = $this->format_query('/customer_coupon/valid_coupon', $post);
        $response = array();
        if (!empty($response_data['list'])) {
            $response = $response_data['list'];
        }
        return $response;
    }
    /**
     * @description 5-23~5-27 日
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _check_by_trick_rules($total_price, $matter_total_price = TRUE) {
        $trick_rules = C('trick_rules.chu');
        $ordercnt_return = $this->user_today_order_count_by_cate();
        $ordercnt = 0;
        if (!empty($ordercnt_return)) {
            foreach ($ordercnt_return as $cnt) {
                if ($cnt['cnt']) {
                    $ordercnt = $cnt['cnt'];
                }
            }
        }

        // 当前日期
        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        $trick_minus_info = array();
        foreach ($trick_rules as $rule) {
            if ($rule['valid_time'] <= $current && $rule['invalid_time'] >= $current) {
                if (empty($ordercnt)) {
                    if ($total_price * 100 >= $rule['require_amount']) {
                        $trick_minus_info = $rule;
                    }
                }
            }
        }
        return $trick_minus_info;
    }

    /**
     * @description 检测23号之前是否买过
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _check_customer_bought_before($timestr) {
        $cur = $this->userauth->current(TRUE);
        // 如果查到了，那么就不走这样的流程，目的是拉新
        $info = $this->format_query('/order/get_order_by_time', array(
            'valid_time' => $timestr,
            'user_id' => $cur['id']
        ));
        if (!empty($info['info'])) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 各个城市微信支付有效减免活动
     *
     * @return Ambigous <multitype:, boolean, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _pay_event_options($user = array()) {
        $options = C('payment.events');
        $events = array();
        $event_time = $this->input->server('REQUEST_TIME');
        if (!empty($options) && !empty($user)) {
            foreach ($options as $key => $value) {
                $event_start = strtotime($value['start_time']);
                $event_end = strtotime($value['end_time']);
                if ($event_time > $event_start && $event_time < $event_end) {
                    if ($user['info']['province_id'] == $key) {
                        $reduce_key = $value['require_amount'] = key($value['total_reduce']);
                        $value['minus_amount'] = $value['total_reduce'][$reduce_key];
                        unset($value['total_reduce']);
                        $events[] = $value;
                    }
                }
            }
            // 如果当天已经下过单，就不参与活动
            if ( $this->_count_today_orders($user['info']['id']) > 0) {
                return array();
            }
        }
        return !empty($events) ? $events : array();
    }

    /**
     * 统计用户当天下单情况
     *
     * @param unknown $user
     * @return Ambigous <number, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _count_today_orders($user_id) {
        // 查询当天下单情况, 避免接口调用失败都享受减免
        $today_orders = 'none';
        if (!empty($user_id)) {
            $result = $this->format_query('order/count_today_orders/' . $user_id);
            if(!empty($result) && $result['status'] == 0){
                $today_orders = is_numeric($result['msg']) ? $result['msg'] : 'none';
            }
        }
        return $today_orders;
    }
    /**
     * @description 检测购物车中是否有对应的分类
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _check_cart() {
        // 检测购物车里是否有满足条件的问题
        $products = !empty($_POST['cartlist']) ? $_POST['cartlist'] : array();
        if(!is_array($products)) {
            $products = json_decode(trim($products, '"'), TRUE);
        }
        $response = array();
        if ($products) {
            $valid = $this->_check_valid_trick_promo($products);
            if (is_bool($valid) && $valid) {
                // 则返回不满足的提示
                $response = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '您选择的商品超过活动参加条件，将无法享受活动优惠！（活动参加条件：食用油不超过3箱，面粉不超过5袋，酒水饮料不超过5箱）'
                );
            }
        }
        return $response;
    }

    private function _check_valid_trick_promo($products) {
        if (empty($products)) {
            return FALSE;
        }
        $trick_rule_arr = C('trick_rules.promo');
        $trick_promos = $trick_rule_arr['chu'];
        $rule = array(
            'valid_time' => $trick_rule_arr['valid_time'],
            'invalid_time' => $trick_rule_arr['invalid_time']
        );
        $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
        if ($rule['valid_time'] > $current || $rule['invalid_time'] < $current) {
            // 特殊处理
            return 2;
        }
        $ordercnt_return = $this->user_today_order_count_by_cate();
        $ordercnt = 0;
        foreach ($ordercnt_return as $cnt) {
            if ($cnt['cnt']) {
                $ordercnt = $cnt['cnt'];
            }
        }
        if ($ordercnt) {
            return FALSE;
        }
        $category_ids = array_unique(array_column($products, "category_id"));
        $path = $this->MCategory->get_lists("id, path", array(
            "in" => array(
                'id' => $category_ids
            ),
            "status" => C("status.common.success")
        ));
        $new_category_ids = array_column($path, 'id');
        $new_path = array_combine($new_category_ids, $path);
        foreach ($products as &$v) {
            $v['path'] = $new_path[$v['category_id']]['path'];
        }
        unset($v);
        // 在限定条件里，且数量超了限额，会给出提示
        $invalid_promo_products = array();
        foreach ($trick_promos as $k => $type) {
            $invalid_promo_products[$k]['quantity'] = 0;
            foreach ($products as $item) {
                $path_arr = explode('.', trim($item['path'], '.'));
                if ($inter = array_intersect($path_arr, $type['id'])) {
                    // 有限购的分类的话，那么就
                    $invalid_promo_products[$k]['quantity'] += $item['quantity'];
                }
            }
            // 判断数量是否满足limit
            if ($invalid_promo_products[$k]['quantity'] > $type['limit']) {
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * @description 检测是否满足最晚配送时间
     *
     * @author : liaoxianwen@ymt360.com
     */
    private function _check_valid_deliver_date($minus_info, $deliver_date) {
        $new_minus_info = array();
        if ($minus_info && $deliver_date) {
            if ($deliver_date <= $minus_info['deliver_date']) {
                $new_minus_info = $minus_info;
            }
        }
        return $new_minus_info;
    }

    private function _check_customer_type($products,$user){
        if(!empty($products) && !empty($user)){
            $return['status'] = C('tips.code.op_failed');
            $ptype = array_unique(array_column($_POST['products'], 'customer_type'));
            if (count($ptype) > 1 && $user['customer_type'] == C('customer.type.KA.value')) {
                $return['msg'] = '购买商品中有非VIP商品，请检查后再下单';
                $this->_return_json($return);
            } elseif (count($ptype) > 1 && $user['customer_type'] == C('customer.type.normal.value')) {
                $return['msg'] = '购买商品中有VIP专供商品，请检查后再下单';
                $this->_return_json($return);
            }
            return true;
        }


    }

    public function get_wx_order_info() {
        if(!empty($_POST['order_number'])) {
            $response_data = $this->format_query('/order/get_order', array('order_number' => $_POST['order_number']));
            if(is_array($response_data['data'])) {
                $response_data['data']['total_price'] /= 100;
                $response_data['data']['final_price'] /= 100;
                $response_data['data']['minus_amount'] /= 100;
                $response_data['data']['service_fee'] /= 100;
                $response_data['data']['deliver_fee'] /= 100;
            }
        } else {
            $response_data = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数缺失'
            );
        }
        if(!empty($response_data['data'])) {
            //过滤字段
            $required_fields = parent::$_app_required_fields['order']['get_wx_order_info']['data'];
            parent::_get_required_fields($response_data, 'data', $required_fields);
        }
        $this->_return_json($response_data);
    }
}
/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
