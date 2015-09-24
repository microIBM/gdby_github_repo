<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_virtual_stock extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MLine',
                'MStock'
            )
        );
    }

    public function set_stock($location_id) {
        $products = $this->MProduct->get_lists('id,storage,sku_number,location_id', array('is_active' => C('status.product.up'), 'storage !=' => -1, 'location_id' => $location_id, 'customer_type' => 1, 'status' => C('status.product.up')));
        $line_warehouses = $this->MLine->get_lists('warehouse_id', array('location_id' => $location_id));
        $warehouse_ids = array_unique(array_column($line_warehouses, 'warehouse_id'));
        $stocks = $this->MStock->get_lists('*', array('in' => array('warehouse_id' => $warehouse_ids)));
        $insert_data = [];
        $update_data = [];

        $new_stocks = [];
        foreach($stocks as $stock) {
            $key = $stock['warehouse_id'] . '_' . $stock['sku_number'];
            $new_stocks[$key] = $stock;
        }
        foreach($products as $product) {
            foreach($warehouse_ids as $warehouse_id ) {
                $stock_key = $warehouse_id . '_' . $product['sku_number'];
                // 若在库存里已经设置了的，那么更新下
                if(isset($new_stocks[$stock_key])) {
                    $stock_update_info = $new_stocks[$stock_key];
                    $stock_update_info['virtual_stock'] = $product['storage'];
                    // 走更新逻辑
                    $update_data[] = $stock_update_info;
                } else {
                    // 走添加逻辑
                    $insert_data[] = [
                        'warehouse_id' => $warehouse_id,
                        'sku_number' => $product['sku_number'],
                        'in_stock' => 0,
                        'virtual_stock' => $product['storage'],
                        'stock_locked' => 0,
                        'exceed_limit' => 0,
                        'created_time' => $this->input->server('REQUEST_TIME'),
                        'updated_time' => $this->input->server('REQUEST_TIME'),
                        'status' => 1
                    ];
                }
            }
        }
        if($insert_data) {
            echo '插入数据条数：' . count($insert_data) . "\n";
            $this->db->insert_batch('t_stock', $insert_data);
        }
        if($update_data) {
            echo count($update_data) . "\n";
            $this->db->update_batch('t_stock', $update_data, 'id');
        }
    }

    public function set_location_status($location_id, $status = 0) {
        // 禁用天津城市
        $update_data = array(
            'status' => $status
        );
        $where = array(
            'id' => $location_id
        );
        $this->MLocation->update_info($update_data, $where);
    }
}

/* End of file init_stock.php */
/* Location: ./application/controllers/init_stock.php */
