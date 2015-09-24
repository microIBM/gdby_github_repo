<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MJpush_notice_log extends MY_Model {

    private $_table = 't_jpush_notice_log';
    public function __construct() {
        parent::__construct($this->_table);
    }
}

/* End of file mjpush_notice_log.php */
/* Location: :./application/models/mjpush_notice_log.php */
