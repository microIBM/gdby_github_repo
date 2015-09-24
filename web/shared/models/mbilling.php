<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: maqiang@dachuwang.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MBilling extends MY_Model {
    use MemAuto;

    private $table = 't_billing';

    public function __construct() {
        parent::__construct($this->table);
    }
    
    public function get_billing_list($sql,$condition_param ) {
    	return  $this->db->query($sql, $condition_param)->result_array();
    }
    
    public function get_billing_count($sql, $condition_param) {
    	return  $this->db->query($sql, $condition_param)->result_array();
    }
}

/* End of file mbilling.php */
/* Location: :./application/models/mbilling.php */
