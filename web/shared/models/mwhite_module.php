<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 白名单模块操作model
 * @author: wangzejun@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-07-17
 */
class MWhite_module extends MY_Model
{
    use MemAuto;

    private $_table = 't_white_module';

    public function __construct()
    {
        parent::__construct($this->_table);
    }
}

/* End of file mwhite_user.php */
/* Location: :./shared/models/mwhite_module.php */