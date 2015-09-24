<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * description
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Coupon extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MCategory'));
        $this->load->library(
            array(
                'form_validation',
            )
        );
    }

    public function info() {
        $coupon_info = $this->format_query('/coupon/info', $this->post);
        $coupon_info = $this->_fill_visiable($coupon_info);
        $this->_return_json($coupon_info);
    }

    private function _fill_visiable($coupon_info) {
        $visiables = C('visiable');
        $coupon_info['info']['visiables'] = array($visiables[1], $visiables[0], $visiables[2]);
        return $coupon_info;
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
        $coupon_list = $this->format_query('/coupon/lists', $this->post);
        $this->_return_json($coupon_list);
    }

    public function create() {
        $this->form_validation->set_rules('title', '发券活动名称', 'trim|required');
        $this->form_validation->set_rules('ruleId', '规则id', 'trim|required');
        $this->form_validation->set_rules('siteId', '站点id', 'trim|required');
        $this->form_validation->set_rules('visiable', '可见性id', 'trim|required');
        $this->form_validation->set_rules('invalidTime', '失效时间', 'trim|required');
        $this->form_validation->set_rules('validTime', '生效时间', 'trim|required');
        $this->form_validation->set_rules('couponTriggerId', '触发类型id', 'trim|required');
        $this->validate_form();
        $category_ids_str = empty($this->post['categories']) ? '' : implode(',', $this->post['categories']);
        $product_ids_str  = empty($this->post['products']) ? '' : implode(',', $this->post['products']);
        $post = array(
            'title' => $this->post['title'],
            'coupon_rule_id' => $this->post['ruleId'],
            'category_ids' => $category_ids_str,
            'product_ids' => $product_ids_str,
            'site_id' => $this->post['siteId'],
            'location_id' => $this->post['locationId'],
            'visiable' => $this->post['visiable'],
            'coupon_nums' => $this->post['couponNums'],
            'line_ids' => empty($this->post['lineIds']) ? 0 : $this->post['lineIds'],
            'coupon_type' => $this->post['couponTriggerId'],
            'coupon_description' => empty($this->post['description']) ? '' : $this->post['description'],
            'valid_time' => $this->post['validTime'],
            'invalid_time' => $this->post['invalidTime'],
            'status' => C('status.common.unverified'),
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME'),
        );
        $data = $this->format_query('/coupon/create', $post);
        $this->_return_json($data);
    }
    public function set_status() {
        $data = $this->format_query('/coupon/set_status', $this->post);
        $this->_return_json($data);
    }
    public function get_category_info() {
        if (empty($_POST['searchVal'])) {
            return array();
        }
        $location_id = empty($_POST['locationId']) ? 0 : $_POST['locationId'];
        $category_info = $this->MCategory->get_lists(
            'id,name',
            array(
                'or_like' => array('name' => $_POST['searchVal']),
                'like' => array('id' => $_POST['searchVal']),
            )
        );
        $this->_return_json($category_info);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 添加的选项
     */
    public function input_options() {
        $data['couponTriggers'] = C('coupon_things_type.trigger');
        $data['couponObjects'] = C('coupon_things_type.objects');
        // 城市列表
        $sites = C('app_sites');
        foreach($sites as $sv) {
            $data['sites'][] = $sv;
        }
        if(!empty($this->post['id'])) {
            $info = $this->format_query('/coupon_rules/info', array('id' => $this->post['id']));
            if($info) {
                $rule_type_cn = $info['info']['rule_type_cn'];
                if($info['info']['rule_type'] == 1) {
                    $data['ruleInfo'] = '【' . $rule_type_cn . '】满' . $info['info']['require_amount'] . '减' . $info['info']['minus_amount'];
                } else {
                    $data['ruleInfo'] = '【' . $rule_type_cn . '】减免' . $info['info']['minus_amount'];
                }
            }
            $data['rule'] = $info;
        }
        $this->_set_location($data);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }
    private function _get_type_cn($rule_type) {
       $coupon_rules = C('coupon_rule_type');
       $rule_type_name = '';
       foreach($coupon_rules as $v) {
            if($v['id'] == $rule_type) {
                $rule_type_name = $v['name'];
            }
       }
       return $rule_type_name;
    }

    private function _set_location(&$data) {
        $location = $this->format_query('/location/get_child');
        $lines = $this->format_query('/line/get_all');
        $new_lines = array();
        foreach($lines['list'] as $v) {
            $new_lines[$v['location_id']][] = $v;
        }
        $data['visiable_options'] = C('visiable');
        $data['location'] = $location['list'];
        $data['line_options'] = $new_lines;
    }

}

/* End of file coupon.php */
/* Location: ./application/controllers/coupon.php */
