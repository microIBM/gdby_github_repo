<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MAnti_product_sku extends MY_Model {

    private $_table = 't_anti_product_sku';

    public function __construct() {
        parent::__construct($this->_table);
        $this->db = $this->load->database('spider', TRUE);
    }

    public function replace_into($data_arr){
        $replace_str = '';
        //增加插入或更新时间
        $now_time = time();
        foreach($data_arr AS $val){
            $replace_str .= "('{$val['auto_id']}', '{$val['sku_number']}', '{$now_time}'),";
        }
        $replace_str = rtrim($replace_str, ',');
        if(empty($replace_str)){
            return 0;
        }
        $this->db->query("INSERT INTO {$this->_table}(`auto_id`, `sku_number`, `created_time`)VALUES{$replace_str} ON DUPLICATE KEY UPDATE `updated_time` = UNIX_TIMESTAMP(),`status` = 1");
        return $this->db->affected_rows();
    }
}

/* End of file MAnti_prod_sku */
/* Location: :./application/models/MAnti_prod_sku.php */
