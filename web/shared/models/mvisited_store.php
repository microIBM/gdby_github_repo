<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MVisited_store extends MY_Model {
    use MemAuto;

    protected $table = 'visited_store';

    public function __construct() {
        parent::__construct($this->table);
    }
}
