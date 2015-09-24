<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 品类统计数据模型
 * @author zhangxiao@dachuwang.com
 * @version 2015-08-10
 */
class MStatics_cate_day extends MY_Model {

    private $_table = 't_statics_category_day';

    public function __construct() {
        parent::__construct($this->_table);
        $this->db = $this->load->database('d_statics', TRUE);
    }
    
}