<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MMarket_info extends MY_Model {
    use MemAuto;

    protected $table = 'market_info';

    public function __construct() {
        parent::__construct($this->table);
    }
}

