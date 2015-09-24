<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 优惠券规则模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MCoupon_rules extends MY_Model {
    use MemAuto;

    private $table = 't_coupon_rules';

    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mcoupon_rules.php */
/* Location: :./application/models/mcoupon_rules.php */
