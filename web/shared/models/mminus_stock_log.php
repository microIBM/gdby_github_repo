<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MMinus_stock_log extends MY_Model {

    private $_table = 't_minus_stock_log';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mminus_stock_log.php */
/* Location: :./application/models/mminus_stock_log.php */
