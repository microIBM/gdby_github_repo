<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 订单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-07
 */
class Order extends MY_Controller {
    private $_order_status_dict = array(
        '0' => '已取消',
        '1' => '已成交',
        '2' => '未提货',
        '3' => '等待供应商确认'
    );

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MCustomer',
                'MWorkflow_log',
                'MLocation',
                'MLine',
                'MSuborder',
            )
        );
        $this->load->helper(array('money_helper'));
        $this->load->library(array('form_validation', 'filter_orders','order_split'));
    }

    /**
     * 待审核订单列表
     * @author yugang@dachuwang.com
     * @since 2015-03-10
     */
    public function lists_audit() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.wait_confirm.code');

        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }



    /**
     * @description 已生成波次
     * @author caochunhui@dachuawng.com
     */
    public function lists_wave_executed() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.wave_executed.code');

        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 分拣中
     */
    public function lists_picking() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.picking.code');

        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }

    //已分拣
    public function lists_picked() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.picked.code');

        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }


    /**
     * 正常订单列表
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function lists() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/order/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 订单详情
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function info() {
        // 查出有效的用户
        $this->check_validation('order', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/order/info', $_POST);
        if($return['info']){
            $_POST['site_id'] = $return['info']['site_src'];
            $_POST['urgent_deliver'] = C('status.common.success');
            $deliver_time = $this->format_query('/order/deliver_dropdown', $_POST);
            $return['deliver_time'] = $deliver_time['list'];
            $return['cancel_reason'] = array_values(C('order.cancel_reason'));
        }
        $this->_return_json($return);
    }

    /**
     * 添加工作人员备注
     * @author yugang@dachuwang.com
     * @since 2015-06-03
     */
    public function add_comment() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/order/add_comment', $_POST);
        $this->_return_json($return);
    }

    /**
     * 更支付方式接口
     * @method : POST
     * @param : $order_id 订单ID
     * @param : $pay_type 支付方式
     * @param : $remark_msg 操作原因
     * @author yuanxiaolin@dachuwang.com
     */
    public function set_pay_type() {
        $order_id = $this->input->post('order_id');
        $pay_type = $this->input->post('pay_type');
        $remark = $this->input->post('remark_msg');
        $return['status'] = C('status.req.failed');
        $return['msg'] = '修改失败';
        // 权限校验
        $check_code = $this->userauth->check_validation('order', 'edit', $module = '', $is_customer = FALSE);
        if ($check_code == C('status.auth.login_timeout')) {
            $return['msg'] = '登陆超时，请重新登陆';
        } elseif ($check_code == C('status.auth.forbidden')) {
            $return['msg'] = '没有操作权限';
        }
        if (!empty($order_id)) {
            $order_info = $this->MOrder->get_one(array('*'), array('id' => $order_id));
            if (!empty($order_info) && $order_info['pay_type'] == C('payment.type.weixin.code') && $order_info['pay_status'] == C('payment.status.waiting.code')) {
                //母单需要更新的信息
                $where['id'] = $order_id;
                $update['pay_type'] = C('payment.type.offline.code');
                $update['pay_status'] = C('payment.status.waiting.code');
                $update['pay_reduce'] = 0;
                $update['final_price']=$order_info['final_price'] + $order_info['pay_reduce'];

                //子单需要更新的信息
                $sub_update['pay_type'] = $update['pay_type'];
                $sub_update['pay_status'] = $update['pay_status'];
                $sub_where['order_id'] = $where['id'];

                $this->db->trans_begin();
                $up_order=$this->MOrder->update_info($update, $where);
                $up_order && $this->MSuborder->update_info($sub_update,$sub_where);
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                }
                else
                {
                    $this->db->trans_commit();
                    $return['status'] = C('status.req.success');
                    $return['msg'] = '修改成功';
                    $user_info = $this->userauth->current(FALSE);
                    $this->MWorkflow_log->record_order_comment($order_id, $user_info, $remark);
                }
            }
        }
        $this->_return_json($return);
    }


    /**
     * 编辑订单
     * @author yugang@dachuwang.com
     * @since 2015-03-19
     */
    public function edit() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/order/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-05-07
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('order', 'list', '', FALSE);
        // 调用基础服务接口
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $return = $this->format_query('/line/lists', $_POST);
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        $return['deliver_time'] = array(
            array(
                'name' => '全天',
                'val' => 0
            ),
            array(
                'name' => '上午',
                'val' => 1
            ),
            array(
                'name' => '下午',
                'val' => 2
            )
        );
        //$order_type = array_values(C('order.order_type'));
        $order_type = $this->order_split->get_config();
        $return['order_type'] = $order_type;

        $this->_return_json($return);
    }


    /**
     * @interface:获取在线支付订单lists
     * @method: post
     * @param int $pay_type 支付类型：0，货到付款 1，微信支付
     * @param int $pay_status 支付状态：0，未支付 1，支付成功 －1，支付失败
     * @param int $site_src 站点ID：1大厨网，2大果网
     * @param int $siteId   城市ID
     * @param string $searchValue 关键词检索
     * @param date $startTime
     * @param date $endTime
     * @param int @currentPage
     * @param string $itemsPerPage
     * @author yuanxiaolin@dachuwang.com
     */
    public function lists_online_pay(){
        $result = $this->format_query('/order/lists_online_pay', $_POST);
        $orders = array();
        $customers = array();
        $pay_type_count = array();
        if ($result['status'] == 0 && !empty($result['count'])){
            $pay_type_count = $result['count'];
        }

        if ($result['status'] == 0 && !empty($result['msg'])) {
            $orders = $result['msg'];
            $post_data['uids'] = implode('-',array_unique(array_column($orders,'user_id')));
            $result = $this->format_query('customer/lists_by_uids',$post_data);
            if($result['status'] == 0 && !empty($result['msg'])){
                $customers = $result['msg'];
            }
        }

        if(!empty($orders) && !empty($customers)){
            $payment_config = C('payment');
            $orders_config = C('order');
            foreach ($orders as $key => $value){
                //客户信息
                if (key_exists($value['user_id'], $customers)) {
                    $orders[$key]['user_info'] = $customers[$value['user_id']];
                }
                //支付类型
                foreach ($payment_config['type'] as $pay_type){
                    if($value['pay_type'] == $pay_type['code']){
                        $orders[$key]['pay_type'] = $pay_type;
                    }
                }
                //支付状态
                foreach ($payment_config['status'] as  $pay_status){
                    if ($value['pay_status'] == $pay_status['code']) {
                        $orders[$key]['pay_status'] = $pay_status;
                    }
                }
                //订单状态
                foreach ($orders_config['status'] as $order_status){
                    if($value['status'] == $order_status['code']){
                        $orders[$key]['order_status'] = $order_status;
                    }
                }

                //大厨网送货时间
                foreach ($orders_config['deliver_time'] as $deliver_time){
                	if ($value['deliver_time'] == $deliver_time['code']) {
                		$orders[$key]['deliver_time'] = $deliver_time;
                	}
                }
                $order_total_price = $orders[$key]['total_price'] + $orders[$key]['deliver_fee'] - $orders[$key]['minus_amount'];
                $orders[$key]['created_time'] = date("Y-m-d H:i:s",$value['created_time']);
                $orders[$key]['deliver_date'] = date('Y/m/d',$value['deliver_date']);
                $orders[$key]['total_price'] =$order_total_price/100;
                $orders[$key]['deal_price'] /= 100;
                $orders[$key]['minus_amount'] /= 100;
                $orders[$key]['deliver_fee'] /=100;
                $orders[$key]['pay_price'] = ($order_total_price - $orders[$key]['pay_reduce'])/100;
                $orders[$key]['pay_reduce'] /= 100;

            }
        }

        $this->_return_json(array('status'=>0,'msg'=>'success','data'=>$orders,'count' => $pay_type_count ));

    }

    /**
     * 统计各种不同支付状态订单总数
     * @method post
     * @author yuanxiaolin@dachuwang.com
     */
    public function count_online_pay(){
        $config_pay = C('payment');
        $where['pay_type'] = $config_pay['type']['weixin']['code'];
        $data['all'] = $this->MOrder->count($where);
        $data['pay_waitting'] = $this->MOrder->count(array_merge($where,array('pay_status' => $config_pay['status']['waiting']['code'])));
        $data['pay_success'] = $this->MOrder->count(array_merge($where,array('pay_status' => $config_pay['status']['success']['code'])));
        $data['pay_failed'] = $this->MOrder->count(array_merge($where,array('pay_status' => $config_pay['status']['failed']['code'])));
        $this->_return_json($data);
    }

    /**
     * 审核订单
     * @author yugang@dachuwang.com
     * @since 2015-06-08
     */
    public function set_order_confirmed() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/order/set_status_confirmed', $_POST);
        $this->_return_json($return);
    }

    /**
     * 取消订单
     * @author yugang@dachuwang.com
     * @since 2015-06-08
     */
    public function cancel_order() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

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
        $return = $this->format_query('/order/set_status_closed', $_POST);
        // 取消订单时调用库存服务减库存
        if (!empty($return) && $return['status'] == C('status.req.success')) {
            $return = $this->format_query('/stock_service/decr_stock_locked', ['order_id' => $_POST['order_id']]);
        }
        $this->_return_json($return);
    }

    /**
     * 修改配送时间
     * @author yugang@dachuwang.com
     * @since 2015-06-09
     */
    public function change_deliver_time() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;
        // 调用基础服务接口
        $return = $this->format_query('/order/change_deliver_time', $_POST);
        $this->_return_json($return);
    }
}

/* End of file order.php */
/* Location: ./application/controllers/order.php */
