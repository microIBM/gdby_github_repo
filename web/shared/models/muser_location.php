<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MUser_location extends MY_Model {

    private $_table = 't_user_location';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file muser_app_binding.php */
/* Location: :./application/models/muser_app_binding.php */
