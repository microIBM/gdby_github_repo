<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 地理位置的基础服务
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-4-7
 */
class Location extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MLocation'));
    }
    // 获取child
    public function get_child() {
        $upid = isset($_POST['upid']) ? intval($_POST['upid']) : 0;
        $list = $this->MLocation->get_lists__Cache3600('*', array('upid' => $upid, 'status' => C('status.common.success')));
        $data = array(
            'status' => C('tips.code.op_success'),
            'list' => $list
        );
        $this->_return_json($data);
    }

    public function info() {
        $info = $this->MLocation->get_one("*", $_POST['where']);
        $this->_return_json(
            array(
                'info' => $info,
                'status' => C('tips.code.op_success')
            )
        );
    }
}

/* End of file location.php */
/* Location: ./application/controllers/location.php */
