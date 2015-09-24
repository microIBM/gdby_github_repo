<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MService_fee extends MY_Model {

    private $_table = 't_service_fee';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mservice_fee.php */
/* Location: :./application/models/mservice_fee.php */
