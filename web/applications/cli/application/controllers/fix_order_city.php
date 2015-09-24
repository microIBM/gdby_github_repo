<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_order_city extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MOrder',
                'MOrder_detail'
            )
        );
    }

    public function fix() {
        $customers = $this->MCustomer->get_lists(
            'id, province_id',
            array(
            )
        );
        foreach($customers as $customer) {
            $customer_id = $customer['id'];
            $city_id = $customer['province_id'];
            $orders = $this->MOrder->get_lists(
                'id, user_id',
                array(
                    'user_id' => $customer_id
                )
            );
            foreach($orders as $order) {
                $order_id = $order['id'];
                $order_update_res = $this->MOrder->update_info(
                    array(
                        'city_id' => $city_id
                    ),
                    array(
                        'id' => $order_id
                    )
                );
                $detail_update_res = $this->MOrder_detail->update_info(
                    array(
                        'city_id' => $city_id
                    ),
                    array(
                        'order_id' => $order_id
                    )
                );
                echo "{$order_id} : {$order_update_res}, {$detail_update_res}\n";
            }
        }
    }
}

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
