<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 专题接口
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-5-12
 */
class Recommend extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function lists() {
        if($this->post['status'] != 'all') {
            $this->post['status'] = $this->post['status'];
        } else {
            unset($this->post['status']);
        }
        if(!empty($this->post['searchVal'])) {
            $this->post['title'] = $this->post['searchVal'];
            unset($this->post['searchVal']);
        }
        $data = $this->format_query('/recommend/manage', $this->post);
        $this->_return_json($data);
    }

    public function save() {
        if(isset($this->post['id'])) {
            $this->post['customer_type'] = empty($this->post['customerType']) ? C('customer.type.normal.value') : $this->post['customerType'];
            $data = $this->format_query('/recommend/save', $this->post);
        } else {
            $this->post['status'] = C('status.common.unverified');
            $this->post['customer_type'] = empty($this->post['customerType']) ? C('customer.type.normal.value') : $this->post['customerType'];
            $data = $this->format_query('/recommend/create', $this->post);
        }
        $this->_return_json($data);
    }

    public function edit() {
        $data = $this->format_query('/recommend/info', $this->post);
        $this->_return_json($data);
    }

    public function set_status() {
        if(!empty($_POST['id'])) {
            $this->post['where'] = array('id' => $_POST['id']);
            $data = $this->format_query('/recommend/set_status', $this->post);
        } else {
            $data = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数错误'
            );
        }
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 添加的选项
     */
    public function input_options() {
        // 城市列表
        $locations = $this->format_query('/location/get_child');
        $data['locations'] = $locations['list'];
        $data['sites'] = array_values(C('app_sites'));
        $data['customer_type_options'] = array_values(C('customer.type'));
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
}

/* End of file recommend.php */
/* Location: ./application/controllers/recommend.php */
