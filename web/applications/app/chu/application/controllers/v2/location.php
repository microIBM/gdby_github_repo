<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 获取开放城市列表
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-4-8
 */
class Location extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function city() {
        $data = $this->format_query('/location/get_child');
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data['list']
            )
        );
    }
}

/* End of file location.php */
/* Location: ./application/controllers/location.php */
