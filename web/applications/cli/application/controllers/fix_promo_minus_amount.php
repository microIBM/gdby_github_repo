<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_promo_minus_amount extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MCustomer'
            )
        );
    }

    /**
     *
     * @description 修复活动期间需要优惠但是没有被优惠的客户
     * 只有大厨才有活动！
     */
    public function fix_20150429() {
        $orders = $this->MOrder->get_lists(
            '*',
            array(
                'site_src'        => C('site.dachu'),
                'created_time >=' => strtotime('20150429'),
                'status !='       => 0
            )
        );

        foreach($orders as $item) {
            //if(!empty($item['minus_amount'])) {
            if($item['minus_amount'] != 0) {
                continue;
            }

            $res = $this->_get_rule_id($item['total_price']);
            $rule_id = $res['rule_id'];
            $minus_amount = $res['minus'];
            $order_id = $item['id'];

            if($rule_id == 0) {
                continue;
            }


            $update_res = $this->MOrder->update_info(
                array(
                    'minus_amount'        => $minus_amount,
                    'promo_event_rule_id' => $rule_id
                ),
                array(
                    'id' => $order_id
                )
            );
            echo "fix order {$order_id} to minus {$minus_amount}, update result {$update_res}\n";
        }

    }

    private function _get_rule_id($order_amount = 0) {
        $res = array(
            'rule_id' => 0,
            'minus'   => 0,
        );

        if($order_amount >= 49900) {
            $res['rule_id'] = 3;
            $res['minus'] = 6000;
            return $res;
        }

        if($order_amount >= 29900) {
            $res['rule_id'] = 2;
            $res['minus'] = 3000;
            return $res;
        }

        if($order_amount >= 19900) {
            $res['rule_id'] = 1;
            $res['minus'] = 1000;
            return $res;
        }

    }

}

/* End of file fix_promo_minus_amount.php */
/* Location: ./application/controllers/fix_promo_minus_amount.php */
