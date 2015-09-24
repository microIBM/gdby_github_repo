<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MAnti_products extends MY_Model {

    private $_table = 't_anti_products';
    public function __construct() {
        parent::__construct($this->_table);
    }

}

/* End of file manti_products.php */
/* Location: :./application/models/manti_products.php */
