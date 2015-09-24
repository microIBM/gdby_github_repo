<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 优惠券模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MCustomer_coupons extends MY_Model {
    use MemAuto;

    private $_table = 't_customer_coupons';

    public function __construct() {
        parent::__construct($this->_table);
    }
    public function count_by_sql($where = '') {
        if($where) {
            $where = ' where ' . $where;
        }
        $sql = "select count(*) as numrows from {$this->_table} {$where}";
        
        $query = $this->db->query($sql);

        if ($query->num_rows() == 0) {
            return 0;
        }

        $row = $query->row();
        return (int) $row->numrows;
    }

    public function get_lists_by_sql($fields = array(), $where = '', $order_by = '', $group_by = array(), $offset = 0, $pagesize = 0) {
        if(!empty($fields)) {
            if(is_array($fields)) {
                $fields = implode(',', $fields);
            }
        } else {
            $fields = '*';
        }
        if($where) {
            $where = "where $where";
        }
        if($order_by) {
            $order_by = "order by $order_by";
        }
        $group_by = "";
        $limit = '';
        if($pagesize) {
            $limit .= "limit {$offset},{$pagesize}";
        }
        $sql = "select {$fields} from {$this->_table} {$where} {$order_by} {$group_by} {$limit}";

        $query = $this->db->query($sql);

        return $query->result_array();
    }
}

/* End of file mcustomer_coupons.php */
/* Location: :./application/models/mcustomer_coupons.php */
