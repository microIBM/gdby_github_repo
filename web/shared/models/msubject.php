<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * subject主题 model
 * @author : liaoxianwen@ymt360.com
 * @version : 1.0.0
 * @since : 2014-12-10
 */
class MSubject extends MY_Model {
    use MemAuto;

    private $table = 't_subjects';

    public function __construct () {
        parent::__construct($this->table);
    }
}

/* End of file msku.php */
/* Location: :./application/models/msku.php */
