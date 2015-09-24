<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 异常单操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-05-09
 */
class MAbnormal_order extends MY_Model {
    use MemAuto;

    private $table = 't_abnormal_order';

    public function __construct() {
        parent::__construct($this->table);
    }


}

/* End of file mabnormal_order.php */
/* Location: :./application/models/mabnormal_order.php */
