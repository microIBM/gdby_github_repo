<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 分类映射
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MCategory_map extends MY_Model {
    use MemAuto;

    private $table = 't_category_map';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mcategory.php */
/* Location: :./application/models/mcategory.php */
