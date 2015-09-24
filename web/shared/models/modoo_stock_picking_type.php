<?php
class Modoo_stock_picking_type extends ODOO_Model {
    
    public function __construct() {
        parent::__construct();
    }
          
    //查询
    public function read_by_id($id) {
        return $this->exe_rpc_call('stock.picking.type','search_read',
            array(array(array('id','=',$id))));
    }
    public function read_by($type, $val) {
        return $this->exe_rpc_call('stock.picking.type', 'search_read',
            array(array(array($type, '=', $val))),
            array('fields' => array('id', 'default_location_src_id'))    
        );
    }
}
/* End of file modoo_stock_picking_type.php */
/* Location: ./web/shared/models/modoo_stock_picking_type.php*/
