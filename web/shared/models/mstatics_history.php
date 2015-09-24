<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 总统计表历史数据静态化
 * @author zhangxiao@dachuwang.com
 *
 */
class MStatics_history extends MY_Model {

    private $_table = 't_statics_history';

    public function __construct() {
        parent::__construct($this->_table);
    }

    public function update_statics($data = array()) {
        if(!empty($data)) {
            if($this->db->count_all_results($this->_table) == 0) {
                $response = $this->_insert_data($data);
            }else {
                $response = $this->_update_data($data);
            }
            return $response;
        } else {
            return FALSE;
        }
    }

    private function _insert_data($data){
        $now_time = $this->input->server("REQUEST_TIME");
        foreach ($data as &$value) {
            $value['created_time'] = $now_time;
            $value['updated_time'] = $now_time;
        }
        $this->db->insert_batch($this->_table, $data);
        $affect_rows = $this->db->affected_rows();
        if ($affect_rows) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    private function _update_data($data) {
        $this->db->trans_start();
        foreach ($data as $value) {
            $where['city_id']       = $value['city_id'];
            $where['customer_type'] = $value['customer_type'];
            $this->update_info($value, $where);
        }
        $this->db->trans_complete();
        if($this->db->trans_status() === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}

/* End of file mstatics_history.php */
/* Location: :./application/models/mstatics_history.php */