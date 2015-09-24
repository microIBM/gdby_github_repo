<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 会员折扣表
 * @author yugang@dachuwang.com
 * @since 2015-08-08
 */
class MMember_rebate extends MY_Model {
    use MemAuto;

    private $_table = 't_member_rebate';
    public function __construct () {
        parent::__construct($this->_table);
    }

}
/* End of file mmember_rebate.php */
/* Location: :./application/models/mmember_rebate.php */
