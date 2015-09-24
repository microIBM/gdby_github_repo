<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_customer_side_status extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MSuborder'
            )
        );
    }

    public function fix() {
        $order_id = 1;
        while($order_id < 90000) {
            $order = $this->MOrder->get_one(
                '*',
                array(
                    'id' => $order_id
                )
            );

            if(empty($order)) {
                continue;
            }

            $suborders = $this->MSuborder->get_lists(
                '*',
                array(
                    'order_id' => $order_id
                )
            );

            if(empty($suborders)) {
                continue;
            }

            $customer_side_status = 0;
            $complete_flag = TRUE;
            $closed_flag = TRUE;
            foreach($suborders as $suborder) {
                //如果有一单待审核，那说明就是待审核
                if($suborder['status'] == C('order.status.wait_confirm.code')) {
                    $customer_side_status = C('order.customer_side_status.wait_confirm.code');
                }

                if($suborder['status'] != C('order.status.success.code') //回款
                    && $suborder['status'] != C('order.status.closed.code') //关闭
                    && $suborder['status'] != C('order.status.wait_comment.code') //关闭
                    && $suborder['status'] != C('order.status.sales_return.code') //退货
                ) {
                    $complete_flag = FALSE;
                }

                if($suborder['status'] != C('order.status.closed.code')) {
                    $closed_flag = FALSE;
                }
            }

            if($complete_flag) {
                $customer_side_status = C('order.customer_side_status.success.code');
            }

            if($closed_flag) {
                $customer_side_status = C('order.customer_side_status.closed.code');
            }

            if($customer_side_status == 0) {
                $customer_side_status = C('order.customer_side_status.wait_receive.code');
            }

            $this->MOrder->update_info(
                array(
                    'customer_side_status' => $customer_side_status
                ),
                array(
                    'id' => $order_id
                )
            );
            echo $this->db->last_query();
            echo "\n";
            $order_id ++;
        }
    }
}

/* End of file fix_customer_side_status.php */
/* Location: ./application/controllers/fix_customer_side_status.php */
