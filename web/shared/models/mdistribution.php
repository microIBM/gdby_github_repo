<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MDistribution extends MY_Model {

    private $_table = 't_distribution';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mdistribution.php */
/* Location: :./application/models/mdistribution.php */
