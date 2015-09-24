<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPromo_event extends MY_Model {

    private $_table = 't_promo_event';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromo_event.php */
/* Location: :./application/models/mpromo_event.php */
