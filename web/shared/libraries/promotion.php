<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 促销活动类库
 * @author: caiyilong@ymt360.com
 * @version: 1.0.0
 * @since: 2015-05-28
 */

class Promotion {


    /**
     * 构造函数
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-05-28
     */
    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model(array(
            'MCustomer',
            'MProduct',
            'MCategory',
        ));
    }

    /**
     * 获取符合条件的促销活动列表
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-05-28
     */
    public function match($user = array(), $products = array(), $deliver_info = array()) {

    }

    /**
     * 检查是否可以参加相关的活动
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-05-28
     */
    public function check($products = array(), $deliver_info = array(), $promotion_list = array()) {

    }
}
