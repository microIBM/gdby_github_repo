<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: maqiang@dachuwang.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MStatics_category_sales_reports extends MY_Model {
    private $table = 't_statics_category_sales_reports';

    public function __construct() {
        parent::__construct($this->table);
        $this->db = $this->load->database('d_statics', TRUE);
    }
    
}

/* End of file mbilling.php */
/* Location: :./application/models/mbilling.php */
