<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 地理位置
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Circle extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }
    public function lists() {
        $cur = $this->userauth->current(FALSE);
        if(!$cur) {
            $this->_return_json(
                array(
                    'status' => -100,
                    'msg' => '登录超时'
                )
            );
        }
        $data = $this->format_query('circle/lists', $this->post); 
        $this->_return_json($data);
    }

}

/* End of file location.php */
/* Location: ./application/controllers/location.php */
