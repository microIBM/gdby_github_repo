<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Change_deliver_fee extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MDeliver_fee'
            )
        );
    }

    public function change() {
        $this->MDeliver_fee->update_info(
            array(
                'free_amount' => 20000,
                'fee'         => 5000,
            ),
            array(
                'city_id' => 804 //北京
            )
        );
        $this->MDeliver_fee->update_info(
            array(
                'free_amount' => 15000,
                'fee'         => 3000,
            ),
            array(
                'city_id != ' => 804 //天津和上海
            )
        );
    }
}

/* End of file change_deliver_fee.php */
/* Location: ./application/controllers/change_deliver_fee.php */
