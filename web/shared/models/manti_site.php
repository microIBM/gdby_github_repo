<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 友商爬虫数据模型
 * @author zhangxiao@dachuwang.com
 * @version 2015-08-19
 */
class MAnti_site extends MY_Model {

    private $_table = 't_anti_site';

    public function __construct() {
        parent::__construct($this->_table);
        $this->db = $this->load->database('spider', TRUE);
    }
}

/* End of file MAnti_site.php */
/* Location: :./application/models/MAnti_site.php */