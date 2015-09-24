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
     * @param status 0:不可用 1:可用
     * @description 用户
     */
    public function lists() {
        $response = array(
            'status' => C('status.auth.login_timeout'),
            'msg'    => '登录超时，请重新登录',
        );
        $page = $this->get_page();
        $post = array(
            'currentPage' => $page['page'],
            'itemsPerPage'=> $page['page_size']
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
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => 'success',
                'lists' => isset($lists['list']) ? $lists['list'] : [],
                'total' => isset($lists['total']) ? $lists['total'] : 0,
                'all_total' => isset($lists['all_nums']) ? $lists['all_nums'] : 0
            );
       }
       $this->_return_json($response);
    }

}

/* End of file coupon.php */
/* Location: ./application/controllers/coupon.php */
