<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 关注商品
 * @author: longlijian@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-06
 */
class MFollow_with_interest extends MY_Model {
    private $table = 't_follow_with_interest';
    public function __construct() {
        parent::__construct($this->table);
    }
}

/* End of file mproduct.php */
/* Location: :./application/models/mproduct.php */
