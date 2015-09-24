<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MDiscount_rule extends MY_Model {

    private $_table = 't_discount_rule';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromotion.php */
/* Location: :./application/models/mpromotion.php */
