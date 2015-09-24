<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 商品快照模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-08-21
 */
class MProduct_snapshot extends MY_Model {
    use MemAuto;

    private $table = 't_product_snapshot';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mproduct_snapshot.php */
/* Location: :./application/models/mproduct_snapshot.php */
