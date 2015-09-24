<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 推荐model
 * @author : liaoxianwen@ymt360.com
 * @version : 1.0.0
 * @since : 2015-6-1
 */
class MRecommend extends MY_Model {
    use MemAuto;

    private $table = 't_recommend';

    public function __construct () {
        parent::__construct($this->table);
    }
}

/* End of file mrecommend.php */
/* Location: :./application/models/mrecommend.php */
