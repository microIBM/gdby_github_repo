<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 投诉详情操作model
 * @author yugang@dachuwang.com
 * @since 2015-05-14
 */
class MComplaint_content extends MY_Model {

    private $_table = 't_complaint_content';
    public function __construct() {
        parent::__construct($this->_table);
    }

}

/* End of file mcomplaint_detail.php */
/* Location: :./application/models/mcomplaint_detail.php */
