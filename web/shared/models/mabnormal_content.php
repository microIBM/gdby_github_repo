<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 异常单详情操作model
 * @author yugang@dachuwang.com
 * @since 2015-05-21
 */
class MAbnormal_content extends MY_Model {

    private $_table = 't_abnormal_content';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mabnormal_content.php */
/* Location: :./application/models/mabnormal_content.php */
