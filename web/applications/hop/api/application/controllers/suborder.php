<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 子订单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-06-08
 */
class Suborder extends MY_Controller {
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
                'MLocation'
            )
        );
        $this->load->helper(array('money_helper'));
        $this->load->library(array('form_validation', 'filter_orders','order_split'));
    }


    /**
     * 待出库订单列表
     * @author yugang@dachuwang.com
     * @since 2015-06-08
     */
    public function lists_deliver() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        if(!empty($_POST['chosedate'])) {
            $deliver_date = strtotime($_POST['chosedate']);
            $_POST['deliver_date_str'] = date('Y-m-d', $deliver_date);
            $_POST['deliver_date'] = $deliver_date;
        }
        $_POST['status'] = C('order.status.confirmed.code');

        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
        $return['cities'] = $cities;

        $this->_return_json($return);
    }

    /**
     * 待分配线路订单列表
     * @author yugang@dachuwang.com
     * @since 2015-06-08
     */
    public function lists_assign() {
        // 权限校验
        $this->check_validation('order', 'list', '', FALSE);
        if(!empty($_POST['chosedate'])) {
            $deliver_date = $_POST['chosedate']/1000;
            $_POST['deliver_date_str'] = date('Y-m-d', $deliver_date);
            $_POST['deliver_date'] = $deliver_date;
        }
        if(!empty($_POST['chosetime']) && is_numeric($_POST['chosetime'])) {
            $_POST['deliver_time'] = $_POST['chosetime'];
        }
        $_POST['status'] = C('order.status.checked.code');
        // 线路规划单独上线，对待出库订单进行规划
        $_POST['list_type'] = 'distribution';
        $_POST['order_by'] = array('line_id' => 'ASC', 'user_id' => 'DESC', 'created_time' => 'DESC');
        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 待签收订单列表
     * @author yugang@dachuwang.com
     * @since 2015-03-11
     */
    public function lists_sign() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.delivering.code');

        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
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
        $return = $this->format_query('/suborder/lists', $_POST);
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
        $return = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($return);
    }

    //已分拣
    public function lists_picked() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.picked.code');

        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($return);
    }


    /**
     * 待回款订单列表
     * @author yugang@dachuwang.com
     * @since 2015-03-11
     */
    public function lists_payment() {
        // 查出有效的用户
        $this->check_validation('order', 'list', '', FALSE);
        $_POST['status'] = C('order.status.wait_comment.code');

        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
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
        $return = $this->format_query('/suborder/lists', $_POST);
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
        $return = $this->format_query('/suborder/info', $_POST);
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

        // 记录日志
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
        $result = $this->MWorkflow_log->record_order_comment($_POST['order_id'], $cur, $remark);
        if($result) {
            $this->_return(TRUE);
        }
        $this->_return(FALSE);
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
        $return = $this->format_query('/suborder/edit', $_POST);
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
        $result = $this->format_query('/suborder/lists_online_pay', $_POST);
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
        $return = $this->format_query('/suborder/set_order_confirmed', $_POST);
        $this->_return_json($return);
    }

    /**
     * 发货
     * @author yugang@dachuwang.com
     * @since 2015-06-09
     */
    public function set_delivering() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/suborder/set_status_delivering', $_POST);
        $this->_return_json($return);
    }

    /**
     * 签收
     * @author yugang@dachuwang.com
     * @since 2015-06-09
     */
    public function set_signed() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/suborder/set_status_signed', $_POST);
        $this->_return_json($return);
    }


    /**
     * 退货
     * @author yugang@dachuwang.com
     * @since 2015-06-09
     */
    public function set_rejected() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/suborder/set_status_rejected', $_POST);
        $this->_return_json($return);
    }

    /**
     * 回款
     * @author yugang@dachuwang.com
     * @since 2015-06-09
     */
    public function set_success() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;

        // 调用基础服务接口
        $return = $this->format_query('/suborder/set_status_success', $_POST);
        $this->_return_json($return);
    }


    /**
     * 修改运费
     * @author yugang@dachuwang.com
     * @since 2015-07-10
     */
    public function change_deliver_fee() {
        // 权限校验
        $this->check_validation('order', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;
        // 调用基础服务接口
        $return = $this->format_query('/suborder/change_deliver_fee', $_POST);
        $this->_return_json($return);
    }
}

/* End of file order.php */
/* Location: ./application/controllers/order.php */
