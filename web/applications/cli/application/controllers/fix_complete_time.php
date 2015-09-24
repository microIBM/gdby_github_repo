<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_complete_time extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function fix() {
        $idx = 0;
        while(TRUE) {
            $res = $this->db->query(
                "select * from t_workflow_log where edit_type = 1 and operate_type = 1 and id >= {$idx} limit 1"
            )->result_array();
            //print_r($this->db->last_query());
            if(empty($res)) {
                break;
            }
            $res = $res[0];
            print_r($res);
            $idx = $res['id'] + 1;
            //print_r($idx);
            $complete_time = $res['created_time'];
            $order_id = $res['obj_id'];

            $this->db->query(
                "update t_order set complete_time = {$complete_time} where id = {$order_id}"
            );
            $this->db->query(
                "update t_suborder set complete_time = {$complete_time} where order_id = {$order_id}"
            );
            $this->db->query(
                "update t_order_detail set complete_time = {$complete_time} where order_id = {$order_id}"
            );
            print_r($this->db->last_query());

        }
    }
}

/* End of file fix_complete_time.php */
/* Location: ./application/controllers/fix_complete_time.php */
