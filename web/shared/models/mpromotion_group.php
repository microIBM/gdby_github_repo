<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPromotion_group extends MY_Model {

    private $_table = 't_promotion_group';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromotion_group.php */
/* Location: :./application/models/mpromotion_group.php */
