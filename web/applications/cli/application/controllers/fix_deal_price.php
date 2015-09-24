<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_deal_price extends MY_Controller {

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
        $order_id = 0;
        while($order_id < 70000) {
            $order_id += 1;
            $order = $this->MOrder->get_one(
                'deal_price',
                array(
                    'id' => $order_id
                )
            );
            $deal_price = $order['deal_price'];
            if(!empty($deal_price)) {
                continue;
            }
            $complete_flag = TRUE;
            $suborders = $this->MSuborder->get_lists(
                'deal_price, status',
                array(
                    'order_id' => $order_id
                )
            );
            $complete_arr = array(
                C('order.status.success.code'),
                C('order.status.closed.code'),
                C('order.status.sales_return.code'),
            );
            $deal_price_total = 0;

            foreach($suborders as $suborder) {
            //    echo $suborder['deal_price'] . "\t";
                $deal_price_total += $suborder['deal_price'];
                if(!in_array($suborder['status'], $complete_arr)) {
                    $complete_flag = FALSE;
                }
            }
            //echo "\n";

            if($complete_flag) {
                $this->MOrder->update_info(
                    array(
                        'deal_price' => $deal_price_total
                    ),
                    array(
                        'id' => $order_id
                    )
                );

                print_r($this->db->last_query());
                echo "\r\n";
            }
        }
    }
}

/* End of file fix_deal_price.php */
/* Location: ./application/controllers/fix_deal_price.php */
