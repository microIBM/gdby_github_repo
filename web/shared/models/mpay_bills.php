<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPay_bills extends MY_Model {

    private $_table = 't_pay_bills';
    public function __construct() {
        parent::__construct($this->_table);
    }
    
}

/* End of file mpay_bills.php */
/* Location: :./application/models/mpay_bills.php */
