<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 友商爬虫信息类
 * @author: zhangxiao@dachuwang.com
 * @version: 1.0.0
 * @since: 15-8-19
 */
class Anti_site extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MAnti_site'
            )
        );
    }

    public function get_info() {
        $result = $this->MAnti_site->get_lists(array(
            'site_id',
            'name',
        ));
        if($result) {
            $result = array_column($result, 'name', 'site_id');
        } else {
            $result = array();
        }
        return $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg'    => '请求成功',
            'data'   => $result
        ));
    }

}

/* End of file anti_site.php */
/* Location: ./application/controllers/anti_site.php */
