<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Odoo_stock extends MY_Controller {
    public function __construct () {
        parent::__construct();
           $this->load->model(
               array('modoo_stock',
                    'modoo_product',
                    'modoo_stock_quant',
                    'modoo_stock_picking_type'
                    )
        );
    }
     
    public function get_product_quant() {
        $post = $_POST;
        
        //检查参数
        if(empty($post)) {
            $data = array('error_code' => '201', 'error_message' => 'parameter error', 'data' => '' );
            $this->_return_json($data);
        }
        
        //查找所有仓库
        $val = 'outgoing';
        $type = 'code';
        $stock = $this->modoo_stock_picking_type->read_by($type, $val);
        $valid_stock = array(2,72,66);
        //拼接返回信息 
        for($i = 0;$i<count($stock);$i++) {
            $args = array(array(array('default_code', 'in', $post)));
            $kwargs = array('fields' => array('qty_available', 'default_code'), 'context' => array('location' => $stock[$i]['default_location_src_id'][0]));
            $res = $this->modoo_product->get_product_info($args, $kwargs);
            if (!in_array($stock[$i]['id'],$valid_stock)) {
                continue;
            }
            foreach($res as $key => $val) { 
                //如果库存为0,则此仓库的库存不显示在返回信息中
                //if(empty($val['qty_available'])) {
                //    continue;
                //}
                $result[$val['default_code']][$stock[$i]['id']] = $val['qty_available'];
            }
            //请求wms2.0的库存数据
            $url = C('wms.url.stock_qty');
            $this->load->library('dachu_request');
            $ret = $this->dachu_request->post($url,$post);
            $ret_arr = json_decode($ret['res'],true);

            //将odoo和wms2.0的数据进行拼接
            if(! empty($ret_arr['data'])) {
                foreach($ret_arr['data'] as $key=>$val) {
                    if(! isset($result[$key])) {
                        $result[$key] = $val; 
                    } 
                        $result[$key] = $result[$key] + $val;
                }
            }
        }
        $data = array('error_code' => '0', 'error_mesage' => 'success', 'data' => $result);
        $this->_return_json($data);
    }
    
    public function create_stock_picking() {
        $one_data=$_POST;
        
        if(empty($one_data)  
            || !array_key_exists('line_name', $one_data)
            || !array_key_exists('product_list', $one_data)
            || !array_key_exists('delivery_date', $one_data)){
            $data = array('error_code' => '201', 'error_message' => 'parameter error', 'data' => '' );
            log_message('debug',"post data is :". print_r($one_data,true));
            $this->_return_json($data);
        }
        //固定赋值为大厨网
        $one_data['biz_type'] = 1;
        
        //判断是否调用wms2.0的创建出库单接口
        /* 已经不会再通过这个接口在wms 2.0上创建出库单了。 
        if(! is_numeric($one_data['picking_type_id'])) {
            $url = C('wms.url.stock_out');
            $this->load->library('dachu_request');
            $data = $this->dachu_request->post($url,$one_data);
            $this->_return_json(json_decode($data['res'],true)); 
        }
        */

        if(empty($one_data['picking_type_id'])) {
            $picking_type_id = C('odoo_config.line2stock_map.default');
        }else {
            $picking_type_id = intval($one_data['picking_type_id']);
            //wms1.0接口的仓库id
            $wms1_warehouse = array(2,65,72,78,66,79,116);
            //如果不是wms1.0的仓库，则不创建出库单
            if(!in_array($picking_type_id, $wms1_warehouse)) {
                $ret = array('error_code' => '0', 'error_message' => 'not wms 1.0 warehouse', 'data' => "" );
                $this->_return_json($ret);
            }
        }
        $delivery_time_map=array(1=>"上午",2=>"下午");
        $am_pm = 'ALL';
        if(!empty($one_data['delivery_time']) && 
            !empty($delivery_time_map[$one_data['delivery_time']])){
            $am_pm = $delivery_time_map[$one_data['delivery_time']];
        }
        log_message("debug",sprintf("create_stock_picking: incoming picking_type_id=%d",$picking_type_id));

        /*针对冻品库，重新做分拣类型的映射:
        * 先根据sku_type，取分拣类型的配置，
        * 然后根据传入的line_id来查找这个line应该使用那个分拣类型；
        * line_id是可选参数，未来可能传入
         */
        /*
        $config_picking_type = C('odoo_config.sku_type2picking_type');
        if(!empty($one_data['sku_type']) && !empty($config_picking_type[$one_data['sku_type']])){

            log_message("debug",sprintf("create_stock_picking: sku_type=%s",$one_data['sku_type']));

            $config_sku_picking_type = $config_picking_type[$one_data['sku_type']];
            log_message("debug",sprintf("create_stock_picking: config_sku_picking_type=%s",print_r($config_sku_picking_type,true)));
            if(!empty($one_data['line_id']) && !empty($config_sku_picking_type[$one_data['line_id']])){
                log_message("debug",sprintf("create_stock_picking: sku_type=%s",$one_data['sku_type']));
                $picking_type_id = intval($config_sku_picking_type[$one_data['line_id']]);
            }
            else{
                $picking_type_id = intval($config_sku_picking_type['default']);
            }
        }

         */

        log_message("debug",sprintf("create_stock_picking: finally picking_type_id=%d",$picking_type_id));

        $picking_type=$this->modoo_stock_picking_type->read_by_id($picking_type_id);
        $location_src_id = $picking_type[0]['default_location_src_id'][0];
        $location_dest_id=$picking_type[0]['default_location_dest_id'][0];

        $biz2partner_map=C('odoo_config.biz2partner_map');
        $partner_id=$biz2partner_map[$one_data['biz_type']];
        $products=array();

        //先一次批量交互odoo，查询得到sku的信息,优化性能
        $prod_codes = array_column($one_data['product_list'],'product_code');
        $tmp = $this->modoo_product->batch_code_to_id($prod_codes);
        $ids=array();
        foreach ($tmp as $i => $p) {
            $ids[$p['default_code']]=$p;
        }
        log_message("debug",sprintf("product ids: %s",print_r($ids,true)));
        $lines = 0;
        $total = 0;
        foreach ($one_data['product_list'] as $i => $product) {
            if(!isset($ids[$product['product_code']])){
                $data = array('error_code' => '202', 'error_message' => 'unknown product code', 'data' => $product['product_code'] );
                $this->_return_json($data);
            }
            //log_message("debug",print_r($p,true));
            $p = $ids[$product['product_code']];
            $products[]=array('id'=>$p['id'],'qty'=>$product['qty'],
                'name'=>$p['partner_ref'],'umo_id'=>$p['uom_id'][0],
                'temp_id'=>$p['product_tmpl_id'][0]);
            $lines += 1;
            $total += $product['qty'];
        }

        $data=array('partner_id'=>$partner_id,
            'picking_type_id'=>$picking_type_id,
            'location_src_id'=>$location_src_id,
            'location_dest_id'=>$location_dest_id,
            'note' => $one_data['line_name'] . "(共${lines}种,${total}件)" . "_" . $am_pm . "_" . $one_data['delivery_date'],
            'products'=>$products
            );
        $id=$this->modoo_stock->create_stock_picking($data);
        if(!is_int($id)){
            $ret = array('error_code' => '203', 'error_message' => 'create stock picking inner error', 'data' => print_r($id,true) );
            $this->_return_json($ret);
        }
        $ret = array('error_code' => '0', 'error_message' => 'success', 'data' => "$id" );
        $this->_return_json($ret);

    }
    /*
     * @function    get_warehouse       获取仓库列表
     * @param       picking_type        仓库类型
     * @author      rockefys@gmail.com  创建人
     * @createtime  2015-03-30          时间
     */
    public function get_warehouse() {
        
        $stock_picking_type = I('post.picking_type', 'outgoing');
        $valid_id = array(2,72,66);
        $map = array(array(array('id',"in",$valid_id),array("code", "=", $stock_picking_type)));
        $warehouse = $this->modoo_stock->get_warehouse($map);
        //获取wms2.0的仓库数据
        $url = C('wms.url.warehouse');
        $this->load->library('dachu_request');
        $data = $this->dachu_request->post($url);
        //和odoo数据进行拼接
        foreach(json_decode($data['res'],true)['data'] as $val) {
            $warehouse[] = $val; 
        }
        E(0, 'sucess.', $warehouse);//总是返回结果
    }

}

/* End of file odoo_stock.php */
/* Location: ./web/applications/api/application/controllers/odoo_stock.php */
