<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 优惠券模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MCoupons extends MY_Model {
    use MemAuto;

    private $table = 't_coupons';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mcoupon.php */
/* Location: :./application/models/mcoupon.php */
