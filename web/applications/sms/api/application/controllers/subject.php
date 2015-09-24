<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 专题接口
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-5-12
 */
class Subject extends MY_Controller {

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
        $data = $this->format_query('/subject/manage', $this->post);
        $this->_return_json($data);
    }

    public function save() {
        if(isset($this->post['id'])) {
            $data = $this->format_query('/subject/save', $this->post);
        } else {
            $this->post['status'] = C('status.common.unverified');
            $data = $this->format_query('/subject/create', $this->post);
        }
        $this->_return_json($data);
    }

    public function edit() {
        $data = $this->format_query('/subject/info', $this->post);
        $this->_return_json($data);
    }

    public function set_status() {
        if(!empty($_POST['id'])) {
            $this->post['where'] = array('id' => $_POST['id']);
            $data = $this->format_query('/subject/set_status', $this->post);
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
        $data['subject_type'] = C('subject_type');
        // 城市列表
        $locations = $this->format_query('/location/get_child');
        $data['locations'] = $locations['list'];
        $sites = C('app_sites');
        foreach($sites as $sv) {
            $data['sites'][] = $sv;
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
}

/* End of file subject.php */
/* Location: ./application/controllers/subject.php */
