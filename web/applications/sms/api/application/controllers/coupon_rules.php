<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * description
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Coupon_rules extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(
            array(
                'form_validation',
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建成功
     */
    public function create() {
        $this->form_validation->set_rules('title', '规则名称', 'trim|required');
        $this->form_validation->set_rules('ruleType', '规则类型', 'trim|required');
        $this->form_validation->set_rules('requireAmount', '最低要求多少元', 'trim|required');
        $this->form_validation->set_rules('minusAmount', '减多少元', 'trim|required');
        $this->validate_form();
        $post = array(
            'title' => $this->post['title'],
            'rule_type' => $this->post['ruleType'],
            'require_amount' => $this->post['requireAmount'],
            'minus_amount' => $this->post['minusAmount'],
            'status' => C('status.common.unverified'),
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME')
        );
        $data = $this->format_query('/coupon_rules/create', $post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 列表查询
     */
    public function lists() {
        if($this->post['status'] != 'all') {
            $this->post['where']['status'] = $this->post['status'];
        }
        if(!empty($this->post['searchVal'])) {
            $this->post['where']['like'] = array('title' => $this->post['searchVal']);
        }
        $data = $this->format_query('/coupon_rules/lists', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 状态启用
     */
    public function set_status() {
        $data = $this->format_query('/coupon_rules/set_status', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 规则input 的选项
     */
    public function input_options() {
        $data['rules_type']  = C('coupon_rule_type');
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }

}

/* End of file coupon_rules.php */
/* Location: ./application/controllers/coupon_rules.php */
