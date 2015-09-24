<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 获取用户的优惠券
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-05-18
 */
class Coupon extends MY_Controller {
    private $_user_info;
    public function __construct() {
        parent::__construct();
        $this->_user_info = $this->userauth->current(TRUE);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 用户
     */
    public function lists() {
        $response = array(
            'status' => C('status.auth.login_timeout'),
            'msg'    => '登录超时，请重新登录',
        );
        if($this->_user_info) {
            $current = strtotime(date('Y-m-d', $this->input->server('REQUEST_TIME')));
            $post['where'] = '';
            $post['where'] =array('customer_id' => $this->_user_info['id']);
            $post['status'] = !isset($this->post['status']) ? C('status.common.success') : $this->post['status'];
            $post['site_id'] = $this->_user_info['site_id'];
            $response = $this->format_query('/customer_coupon/lists', $post);
        }
        $this->_return_json($response);
    }
}

/* End of file coupon.php */
/* Location: ./application/controllers/coupon.php */
