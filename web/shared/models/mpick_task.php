<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPick_task extends MY_Model {

    private $_table = 't_pick_task';
    public function __construct() {
        parent::__construct($this->_table);
    }
    
    
}

/* End of file mpick_task.php */
/* Location: :./application/models/mpick_task.php */
