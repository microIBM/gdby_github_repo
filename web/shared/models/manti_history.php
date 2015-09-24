<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MAnti_history extends MY_Model {

    private $_table = 't_anti_history';

    public function __construct() {
        parent::__construct($this->_table);
        $this->db = $this->load->database('spider', TRUE);
    }
}

/* End of file MAnti_history */
/* Location: :./application/models/MAnti_history.php */
