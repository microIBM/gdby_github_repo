<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 总统计表按天数据静态化
 * @author zhangxiao@dachuwang.com
 *
 */
class MStatics extends MY_Model {

    private $_table = 't_statics';
    private $_date_time = '';
    public function __construct() {
        parent::__construct($this->_table);
    }

    public function update_statics($data = array()) {

        if(!empty($data)) {
            if(!$this->_contain_this_data($data)) {
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
        $this_date = $this->_date_time;
        foreach ($data as $value) {
            $where['date_time']     = $this_date;
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

    private function _contain_this_data($data) {
        $count = count($data);
        $this->_date_time = $data[0]['date_time'];
        $result = $this->get_by('date_time', $this->_date_time);
        if(count($result) == $count) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

/* End of file mstatics.php */
/* Location: :./application/models/mstatics.php */