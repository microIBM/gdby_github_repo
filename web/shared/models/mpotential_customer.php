<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 潜在客户操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-24
 */
class MPotential_customer extends MY_Model {
    use MemAuto;

    private $table = 't_potential_customer';
    protected $_salt  = NULL;

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 以函数返回值形式返回用户信息(不包含密码)
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function get_user_info($query) {
        $user_info = $this->get_one('*', $query);
        return $user_info;
    }

    /**
     * 检测手机号是否唯一
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     * @return 检测结果
     */
    public function check_mobile_unique($mobile) {
        if ($this->get_one('*', array('mobile' => $mobile, 'status !=' => C('status.common.del')))) {
            return FALSE;
        }
        return TRUE;
    }
}

/* End of file mpotential_customer.php */
/* Location: :./shared/models/mpotential_customer.php */
