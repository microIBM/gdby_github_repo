<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MOrder_type_config extends MY_Model {

    private $_table = 't_order_type_config';
    public function __construct() {
        parent::__construct($this->_table);
    }

}

/* End of file morder_split_rules.php */
/* Location: :./application/models/morder_split_rules.php */
