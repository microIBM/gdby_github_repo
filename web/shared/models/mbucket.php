<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MBucket extends MY_Model {
    use MemAuto;

    private $table = 't_product_buket';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mproduct.php */
/* Location: :./application/models/mproduct.php */
