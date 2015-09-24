<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 规格属性操作model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MProperty extends MY_Model {
    use MemAuto;

    protected $table = 't_category_property';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mproperty.php */
/* Location: :./application/models/mproperty.php */
