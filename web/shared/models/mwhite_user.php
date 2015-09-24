<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 白名单操作model
 * @author: wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-17
 */
class MWhite_user extends MY_Model
{
    use MemAuto;

    private $_table = 't_white_user'; //t_white_user

    public function __construct()
    {
        parent::__construct($this->_table);
    }
}

/* End of file mwhite_user.php */
/* Location: :./shared/models/mwhite_user.php */