<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MWarehouse extends MY_Model {

    private $_table = 't_warehouse';

    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mwarehouse.php */
/* Location: :./application/models/mwarehouse.php */
