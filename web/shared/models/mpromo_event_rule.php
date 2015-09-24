<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MPromo_event_rule extends MY_Model {

    private $_table = 't_promo_event_rule';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mpromo_event_rule.php */
/* Location: :./application/models/mpromo_event_rule.php */
