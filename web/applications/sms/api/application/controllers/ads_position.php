<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 广告位接口
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-4-24
 */
class Ads_position extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function lists() {
        if($this->post['status'] != 'all') {
            $this->post['where']['status'] = $this->post['status'];
        }
        if(!empty($this->post['searchVal'])) {
            $this->post['where']['like'] = array('title' => $this->post['searchVal']);
        }
        $data = $this->format_query('/ads_position/lists', $this->post);
        $this->_return_json($data);
    }

    public function save() {
        if(empty($this->post['id'])) {
            $data = $this->format_query('/ads_position/create', $this->post);
        } else {
            $data = $this->format_query('/ads_position/save', $this->post);
        }
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置状态
     */
    public function set_status() {
        $data = $this->format_query('/ads_position/set_status', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取选项
     */
    public function input_options() {

        $options = C('ads.options.position');
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $options
            )
        );
    }
}

/* End of file ads.php */
/* Location: ./application/controllers/ads.php */
