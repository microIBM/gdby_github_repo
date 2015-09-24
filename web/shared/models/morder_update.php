<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Morder_update extends MY_Model {

    private $_table = 't_order_update';
    public function __construct() {
        parent::__construct($this->_table);
    }

    /**
     * 批量replace方法,存在则更新创建时间,不存在则新插入
     * @param $data_arr
     * @return int 结果条数,注意,"INSERT ... ON DUPLICATE KEY UPDATE" 这种情况的查询，当执行了一次 INSERT 返回的值会是 1；如果是对已经存在的记录执行一次 UPDATE 将返回 2。
     */
    public function replace_into($data_arr){
        $insert_num = 0;
        $update_num = 0;
        $value = null;
        foreach ($data_arr as $value) {
            // 1. 判断数据库中是否存在该条数据; 2. 存在,更新;3. 不存在,插入
                $result = $this->db->select('*')->from($this->_table)->where('order_id', $value['order_id'])->where('`order_status`', $value['order_status'])->get()->result_array();
                if(count($result) === 0){
                    $this->db->insert($this->_table, $value);
                    $insert_num ++;
                }else{
                    $this->db->where('order_id', $value['order_id'])->where('`order_status`', $value['order_status'])->update($this->_table, $value);
                    $update_num ++;
                }
            }

            $msg['insert_counts'] = $insert_num;
            $msg['update_counts'] = $update_num;
            return $msg;
        }
}

/* End of file Morder_update */
/* Location: :./application/models/Morder_update.php */
