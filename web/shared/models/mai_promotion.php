<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MAi_promotion extends MY_Model {

    private $_table = 't_ai_promotion';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromotion.php */
/* Location: :./application/models/mpromotion.php */
