<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_order_type_config extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder_type_config'
            )
        );
    }

    public function fix() {
        $rules = array(
            array(
                //普通订单
                'id'           => 1,
                'score'        => 1,
                'status'       => 1,
                'type_name'    => '普通订单',
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            ),
            array(
                //冻品订单
                'id'           => 2,
                'score'        => 10,
                'status'       => 0,
                'type_name'    => '冻品订单',
                'category_ids' => '198',
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            ),
            array(
                //水果爆款订单
                'id'           => 3,
                'score'        => 10,
                'status'       => 1,
                'category_ids' => '492',
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            ),
            array(
                //水果订单
                'id'           => 4,
                'score'        => 10,
                'status'       => 1,
                'category_ids' => '43',
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            )
        );
        $this->MOrder_type_config->create_batch(
            $rules
        );
    }
}

/* End of file fix_order_type_config.php */
/* Location: ./application/controllers/fix_order_type_config.php */
