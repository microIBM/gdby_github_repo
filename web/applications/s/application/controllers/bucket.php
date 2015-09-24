<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 获取图片信息
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-04-16
 */
class Bucket extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MBucket'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 根据id来获取数据
     */
    public function lists() {
        $where = isset($_POST['where']) ? $_POST['where'] : array();
        $lists = $this->MBucket->get_lists(
            '*',
            $where
        );
        $this->_return_json(array('status' => C('tips.code.op_success'), 'list' => $lists));
    }
}

/* End of file bucket.php */
/* Location: ./application/controllers/bucket.php */
