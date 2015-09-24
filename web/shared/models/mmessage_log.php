<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mmessage_log extends MY_Model {

    private $_table = 't_message_log';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mmessage_log.php */
/* Location: :./application/models/mmessage_log.php */
