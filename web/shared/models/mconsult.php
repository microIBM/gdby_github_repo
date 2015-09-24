<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 咨询单操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-05-22
 */
class MConsult extends MY_Model {
    use MemAuto;

    private $table = 't_consult';

    public function __construct() {
        parent::__construct($this->table);
    }


}

/* End of file mconsult.php */
/* Location: :./application/models/mconsult.php */
