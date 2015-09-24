<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 角色操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-04
 */
class MRole extends MY_Model {
    use MemAuto;

    private $table = 't_role';

    public function __construct() {
        parent::__construct($this->table);
    }

}

/* End of file mrole.php */
/* Location: :./application/models/mrole.php */
