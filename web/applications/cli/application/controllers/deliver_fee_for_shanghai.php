<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Deliver_fee_for_shanghai extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MDeliver_fee'
            )
        );
    }

    public function fee() {
        $deliver_fee_rule = array(
            'free_amount'  => 10000,
            'fee'          => 2000,
            'city_id'      => 993,
            'site_id'      => C('site.dachu'),
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME'),
        );
        $this->MDeliver_fee->create($deliver_fee_rule);
        echo $this->db->last_query();
    }
}

/* End of file deliver_fee_for_shanghai.php */
/* Location: ./application/controllers/deliver_fee_for_shanghai.php */
