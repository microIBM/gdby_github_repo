<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 订单流水model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MPay extends MY_Model {
    use MemAuto;

    private $table = 'pay';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mpay.php */
/* Location: :./application/models/mpay.php */
