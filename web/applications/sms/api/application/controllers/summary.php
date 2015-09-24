<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author caochunhui@ymt360.com
 * @description 统计信息
 */

class Summary extends MY_Controller {

    private $_default_user_type;
    
    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MUser',
                'MProduct',
                'MOrder'
            )
        );
        $this->_default_user_type = C('user.normaluser.supply.type');
    }

    /**
     * @author: modify by liaoxianwen@ymt360.com
     * @description 运营首页展示数据
     */
    public function index() {
        $time = $this->input->server('REQUEST_TIME');
        $start_time = strtotime(date('Y-m-d', $time));
        $end_time = $start_time + 86400;
        $show_condition = C('status.common.success');
        //data area
        //今日新增用户，总用户
        $today_new_user_count = $this->MUser->count_by(
            array('status', 'created_time >=', 'created_time <'),
            array($show_condition, $start_time, $end_time)
        );
        $total_user_count = $this->MUser->count_by(
            array('status'),
            array($show_condition)
        );
        //今日新增采购商，总采购商
        $today_new_buyer_count = $this->MUser->count_by(
            array('status', 'created_time >=', 'created_time <', 'type'),
            array($show_condition, $start_time, $end_time, C('user.normaluser.purchase.type'))
        );
        $total_buyer_count = $this->MUser->count_by(
            array('status', 'type'),
            array($show_condition, C('user.normaluser.purchase.type'))
        );
        //今日新增供应商，总供应商
        $today_new_seller_count = $this->MUser->count_by(
            array('status', 'created_time >=', 'created_time <', 'type'),
            array($show_condition, $start_time, $end_time, C('user.normaluser.supply.type'))
        );
        $total_seller_count = $this->MUser->count_by(
            array('status', 'type'),
            array($show_condition, C('user.normaluser.supply.type'))
        );
        // 查出采购商的id
        $buyer = $this->MUser
            ->get_lists('id',
                array(
                    'type'   => C('user.normaluser.purchase.type'),
                    'status' => C('status.user.new')
                )
            );

        $buyer_ids = array_column($buyer, 'id');
        //今日新增订单数，总订单数
        //新增订单以updated_time为准，避免漏掉例如昨日创建，今日成交的订单
        $today_new_order_count = $this->MOrder->count_by(
            array('status', 'updated_time >=', 'updated_time <', 'uid'),
            array($show_condition, $start_time, $end_time, $buyer_ids)
        );
        $total_order_count = $this->MOrder->count_by(
            array('status'),
            array($show_condition)
        );

        //今日交易额，总交易额
        $valid_orders = $this->MOrder->get_lists(
            array(
                'sumprice', 'uid', 'supply_uid'
            ),
            array(
                'status' => $show_condition
            )
        );

        $today_valid_orders = $this->MOrder->get_lists(
            array(
                'sumprice', 'uid', 'supply_uid'
            ),
            array(
                'status'          => $show_condition,
                'updated_time >=' => $start_time,
                'updated_time <'  => $end_time
            )
        );

        $total_deal_amount = 0;
        $buyer_ids = array_column($valid_orders, 'uid');
        $seller_ids = array_column($valid_orders, 'supply_uid');
        $user_ids = array_merge($buyer_ids, $seller_ids);

        $order_users = $this->MUser->gets_by(
            array('id', 'status'),
            array(
                $user_ids, 1
            )
        );
        $user_ids = array_column($order_users, 'id');
        $user_map = array_combine($user_ids, $order_users);

        foreach($valid_orders as $item) {
            $buyer_id = $item['uid'];
            $buyer = empty($user_map[$buyer_id]) ? '' : $user_map[$buyer_id];
            if(empty($buyer)) {
                continue;
            }
            $seller_id = $item['supply_uid'];
            $seller = empty($user_map[$seller_id]) ? '' : $user_map[$seller_id];
            if(empty($seller)) {
                continue;
            }
            if($buyer['status'] == 1 && $seller['status'] == 1
                && $item['sumprice'] <= C('order.valid_amount.most') * 100
                && $item['sumprice'] >= C('order.valid_amount.least') * 100
            ) {
                $total_deal_amount += $item['sumprice'];
            }
        }

        $today_deal_amount = 0;

        foreach($today_valid_orders as $item) {
            $buyer_id = $item['uid'];
            $buyer = empty($user_map[$buyer_id]) ? '' : $user_map[$buyer_id];
            if(empty($buyer)) {
                continue;
            }
            $seller_id = $item['supply_uid'];
            $seller = empty($user_map[$seller_id]) ? '' : $user_map[$seller_id];
            if(empty($seller)) {
                continue;
            }
            if($buyer['status'] == 1 && $seller['status'] == 1
                && $item['sumprice'] <= C('order.valid_amount.most') * 100
                && $item['sumprice'] >= C('order.valid_amount.least') * 100
            ) {
                $today_deal_amount += $item['sumprice'];
            }
        }


        $total_deal_amount /= 100;
        $today_deal_amount /= 100;

        //今日新增商品数，总商品数
        $today_new_product_count = $this->MProduct->count_by(
            array('status', 'updated_time >=', 'updated_time <'),
            array($show_condition, $start_time, $end_time)
        );
        $total_product_count = $this->MProduct->count_by(
            array('status'),
            array($show_condition)
        );

        $res = array(
           'today_new_user'    => $today_new_user_count,
           'total_user'        => $total_user_count,
           'today_new_buyer'   => $today_new_buyer_count,
           'total_buyer'       => $total_buyer_count,
           'today_new_seller'  => $today_new_seller_count,
           'total_seller'      => $total_seller_count,
           'today_new_order'   => $today_new_order_count,
           'total_order'       => $total_order_count,
           'today_deal_amount' => $today_deal_amount,
           'total_deal_amount' => $total_deal_amount,
           'today_new_product' => $today_new_product_count,
           'total_product'     => $total_product_count
        );
        if($res) {
            $arr = array('status' => 0, 'data' => $res);
        } else {
            $arr = array('status' => -1, 'data' => array());
        }
        $this->_return_json($arr);
    }

    public function search() {
    }

}

/* End of file summary.php */
/* Location: ./application/controllers/hop/summary.php */
