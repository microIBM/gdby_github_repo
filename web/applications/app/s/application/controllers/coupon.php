<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 优惠券生成
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Coupon extends MY_Controller {
    public $app_sites;
    private $_rules_type;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCoupons',
                'MLocation',
                'MCoupon_rules'
            )
        );

        $this->_rules_type  = C('coupon_rule_type');
        $this->app_sites = array_values(C('app_sites'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 优惠券
     */
    public function info() {
        $info = $this->MCoupons->get_one('*', array('id' => $_POST['id']));
        if($info) {
            $new_info = array($info);
            $this->_deal_coupon_data($new_info);
            $info = $new_info[0];
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
        $total = $this->MCoupons->count($where);
        $data = $this->MCoupons->get_lists(
            '*',
            $where,
            $orderBy,
            array(),
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $this->_deal_coupon_data($data);
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
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化优惠券信息
     */
    private function _deal_coupon_data(&$data) {

        $locations = $this->MLocation->get_lists__Cache120('*', array('upid' => 0));
        $new_locations = array_combine(array_column($locations, 'id'), $locations);
        $new_sites = array_combine(array_column($this->app_sites, 'id'), $this->app_sites);
        $new_rules_type = array_combine(array_column($this->_rules_type, 'id'), $this->_rules_type);
        foreach($data as &$dv) {
            $rule_info = $this->MCoupon_rules->get_one('*', array('id' => $dv['coupon_rule_id']));
            $dv['updated_time'] = date('Y-m-d H:i:s', $dv['updated_time']);
            $dv['valid_time'] = date('Y-m-d', $dv['valid_time']);
            $dv['invalid_time'] = date('Y-m-d', $dv['invalid_time']);
            $dv['rule_type'] = $new_rules_type[$rule_info['rule_type']]['name'];
            $dv['site_cn'] = $new_sites[$dv['site_id']]['name'];
            $dv['location_cn'] = $new_locations[$dv['location_id']]['name'];
        }
    }


    public function create() {
        $rule_info = $this->MCoupon_rules->get_one('*', array('id' => $_POST['coupon_rule_id']));
        if($rule_info) {
            $id = $this->MCoupons->create($_POST);
            if($id) {
                $response = array(
                    'status' => C('tips.code.op_success'),
                    'msg' => '创建成功'
                );
            }
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '规则信息有误'
            );
        }
        $this->_return_json($response);
    }

    public function save() {
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
        $this->MCoupons->update_info($updata, $where);
        $this->_return_json(array('status' => C('tips.code.op_success'), 'msg' => '设置成功'));
    }
}

/* End of file coupon.php */
/* Location: ./application/controllers/coupon.php */
