<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPromotion extends MY_Model {

    private $_table = 't_promotion';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromotion.php */
/* Location: :./application/models/mpromotion.php */
