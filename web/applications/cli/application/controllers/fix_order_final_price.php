<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_order_final_price extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder'
            )
        );
    }

    public function fix() {
        $no_price_orders = $this->MOrder->get_lists(
            'id, total_price, deliver_fee, final_price, minus_amount',
            array(
                'final_price' => 0
            )
        );

        foreach($no_price_orders as $order) {
            $final_price = $order['total_price'] + $order['deliver_fee'] - $order['minus_amount'];
            $order_id = $order['id'];
            $this->db->query(
                "update t_order set final_price = {$final_price} where id = {$order_id}"
            );
            echo $this->db->last_query() . "\n";
        }
    }
}

/* End of file fix_order_final_price.php */
/* Location: ./application/controllers/fix_order_final_price.php */
