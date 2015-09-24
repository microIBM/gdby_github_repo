<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MSuborder extends MY_Model {

    private $_table = 't_suborder';
    public function __construct() {
        parent::__construct($this->_table);
    }

    /**
     * 批量更新订单状态
     * @param unknown $order_ids 订单ID list
     * @param unknown $status 要更新的状态
     * @return number
     * @author yuanxiaolin@dachuwang.com
     */
    public function update_batch_orders_status($order_ids,$status){

        $update_data = array();
        if(is_array($order_ids) && !empty($order_ids)){
            foreach ($order_ids as $key => $value){

                $update_data[$key]['id'] = $value;
                $update_data[$key]['status'] = $status;
                $update_data[$key]['updated_time'] = time();
            }
            $this->db->trans_begin();

            $this->db->update_batch($this->_table,$update_data,'id');

            if ($this->db->trans_status() === FALSE)
            {
                $this->db->trans_rollback();
                return 0;
            }
            else
            {
                $this->db->trans_commit();
                return count($order_ids);
            }
        }
    }

    /**
     * 批量更新子单的信息
     * @author zhangxiao@dachuwang.com
     */
    public function update_batch_orders($suborder_ids, $pay_status) {
    
        if(is_array($suborder_ids) && !empty($suborder_ids)){
            foreach ($suborder_ids as $index => $suborder_id){
                $update_data[$index]['id'] = $suborder_id;
                $update_data[$index]['pay_status'] = $pay_status;
                $update_data[$index]['updated_time'] = time();
            }
            $this->db->trans_begin();
            $this->db->update_batch($this->_table,$update_data,'id');
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return 0;
            } else {
                $this->db->trans_commit();
                return count($suborder_ids);
            }
        }
    }
    
    /**
     * 返回母单id对应的所有子弹ids
     * @author zhangxiao@dachuwang.com
     */
    public function get_suborder_ids_by_orderid ($order_id) {
        $fields = ['id'];
        $where  = ['order_id' => $order_id];
        $results = $this->get_lists($fields, $where);
        return array_column($results, 'id');
    }

    /**
     * 返回子单对应的所有母单
     * @param $suborders
     */
    public function get_order_ids_by_suborder($suborders){
        $fields = ['order_id', 'id as suborder_id'];
        $where  = ['in' => ['id' => $suborders]];
        return $this->get_lists($fields, $where);
    }
}

/* End of file msuborder.php */
/* Location: :./application/models/msuborder.php */
