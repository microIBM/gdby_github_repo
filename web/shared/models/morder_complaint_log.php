<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MOrder_complaint_log extends MY_Model {
    use MemAuto;

    private $table = 'order_complaint_log';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file morder_complaint_log.php */
/* Location: :./application/models/morder_complaint_log.php */
