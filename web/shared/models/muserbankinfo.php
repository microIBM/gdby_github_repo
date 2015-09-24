<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MUserbankinfo extends MY_Model {
    use MemAuto;
    private $_table = 'user_bank_info';
    public function __construct() {
        parent::__construct($this->_table);
    }
}
