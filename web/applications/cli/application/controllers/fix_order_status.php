<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_order_status extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MOrder_detail'
            )
        );
    }

    public function change_picked_to_checked() {
        $orders = $this->MOrder->get_lists(
            'id',
            array(
                'status' => C('order.status.picked.code')
            )
        );
        $order_ids = array_column($orders, 'id');

        foreach($order_ids as $order_id) {
            $this->MOrder->update_info(
                array(
                    'status' => C('order.status.checked.code')
                ),
                array(
                    'id' => $order_id
                )
            );
            $this->MOrder_detail->update_info(
                array(
                    'status' => C('order.status.checked.code')
                ),
                array(
                    'order_id' => $order_id
                )
            );
            echo "change picked to checked of order {$order_id}\n";
        }
        echo "done\n";

    }
}

/* End of file fix_order_status.php */
/* Location: ./application/controllers/fix_order_status.php */
