<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 规格选项操作model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MOptions extends MY_Model {
    use MemAuto;

    protected $table = 't_property_options';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file moptions.php */
/* Location: :./application/models/moptions.php */
