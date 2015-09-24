<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: maqiang@dachuwang.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MStatics_core_measure extends MY_Model {
    private $table = 't_statics_core_measure';

    public function __construct() {
        parent::__construct($this->table);
        $this->db = $this->load->database('d_statics', TRUE);
    }
    
}

/* End of file mbilling.php */
/* Location: :./application/models/mbilling.php */
