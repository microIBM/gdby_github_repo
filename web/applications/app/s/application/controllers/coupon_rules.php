<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 优惠规则
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-05-15
 */
class Coupon_rules extends MY_Controller {
    private $_rules_type;
    public function __construct() {
        parent::__construct();
        $this->load->model(array('MCoupon_rules'));
        $this->_rules_type  = C('coupon_rule_type');
        $this->load->helper(array('compare_minus_amount'));
    }
    public function info() {
        $info = $this->MCoupon_rules->get_one('*', array('id' => $_POST['id']));
        if($info) {
            $data = array($info);
            $this->_deal_rules_data($data);
            $info = $data[0];
            $response = array(
                'status' => C('tips.code.op_success'),
                'info' => $info
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '无此信息'
            );
        }
        $this->_return_json($response);
    }

    public function lists() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : array('created_time' => 'DESC');
        $page = $this->get_page();
        $total = $this->MCoupon_rules->count($where);
        $data = $this->MCoupon_rules->get_lists(
            '*',
            $where,
            $orderBy,
            array(),
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $this->_deal_rules_data($data);
            $response = array(
                'status' => C('tips.code.op_success'),
                'total' => $total,
                'list' => $data
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有数据'
            );
        }
        $this->_return_json($response);
    }

    private function _deal_rules_data(&$data) {

        $new_rules_type = array_combine(array_column($this->_rules_type, 'id'), $this->_rules_type);
        foreach($data as &$dv) {
            $dv['updated_time'] = date('Y-m-d H:i:s', $dv['updated_time']);
            $dv['minus_amount'] /= 100;
            $dv['require_amount'] /= 100;
            $dv['rule_type_cn'] = $new_rules_type[$dv['rule_type']]['name'];
        }
    }

    public function create() {
        $data = array(
            'title' => $_POST['title'],
            'require_amount' => $_POST['require_amount'] * 100,
            'minus_amount' => $_POST['minus_amount'] * 100,
            'rule_type' => empty($_POST['rule_type']) ? 1 : $_POST['rule_type'],
            'status' => empty($_POST['status']) ? C('status.common.unverified') : $_POST['status'],
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME')
        );
        // 比较减免3000额度
        if($compare_info = compare_minus_amount($data['minus_amount']))  {
            $this->_return_json($compare_info);
        }
        $id = $this->MCoupon_rules->create($data);
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '规则创建失败'
        );
        if($id) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '规则创建成功'
            );
        }
        $this->_return_json($response);
    }

    public function save() {
        $this->_return_json($_POST);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 
     */
    public function set_status() {
        $updata = array(
            'status' => $_POST['status']
        );
        $where = array('id' => $_POST['id']);
        $this->MCoupon_rules->update_info($updata, $where);
        $this->_return_json(array('status' => C('tips.code.op_success'), 'msg' => '设置成功'));
    }
}

/* End of file coupon_rules.php */
/* Location: ./application/controllers/coupon_rules.php */
