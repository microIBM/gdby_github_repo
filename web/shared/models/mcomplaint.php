<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 投诉单操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-05-13
 */
class MComplaint extends MY_Model {
    use MemAuto;

    private $table = 't_complaint';

    public function __construct() {
        parent::__construct($this->table);
    }


}

/* End of file mcomplaint.php */
/* Location: :./application/models/mcomplaint.php */
