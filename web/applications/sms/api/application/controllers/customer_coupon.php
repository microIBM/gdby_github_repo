<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Customer_coupon extends MY_Controller {

    public function __construct() {
        parent::__construct();
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
            $this->post['where']['mobile'] = $this->post['searchVal'];
        }
        $data = $this->format_query('/customer_coupon/manage', $this->post);
        $this->_return_json($data);
    }
    public function create() {
        $this->check_validation('product', 'edit', '', FALSE);
        $post = array(
            'coupon_id' => $this->post['couponId'],
        );
        // 根据手机号来发客户
        if(!empty($this->post['mobiles'])) {
            $customer = $this->format_query('/customer/get_id_by_mobile', array('mobiles' => $this->post['mobiles']));
            if(!empty($customer)) {
                $post['customer_ids'] = array_column($customer, 'id');
            } else {
                $this->_return_json(array('status' => C('tips.code.op_failed'), 'msg' => '发送失败'));
            }
        }
//         else {
//             $this->_return_json(array('status' => C('tips.code.op_failed'), 'msg' => '暂时不能给全部用户发送优惠券'));
//         }
        $data = $this->format_query('/customer_coupon/create', $post);
        $this->_return_json($data);
    }
    public function set_status() {
        $data = $this->format_query('/customer_coupon/set_status', $this->post);
        $this->_return_json($data);
    }

}

/* End of file customer_coupon.php */
/* Location: ./application/controllers/customer_coupon.php */
