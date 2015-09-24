<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MUser_action extends MY_Model {

    private $_table = 'user_action';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file muser_action.php */
/* Location: :./application/models/muser_action.php */
