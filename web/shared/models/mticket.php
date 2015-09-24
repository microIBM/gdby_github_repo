<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mticket extends MY_Model {

    private $_table = 't_ticket';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mticket.php */
/* Location: :./application/models/mticket.php */
