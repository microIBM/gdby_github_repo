<?php
class Modoo_stock extends ODOO_Model {
    
    public function __construct() {
        parent::__construct();
    }
          
    //创建出库单
    public function create_stock_picking($data) {
        log_message('debug',"enter create stock picking");
        $ob_name = 'stock.picking';
        $action = 'create';
        $move_lines=array();
        foreach ($data['products'] as $i => $product) {
            //log_message('debug',print_r($product,true));
            $one=array(0,FALSE,array(
                    'invoice_state'=>'none',
                    'picking_type_id'=>$data['picking_type_id'],
                    'product_packaging'=>FALSE,
                    'product_tmpl_id'=>$product['temp_id'],
                    'product_uos'=>FALSE,
                    'product_uos_qty'=>$product['qty'],
                    'reserved_quant_ids'=>array(),
                    'product_id'=>$product['id'],
                    'procure_method'=>'make_to_stock',
                    'location_id'=>$data['location_src_id'],
                    'location_dest_id'=>$data['location_dest_id'],
                    'product_uom_qty'=>$product['qty'],
                    'name' =>$product['name'],
                    'product_uom'=>$product['umo_id'],
                ));
            $move_lines[]=$one;
        }
        //log_message('debug',sprintf('move_lines: %s',print_r($move_lines,true)));
        $args = array( 
                    'company_id'      => C('odoo_config.company.id'),
                    'date_done'       =>FALSE,
                    'invoice_state'   =>'none',
                    'message_follower_ids'=>FALSE,
                    'message_ids'     =>FALSE,
                    'min_date'        =>FALSE,
                    'move_type'       =>'direct',
                    'origin'          =>FALSE,
                    'owner_id'        =>FALSE,
                    'pack_operation_ids'=>array(),
                    'picking_type_code'=>FALSE,
                    'priority'        =>'1',
                    'partner_id'      => $data['partner_id'], 
                    'picking_type_id' => $data['picking_type_id'],
                    'note'            => $data['note'],
                    'move_lines'=>($move_lines)
                );
        //log_message('debug',sprintf("input args:%s",print_r($args,true)));
        $id = $this->exe_rpc_call($ob_name, $action, array($args));
        if(!is_int($id)){
            log_message("error",sprintf("create stock.picking failed,ret:%s"),print_r($id,TRUE));
        }
        else{
            $ret = $this->exe_rpc_call($ob_name,'force_assign_direct',[$id]);
            log_message("debug",sprintf("force_assign_direct return %s",print_r($ret,TRUE)));   
        }
        return $id;
        
    }
    /*
     * @function    get_warehouse       获取仓库列表
     * @param       $where              查询条件
     * @author      rockefys@gmail.com  创建人
     * @createtime  2015-03-30          时间
     */
    public function get_warehouse($where) {
        $ob_name = 'stock.picking.type';
        $action = 'search_read';
        $fields = array('fields'=>array('warehouse_id', 'name', 'code'));
        $res = $this->exe_rpc_call($ob_name, $action, $where,$fields);
        $i = 0 ;
        foreach ($res as $key => $val) {
            $warehouse[$i]['id'] = $val['id'];
            $warehouse[$i++]['name'] = $val['warehouse_id'][1] . '：' . $val['name'];
        }
        return $warehouse;
    }
}

/* End of file category.php */
/* Location: ./application/controllers/category.php */
