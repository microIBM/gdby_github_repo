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
            'product_lib',
            'check_storage',
            'product_price'
        ));
        $this->_wait_status_arr = array(
            C('order.status.confirmed.code'),
            C('order.status.wave_executed.code'),
            C('order.status.picking.code'),
            C('order.status.picked.code'),
            C('order.status.checked.code'),
            C('order.status.allocated.code'),
            C('order.status.delivering.code'),
            C('order.status.loading.code'),
        );
        $this->_user_info = $this->userauth->current(TRUE);
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
        // 调用基础服务接口
        $return = $this->format_query('/order/mall_lists', $_POST);
        // 设置待收货状态的数量
        $return['total']['100'] = 0;
        foreach ($this->_wait_status_arr as $item) {
            if(!empty($return['total'][$item])) {
                $return['total']['100'] += $return['total'][$item];
            }
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
        $cur = $this->userauth->current(TRUE);
        if (empty($cur['id'])) {
            $res['msg'] = '您未登录，请登录后再下单。';
            $res['status'] = C('status.auth.login_timeout');
            $this->_return_json($res);
        }

        $_POST['user_id'] = $cur['id'];
        $_POST['site_id'] = C('site.dachu');

        $res['cartlist'] = $_POST['cartlist'] = $this->product_price->get_rebate_price($_POST['cartlist'], $cur['id']);
        $products = !empty($_POST['cartlist']) ? $_POST['cartlist'] : array();
        if (empty($products)) {
            $res['msg'] = '您的购物车没有商品，请重新操作。';
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

        // 检查有没有水果，有水果就不能下午配送
        $can_after = $this->_can_afternoon_deliver($products);
        if(!$can_after) {
            $date_dropdown = array();
            foreach($res['date_dropdown'] as $item) {
                $new_time = $item;
                foreach($item['time'] as $time) {
                    if($time['code'] == 1) {
                        $new_time['time'] = array($time);
                        $date_dropdown[] = $new_time;
                    }
                }
            }
            $res['date_dropdown'] = $date_dropdown;
            $res['deliver_notice'] = "亲，水果暂时不支持下午配送，您可以将水果单独生成其他时段的订单哦！";
        }

        // 获取用户基本信息
        $uinfo_return = $this->format_query('/customer/baseinfo', array(
            'user_id' => $cur['id']
        ));

        $res['user_info'] = $uinfo_return['info'];
        unset($res['user_info']['password']);
        unset($res['user_info']['salt']);
        unset($res['user_info']['geo']);
        $site_id = C('app_sites.chu.id');
        $city_id = $cur['province_id'];
        $customer_type = $uinfo_return['info']['customer_type'];

        // 获取合适的规则
        $res['promotion_list'] = $this->_get_valid_promotions($cur, $products);

        // 获取活动优惠总价
        $res['minus_amount'] = $this->_cal_minus_amount($res['promotion_list']);

        // 计算购物车总价
        $res['total_price'] = $this->_cal_total_price($products);

        // 计算购物车ka 服务费
        $res['service_fee'] = $this->_calc_service_fee($uinfo_return['info']['customer_type'], $res['total_price']);

        // 首先根据城市获取运费信息
        $deliver_fee_rule = $this->MDeliver_fee->get_one('*', array(
            'city_id' => $city_id,
            'site_id' => $site_id
        ));
        // 如果对线路单独设置的运费，则采用线路运费
        $line_deliver_fee_rule = array_values(C('deliver_fee'));
        $deliver_fee_map = array_column($line_deliver_fee_rule, NULL, 'line_id');
        if (isset($deliver_fee_map[$res['user_info']['line_id']])) {
            $deliver_fee_rule = $deliver_fee_map[$res['user_info']['line_id']];
        }
        $res['fee'] = 0;
        $res['free_amount'] = 0;
        if (!empty($deliver_fee_rule) && $res['total_price'] < $deliver_fee_rule['free_amount'] / 100) {
            $res['fee'] = $deliver_fee_rule['fee'] / 100;
            $res['free_amount'] = $deliver_fee_rule['free_amount'] / 100;
        }

        $res['status'] = C("tips.code.op_success");
        // 计算总价
        $this->_calc_sum_price($res);
        $res['coupons'] = $this->_get_valid_coupon($products);
        // 支付连接
        $res['pay_url'] = C('urls.weixin.pay_url');
        $res['cur'] = $cur;
        // 支付推广活动
        $res['pay_events'] = $this->_pay_event_options($uinfo_return);
        // 支付推广活动title, KA客户不显示
        if($customer_type != C('customer.type.KA.value')) {
            $res['pay_events_title'] = $this->_pay_event_options($uinfo_return, true);
        }
        if(empty($res['pay_events_title'])) {
            $res['pay_events_title'] = '';
        }
        $res['sub_account_address'] = $this->_get_sub_account_address($cur['id']);
        $res['payments'] = $this->_get_payments($cur);
        $this->_return_json($res);
    }

    /**
     * @param 母账号id
     * @description 获取子账号地址
     */
    private function _get_sub_account_address($id) {
        $response = $this->format_query('/customer/sub_account_address',
            array(
                'id' => $id
            )
        );
        return empty($response['list']) ? [] : $response['list'];
    }

    private function _get_payments($cur) {
        $payments = C('payment.type');
        if(!empty($cur['parent_mobile'])) {
            $info = $this->format_query('/customer/get_parent_info_by_mobile', array('mobile' => $cur['parent_mobile']));
            isset($info['info']['billing_cycle']) AND $cur['billing_cycle'] = $info['info']['billing_cycle'];
        }
        if(empty($cur['billing_cycle']) || $cur['billing_cycle'] == 'none'){
            unset($payments['bill_pay']);
        }
        $payments = array_values($payments);
        return $payments;
    }

    private function _calc_sum_price(&$res) {
         // 设置应付总额
        $res['sum'] = sprintf('%.2f', $res['total_price'] + $res['fee'] - $res['minus_amount'] + $res['service_fee']);
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
        //现采类型不支持当日配送
        $pid_array = array_column($products,"id");
        $collect_types = $this->MProduct->get_lists('collect_type', array(
            'in' => array("id" => $pid_array)
        ));
        $collect_type_array = array_column($collect_types, 'collect_type');
        if (in_array(C('foods_collect_type.type.now_collect.value'), $collect_type_array)) {
            return FALSE;
        }
        //预采的冻品返回false
        $cids = array_column($products,'category_id');
        $all_path_infos = array();
        if ($cids) {
            $all_path_infos = $this->MCategory->get_lists('path',array(
                'in' => array('id' => $cids),
                'like' => array('path' => ('.' . C('category.category_type.frozen_class.code') . '.'))
            ));
        }
        if ($all_path_infos) {
            return FALSE;
        }
        // 如果当前城市不允许当日达
        if (!in_array($city_id, C("daily_deliver_city.cities"))) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 检查是否包含水果，如果有水果，不能下单
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-07-02
     */
    private function _can_afternoon_deliver($products) {
        // 找出所有的水果类别
        $fruit_cats = $this->MCategory->get_lists('id', array(
            'like' => array(
                'path' => '.' . C("category.category_type.fruit.code") . '.'
            )
        ));
        $fruit_cat_ids = array_column($fruit_cats, "id");
        foreach($products as $item) {
            if(in_array($item['category_id'], $fruit_cat_ids)) {
                return FALSE;
            }
        }
        return TRUE;
    }



    /**
     * 获取活动优惠总额
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
        if (!empty($info['info'])) {
            $products = array();
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

        // 调用基础服务接口
        $_POST['cur'] = $cur;
        $return = $this->format_query('/order/set_status_closed', $_POST);
        $remark = isset($_POST['sign_msg']) ? $_POST['sign_msg'] : '';
        if(intval($return['status']) === 0) {
            $this->_set_storage(array('order_id' => $_POST['order_id']));
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
        // 调用基础服务接口
        $return = $this->format_query('/order/info', $_POST);
        if(!empty($return['info'])) {
            $new_return = $this->_format_order_info($return);
        }
        if(isset($new_return)) {
            $return = $new_return;
        }
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
     * 添加订单
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function create() {
        if(!empty($_POST['subUserId']) && $this->_user_info['id'] != $_POST['subUserId']) {
            $this->load->model('MCustomer');
            $cur = $this->MCustomer->get_user_info(array('id' => $_POST['subUserId']));
        } else {
            $cur = $this->_user_info;
        }

        //检查选择账期支付的客户是否是账期客户
        if($_POST['pay_type'] == C('payment.type.bill_pay.code')) {
            //检查客户是否是账期客户
            if($this->_user_info['account_type'] == C('customer.account_type.parent.value')) {
                $billing_cycle = $this->_user_info['billing_cycle'];
            } else {
                $where = array('mobile' => $this->_user_info['parent_mobile']);
                $parent_info = $this->format_query('/customer/get_parent_info_by_mobile', $where);
                $billing_cycle = empty($parent_info['info']) ? '' : $parent_info['info']['billing_cycle'];
            }
            if($billing_cycle == 'none' || empty($billing_cycle)) {
                $this->_return_json(array('status' => -1, 'msg' => '只有账期客户才能账期支付'));
            }
        }

        // 获取当前登录客户
        //$cur = $this->_user_info_with_ip();
        $_POST['user_id'] = $cur['id'];
        $_POST['site_id'] = C('site.dachu');

        if (empty($_POST['products'])) {
            $res['status'] = -1;
            $res['msg'] = '您的购物车没有商品，请重新操作。';
            $this->_return_json($res);
        }

        $_POST['products'] = $this->product_price->get_rebate_price($_POST['products'], $cur['id']);
        // 检测其中是否有异市销售的商品---add by xianwen 15-4-8
        $ids = array_unique(array_column($_POST['products'], 'location_id'));
        if(array_diff(array($cur['province_id']), $ids)) {
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

        // 检测商品中是否有在redis里面,
        // 并且购买的数量是否符合要求
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

        // 存储普通的活动列表
        $_POST['rules'] = $return['rules'];

        // 传过来的优惠券id，需要检测
        $_POST['coupon_info'] = array();
        if (!empty($_POST['coupon_id'])) {
            $customer_coupon_info = $this->format_query('/customer_coupon/check_coupon_valid', array(
                'total_price' => $total_price * 100,
                'id'          => $_POST['coupon_id'],
                'customer_id' => $this->_user_info['id']
            ));
            if (isset($customer_coupon_info['info'])) {
                $_POST['coupon_info'] = $customer_coupon_info['info'];
            }
        }

        if(!empty($cur['customer_type'])){
            $_POST['customer_type'] = $cur['customer_type'];
        }
        $_POST['cur'] = $cur;
        $_POST['user_id'] = $cur['id'];
        // 增加订单来源
        $_POST['order_resource'] = C('order.resource.chu.code');
        // 调用基础服务接口
        $return = $this->format_query('/order/add', $_POST);

        // @todo
        if(!empty($_POST['coupon_id'])) {
            $this->format_query('/customer_coupon/set_coupon_used_nums', array('id' => $_POST['coupon_id']));
        }
        if(intval($return['status']) === 0) {
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

        $res = array(TRUE, '');

        $flag = TRUE;
        $prods = [];
        $no_record_limit = C("realtime_stock.no_record_limit");
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
     * @author changshaoshuai@dachuwang.com
     * @param order_id 订单id
     * @param order_type 订单类型：1母订单 2子订单
     * @description 再次购买
     */
    public function buy_again() {
        $tips = C('tips.code');
        $cur = $this->_user_info_with_ip();
        empty($cur['id']) AND $this->_return_json(array('status' => $tips['op_success'], 'msg' => '请先登录'));

        $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : NULL;
        //1母订单号，2子订单号
        $order_type = isset($_POST['order_type']) ? $_POST['order_type'] : NULL;
        (empty($order_id) || empty($order_type)) AND $this->_return_json(
            array(
                'status' => $tips['op_failed'],
                'msg'    => '请传入订单id或者订单类型！'
            )
        );
        $order_status = C('order.status');
        /**** 获取sku_number ****/
        $post = array(
            'order_id' => $order_id,
            'order_type' => $order_type,
            'status'   => array(
                $order_status['closed']['code'],
                $order_status['success']['code'],
                $order_status['wait_comment']['code'],
                $order_status['sales_return']['code']
            )
        );
        $order_list = $this->format_query('/order/get_order_detail_by_id', $post);

        empty($order_list['list']) AND $this->_return_json(array('status' => $tips['op_failed'], 'msg' => '该订单不存在！'));
        $sku_numbers = array_column($order_list['list'], 'sku_number');

        /**** 获取商品列表 ****/
        $where = array(
            'sku_number' => $sku_numbers,
            'location_id' => $cur['province_id'],
            'customer_type' => $cur['customer_type']
        );
        $response = $this->format_query('/product/get_products_by_sku', $where);
        if(!empty($response['list'])) {
            if(isset($cur['id'])) {
                $response['list'] = $this->product_price->get_rebate_price($response['list'], $cur['id'], FALSE);
                $product_list = $this->product_lib->set_product_fields($response['list']);
                $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
                $this->product_lib->set_default_check_storage_list($check_storage_info, $response['list']);
            }
            if(!empty($response['list']))  {
                $response['list'] = $this->product_lib->format_shop_product_list($response['list']);
                $response['list'] = $this->_format_product_storage($response['list']);
            }
            $this->_return_json($response);
        }
        $this->_return_json(array('status' => $tips['op_failed'], 'msg' => '商品已下架!'));
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
            $this->_return_json($return_data);
        }
    }

    private function _check_buy_limit($cur) {
        $list = $this->today_bought_products($cur, TRUE, $_POST['products']);
        foreach ($_POST['products'] as $v) {
            $redis_limit = $this->redisclient->hget($v['id'], 'buy_limit');
            ;
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
        if ( ! empty($response_data['list'])) {
            $response = $response_data['list'];
        }
        return $response;
    }

    /**
     * 各个城市微信支付有效减免活动
     *
     * @return Ambigous <multitype:, boolean, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _pay_event_options($user = array(), $return_title = false) {
        $options = C('payment.events');
        $events = array();
        $event_time = $this->input->server('REQUEST_TIME');
        if (!empty($options) && !empty($user)) {
            foreach ($options as $key => $value) {
                $event_start = strtotime($value['start_time']);
                $event_end = strtotime($value['end_time']);
                if ($event_time > $event_start && $event_time < $event_end) {
                    $events[$key] = $value;
                    if ($return_title && $user['info']['province_id'] == $key) {
                        return !empty($value['event_title']) ? $value['event_title'] : '';
                    }
                }
            }
            // 如果当天已经下过单，就不参与活动 || 如果是KA客户不参加活动
            if ( $this->_count_today_orders($user['info']['id']) > 0 || $user['info']['customer_type'] == C('customer.type.KA.value')) {
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
    /**
     * @description 格式化订单详情信息
     */
    private function _format_order_info($return) {
        $pay_type_arr = array_column(C('payment.pay_types'), 'msg', 'code');
        $new_return['status'] = $return['status'];
        $new_return['info'] = array(
            'id'     => $return['info']['id'],
            'status'       => $return['info']['status'],
            'status_cn'    => $return['info']['status_cn'],
            'order_number' => $return['info']['order_number'],
            'order_status' => $return['info']['status'],
            'minus_amount' => $return['info']['minus_amount'],
            'deliver_fee'  => $return['info']['deliver_fee'],
            'deposit'      => $return['info']['deposit'],
            'total_price'  => $return['info']['total_price'],
            'final_price'  => $return['info']['final_price'],
            'deal_price'   => $return['info']['deal_price'],
            'deliver_addr' => $return['info']['deliver_addr'],
            'created_time' => $return['info']['created_time'],
            'deliver_time' => $return['info']['deliver_date']. ' '.$return['info']['deliver_time'],
            'remarks'      => $return['info']['remarks'],
            'pay_type'     => $return['info']['pay_type'],
            'pay_status'   => $return['info']['pay_status'],
            'pay_type_cn'  => $pay_type_arr[$return['info']['pay_type']],
            'pay_reduce'   => $return['info']['pay_reduce'],
            'customer_side_status' => $return['info']['customer_side_status'],
            'pay_url'      => C('urls.weixin.pay_url')
        );
        if(!empty($return['info']['suborders'])) {
            //子账单
            foreach($return['info']['suborders'] as $k => $suborder) {
                $new_return['info']['deposit'] += $suborder['deposit'];
                $tmp_product = array(
                    'suborder_id'  => $suborder['id'],
                    'order_number' => $suborder['order_number'],
                    'minus_amount' => sprintf('%.2f', $suborder['minus_amount'] / 100),
                    'total_price'  => sprintf('%.2f', $suborder['total_price'] / 100),
                    'pay_reduce'   => sprintf('%.2f', $suborder['pay_reduce'] /100),
                    'final_price'  => sprintf('%.2f', $suborder['final_price'] / 100),
                    'deal_price'  => sprintf('%.2f', $suborder['deal_price'] / 100),
                    'deliver_fee'  => sprintf('%.2f', $suborder['deliver_fee'] / 100),
                    'log_list'     => empty($suborder['log_list']) ? [] : $suborder['log_list'],
                    'sign_img_url' => isset($suborder['sign_img_url']) ? $suborder['sign_img_url'] : '',
                    'deposit'      => $suborder['deposit'],
                );
                foreach($suborder['products'] as $product) {
                    foreach($product['spec'] as $spec) {
                        preg_match('/规格/i', $spec['name'], $get);
                        if(!empty($get)) {
                            $pro_spec = $spec['val'];
                            break;
                        }
                    }
                    $tmp_product['product'][] = array(
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'actual_quantity' => $product['actual_quantity'],
                        'spec' => isset($pro_spec) ? $pro_spec : '',
                        'unit_id' => $product['unit_id']
                    );
                }
                $new_return['info']['list'][$k] = $tmp_product;
            }
        }
        return $new_return;
    }

    /**
     * @description 过滤库存为0的商品
     */
    private function _format_product_storage($products) {
        $new_products = array();
        foreach($products as $product) {
            if($product['storage'] == 0) {
                continue;
            }
            $new_products[] = $product;
        }
        return $new_products;
    }


}
/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
