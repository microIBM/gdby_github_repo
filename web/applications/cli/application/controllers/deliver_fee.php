<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Deliver_fee extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MDeliver_fee'
            )
        );
    }

    public function update_0731() {
        $res = $this->MDeliver_fee->update_info(
            array(
                'free_amount' => 20000,
                'fee' => 5000
            ),
            array(
                'city_id' => 993
            )
        );
        echo $res;
    }

    public function update_0809() {
        $res = $this->MDeliver_fee->update_info(
            array(
                'free_amount' => 20000,
                'fee' => 5000
            ),
            array(
                'city_id' => 1206
            )
        );
        echo $res;
    }
}

/* End of file deliver_fee.php */
/* Location: ./application/controllers/deliver_fee.php */
