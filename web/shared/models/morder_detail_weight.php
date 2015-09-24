<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MOrder_detail_weight extends MY_Model {

    private $_table = 't_order_detail_weight';
    public function __construct() {
        parent::__construct($this->_table);
    }
    
}

/* End of file mpay_bills.php */
/* Location: :./application/models/mpay_bills.php */
