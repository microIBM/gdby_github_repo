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
            $post['where'] = '';
            $post['where'] =array('customer_id' => $this->_user_info['id']);
            $post['site_id'] = $this->_user_info['site_id'];
            // 待收货状态特俗处理，包括三种状态
            if(isset($this->post['status'])) {
                $post['status'] = $this->post['status'];
            }
            $lists = $this->format_query('/customer_coupon/lists', $post);
            $res_lists = array();
            $res_total = 0;
            $res_all_total = 0;
            if(!empty($lists) && isset($lists['status']) && $lists['status'] == 0){
                $res_lists = !empty($lists['list']) ? $lists['list'] : array();
                $res_total = !empty($lists['total']) ? $lists['total'] : 0;
                $res_all_total = !empty($lists['all_nums']) ? $lists['all_nums'] : 0;
            }
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => 'success',
                'lists' => $res_lists,
                'total' => $res_total,
                'all_total' => $res_all_total,
            );
            if(!empty($response['lists'])) {
                $required_fields = parent::$_app_required_fields['coupon']['lists'];
                parent::_get_required_fields($response, 'lists', $required_fields);
            }
        }
        $this->_return_json($response);
    }
}

/* End of file coupon.php */
/* Location: ./application/controllers/coupon.php */
