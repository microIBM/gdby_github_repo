<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MDeliver_fee extends MY_Model {

    private $_table = 't_deliver_fee';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mdeliver_fee.php */
/* Location: :./application/models/mdeliver_fee.php */
