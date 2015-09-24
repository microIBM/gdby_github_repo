<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 订单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-06
 */
class Order extends MY_Controller {
    private $_user_info;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MOrder',
                'MWorkflow_log',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'redisclient',
                'check_storage'
            )
        );
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
        if(isset($_POST['status']) && $_POST['status'] == '100') {
            $_POST['status'] = $this->_wait_status_arr;
        }
        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        // 设置待收货状态的数量
        $return['total']['100'] = 0;
        //var_dump($return['total']);
        foreach($this->_wait_status_arr as $item) {
            $return['total']['100'] += !empty($return['total'][$item]) ? $return['total'][$item] : 0;
        }
        $this->_return_json($return);
    }

    /**
     * 查看订单
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function view() {
        // 调用基础服务接口
        $return = $this->format_query('/order/view', $_POST);
        $this->_return_json($return);
    }

    private function _user_info_with_ip() {
        $cur = $this->userauth->current(TRUE);
        if(empty($cur)) {
            return $cur;
        }
        $ip = $this->input->ip_address();
        $cur['ip'] = $ip;
        return $cur;
    }

    /**
     * @author caochunhui@dachuawng.com
     * @description 根据当前时间取能够配送的时间
     */
    public function deliver_dropdown() {
        $res = array(
            'status' => C("tips.code.op_failed"),
            'msg'    => ''
        );

        $_POST['site_id'] = C('site.daguo');
        $cur = $this->userauth->current(TRUE);

        $products = !empty($_POST['cartlist']) ? $_POST['cartlist'] : array();
        if(empty($products)) {
            $res['msg'] = '您的购物车没有商品，请重新操作。';
            $this->_return_json($res);
        }
        if(empty($cur)) {
            $res['msg'] = '您未登录，请登录后再下单。';
            $res['status'] = C('status.auth.login_timeout');
            $this->_return_json($res);
        }
        $_POST['province_id'] = $cur['province_id'];
        // 调用基础服务接口
        $res = $this->format_query('/order/deliver_dropdown', $_POST);
        // 获取用户基本信息
        $uinfo_return = $this->format_query('/customer/baseinfo', array('user_id' => $cur['id']));
        $res['user_info'] = $uinfo_return['info'];
        unset($res['user_info']['password']);
        unset($res['user_info']['salt']);
        $res['fee'] = 0;
        $res['total_price'] = $this->_cal_total_price($products);
        $res['minus_amount'] = 0;
        $res['sum'] = sprintf('%.2f', $res['total_price'] + $res['fee'] - $res['minus_amount']);
        $res['coupons'] = $this->_get_valid_coupon($products);
        $this->_return_json($res);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 根据总金额来筛选优惠券
     */
    private function _get_valid_coupon($products) {
        $post['customer_id'] = $this->_user_info['id'];
        $post['site_id'] = $this->_user_info['site_id'];
        $post['products'] = $products;
        // 待收货状态特俗处理，包括三种状态
        if(!empty($this->post['status'])) {
            $post['where']['status'] = C('coupon_status.valid.value');
        }
        $response_data = $this->format_query('/customer_coupon/valid_coupon', $post);
        $response = array();
        if(!empty($response_data['list'])) {
            $response = $response_data['list'];
        }
        return $response;
    }

    /**
     * 获取商品总价
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-05-14
     */
    private function _cal_total_price($products) {
        if(empty($products)) {
            return 0;
        }
        $total = 0;
        foreach($products as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * 取消订单
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function cancel() {
        $_POST['status'] = C('order.status.closed.code');
        // 记录日志
        $cur = $this->_user_info_with_ip();
        $info = $this->format_query('/order/info', array('order_id' => $_POST['order_id']));
        $remark = isset($_POST['sign_msg']) ? $_POST['sign_msg'] : '';
        //$this->MWorkflow_log->record_order($_POST['order_id'], $_POST['status'], $cur, $remark);
        // 调用基础服务接口
        $_POST['cur'] = $cur;
        $return = $this->format_query('/order/set_status_closed', $_POST);
        if(intval($return['status']) === 0 && !empty($info['info'])) {
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
            $this->_set_storage(FALSE);
        }
        $this->_return_json($return);
    }

    /**
     * 设置订单状态
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function set_status() {
        // 调用基础服务接口
        $return = $this->format_query('/order/set_status', $_POST);
        $this->_return_json($return);
    }

    /**
     * 订单详情
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
     * @author caochunhui@dachuwang.com
     * @description 早于最早配送时间的，要返回错误
     */
    private function _check_deliver_time_valid($deliver_date, $deliver_time) {
        $request_time = $this->input->server('REQUEST_TIME');
        //日期是否为数字
        if(!is_numeric($deliver_date) || !is_numeric($request_time)) {
            return FALSE;
        }
        $hour = intval(date('H', $request_time));
        //把时间转换为当天的0点0分0秒方便计算
        $new_request_time = strtotime(date('Y-m-d', $request_time));
        $new_deliver_date = strtotime(date('Y-m-d', $deliver_date));
        //送货时间是否比明天更早
        if($new_deliver_date <= $new_request_time) {
            return FALSE;
        }
        // 送货日期时间戳不能包含时分秒
        if($new_deliver_date != $deliver_date) {
            return FALSE;
        }
        //当下单时间为23点以后，送货时间是否为明天
        if($hour === 23 && ($new_deliver_date-$new_request_time<=86400)) {
            return FALSE;
        }
        //送货时间晚于1天后
        if($hour !== 23 && $new_deliver_date-$new_request_time>86400) {
            return FALSE;
        }
        // 校验送货时间是否正确
        $deliver_time_config = array_values(C('order.deliver_time_guo'));
        $allow_deliver_time = array_column($deliver_time_config, 'code');
        if (!in_array($deliver_time, $allow_deliver_time)) {
            return FALSE;
        }
        return TRUE;
    }
   /**
     * 编辑订单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function edit_input() {
    }

    /**
     * 编辑订单
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function edit() {
    }

    /**
     * 删除订单
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function delete() {
    }

    /**
     * 添加订单
     * @author liaoxianwen@dachuwang.com
     * @since 2015-05-06
     */
    public function create() {
        // 获取当前登录客户
        $cur = $this->_user_info_with_ip();
        $_POST['user_id'] = $cur['id'];
        $_POST['site_id'] = C('site.daguo');
        // 检测其中是否有异市销售的商品---add by xianwen 15-4-8
        $ids = array_unique(array_column($_POST['products'], 'location_id'));
        if(count($ids) > 1) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '购买商品中有其他城市销售的商品, 请去购物车检查下再下单'
                )
            );
        } else {
            $_POST['location_id']  = $ids[0];
        }
        //检查配送时间是否合法
        $deliver_time = isset($_POST['deliver_time']) ? $_POST['deliver_time'] : '';
        $deliver_date = isset($_POST['deliver_date']) ? $_POST['deliver_date'] : '';
        if(!$this->_check_deliver_time_valid($deliver_date, $deliver_time)) {
            $arr = array(
                'status' => -1,
                'msg'    => '根据您的下单时间，该配送时间不可选，请选择稍晚的配送时间'
            );
            $this->_return_json($arr);
        }
        // 检测限购
        $this->_check_buy_limit($cur);
        // 检测商品中是否有在redis里面,
        // 并且购买的数量是否符合要求
        $this->check_storage->check($_POST['products']);
        //$this->_check_storage();
        $this->_set_storage();
        $total_price = $this->_cal_total_price($_POST['products']);
        // 传过来的优惠券id，需要检测
        if(!empty($_POST['coupon_id'])) {
            $customer_coupon_info = $this->format_query('/customer_coupon/check_coupon_valid', array('total_price' => $total_price * 100, 'id' => $_POST['coupon_id'], 'customer_id' => $cur['id']));
            if(isset($customer_coupon_info['info'])) {
                $_POST['coupon_info'] = $customer_coupon_info['info'];
            }
        }
        $_POST['cur'] = $cur;
        // 调用基础服务接口
        $return = $this->format_query('/order/add', $_POST);
        // @todo
        if(!empty($_POST['coupon_id'])) {
            $this->format_query('/customer_coupon/set_coupon_used_nums', array('id' => $_POST['coupon_id']));
        }
        $this->_return_json($return);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置库存
     */
    private function _set_storage($del = TRUE) {
        // 检测控制的限额
        $keys = array_column($_POST['products'], 'id');
        $new_products = array_combine($keys, $_POST['products']);

        $update_products = array();// 异常
        if(is_array($keys)) {
            foreach($keys as $v) {
                $redis_data['storage'] = $this->redisclient->hget($v, 'storage');
                if($redis_data['storage']) {

                    $quantity = $new_products[$v]['quantity'];
                    if($del) {
                        if($redis_data['storage'] >= $quantity) {
                            $redis_data['storage'] -= $new_products[$v]['quantity'];
                            $quantity *= -1;
                        } else {
                            $this->_return(array(), '库存不足');
                        }
                    } else  {
                        $redis_data['storage'] += $quantity;
                    }

                    // 更新库存
                    $update_products[] = array(
                        'id' => $new_products[$v]['id'],
                        'storage' => $redis_data['storage'],
                        'decr_storage' => $quantity
                    );
                }
            }
        }
        if($update_products) {
            foreach($update_products as $up_val) {
                $this->redisclient->hincr($up_val['id'], 'storage', $up_val['decr_storage']);
            }
            $this->format_query('/product/update_storage', array('data' => $update_products));
        }
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测库存和限购
     */
    private function _check_storage() {
        // 检测控制的限额
        $keys = array_column($_POST['products'], 'id');
        $new_products = array_combine($keys, $_POST['products']);
        $abnormal_products = array();// 异常
        if(is_array($keys)) {
            foreach($keys as $v) {
                $redis_data['storage'] = $this->redisclient->hget($v, 'storage');
                if(!is_bool($redis_data['storage']) && $new_products[$v]['quantity'] > $redis_data['storage']){
                    $abnormal_products[$v]['current_storage'] = $redis_data['storage'];
                }
            }
        }
        $return_data = array();
        if($abnormal_products) {
            $msg = '';
            foreach($abnormal_products as $pv) {
                $msg .= isset($pv['title']) ? $pv['title']: '';
                if(isset($pv['current_storage'])) {
                    $msg .= '当前库存不足';
                }
                $msg = rtrim($msg, ',');
            }
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => $msg
                )
            );
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 已经买了的商品，然后检测其中是否有限购
     */
    public function today_bought_products($cur = NULL, $return_arr = FALSE) {
        if(empty($cur)) {
            $cur = $this->_user_info_with_ip();
        }
        $return_data = $this->format_query('/product/get_today_bought_products', array('user_info' => $cur));

        $new_lists = [];
        if(!empty($return_data['list'])) {
            $product_ids = array_column($return_data['list'], 'product_id');
            foreach($return_data['list'] as $v) {
                $new_lists[$v['product_id']]['product_id'] = $v['product_id'];
                if(!isset($new_lists[$v['product_id']]['quantity'])) {
                    $new_lists[$v['product_id']]['quantity'] = 0;
                }

                $new_lists[$v['product_id']]['quantity'] += $v['quantity'];
            }
            $return_data['list'] = $new_lists;
        }
        if($return_arr) {
            return $new_lists;
        } else {
            $this->_return_json($return_data);
        }
    }

    private function _check_buy_limit($cur) {
        $list = $this->today_bought_products($cur, TRUE, $_POST['products']);
        foreach($_POST['products'] as $v) {
            $redis_limit = $this->redisclient->hget($v['id'], 'buy_limit');;
            if(intval($redis_limit)) {
                if(isset($list[$v['id']]['quantity'])) {
                    $quantity = $list[$v['id']]['quantity'] + $v['quantity'];
                    if($quantity > $redis_limit) {
                        $this->_return_json(
                            array(
                                'status' => C('tips.code.op_failed'),
                                'msg' => $v['title'] . "限购{$redis_limit}"
                            )
                        );
                    }
                }
            }
        }
    }

}
/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
