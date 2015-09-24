<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MProduct_price extends MY_Model {
    use MemAuto;

    private $_table = 't_product_price';

    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mproduct_price.php */
/* Location: :./application/models/mproduct_price.php */
