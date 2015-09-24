<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MWave extends MY_Model {

    private $_table = 't_wave';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mwave.php */
/* Location: :./application/models/mwave.php */
