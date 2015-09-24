<?php
class Modoo_stock_quant extends ODOO_Model {
    
    public function __construct() {
        parent::__construct();
    }

    public function get_stock_quant($where) {
        $ob_name = 'stock.quant';
        $action = 'search_read';
        $stock_quant = $this->exe_rpc_call($ob_name, $action, $where);
        return $stock_quant;
    }
    
}

/* End of file category.php */
/* Location: ./application/controllers/category.php */