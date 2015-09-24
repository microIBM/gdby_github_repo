<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_tianjin extends MY_Controller {
    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
            )
        );
        $this->load->library(array("redisclient"));
    }

    public function set_storage_zero() {
        // 天津id
        $location_id = 1206;
        $this->MProduct->update_info(array('storage' => 0), array('location_id' => $location_id, 'is_active' => 1));
        // 重置天津的商品redis的虚拟库存值
        $products = $this->MProduct->get_lists('id, storage', array('storage' => 0, 'location_id' => $location_id, 'is_active' => 1));
        foreach($products as $product) {
            $this->redisclient->hset($product['id'], 'storage',$product['storage']);
        }
        echo mysql_affected_rows();
    }
   }

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
