<?php

class MSms_log extends MY_Model {
    use MemAuto;
    public $table = 't_sms_log';

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * add multi
     * @param array $data
     */
    public function add_batch($data) {
        $this->db->insert_batch($this->table, $data);
        return $this->db->insert_id();
    }

    public function add($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
}
