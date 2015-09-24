<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 离线跑分类映射的商品数量统计
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-8-25
 */
class Fix_catemap extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory_map',
                'MCategory',
                'MProduct',
            )
        );
    }

   /**
     * @author: liaoxianwen@ymt360.com
     * @description 统计分类映射下的商品数量
     * @param location_id 城市id 804 993 1206
     * @param customer_type 客户类型 1是普通 2是ka
     */
    public function set_map_product_nums($location_id, $customer_type) {
        // 获取符合条件的商品
        $products = $this->_get_products($location_id, $customer_type);
        // 分类映射的查询条件
        $catemap_where_condition = [
            'location_id' => $location_id,
            'customer_type' => $customer_type
        ];
        // 获取到分类映射的数据
        $catemap = $this->_get_catemap($catemap_where_condition);
        $map_product_nums = $this->_set_product_nums($products, $catemap);
        $rows = $this->db->update_batch('t_category_map', $map_product_nums, 'id');
        echo $rows . '设置成功';
    }

    private function _set_product_nums($products, $catemap) {
        $final_map_products = [];
        foreach($catemap as &$map) {
            $final_map_product_nums['product_nums'] = 0;
            $final_map_product_nums['id'] = $map['id'];
            foreach($products as $product) {
                if(!is_bool(strpos($product['path'], ".{$map['origin_id']}."))) {
                    $final_map_product_nums['product_nums'] += 1;
                }
            }
            $final_map_products[] = $final_map_product_nums;
        }
        return $final_map_products;
    }

    private function _get_catemap($catemap_where_condition) {
        $catemaps = $this->MCategory_map->get_lists('id, origin_id', $catemap_where_condition);
        return $catemaps;
    }

    // 获取当前地区，当前用户类型 的商品有效数据
    private function _get_products($location_id, $customer_type) {
        // 若是普通客户，那么customer_visiable in (0, 1) 且 customer_type = 1
        if(intval($customer_type) === 1) {
            // 商品用户可见性 设置为普通客户可见 & 全部客户可见
            $product_where_condition = [
                'in' => [
                    'customer_visiable' => [0, 1]
                ]
            ];
        } else {
            // 商品用户可见性 设置为KA客户可见 & 全部客户可见
            $product_where_condition = [
                'in' => [
                    'customer_visiable' => [0, 2]
                ]
            ];
        }
        $product_where_condition['location_id'] = $location_id;
        $product_where_condition['status'] = C('status.product.up');
        // 1 为默认普通客户的商品
        $product_where_condition['customer_type'] = 1;
        $products = $this->MProduct->get_lists('id, category_id', $product_where_condition);
        $product_category_ids = array_unique(array_column($products, 'category_id'));
        $category_condition = [
            'in' => ['id' => $product_category_ids]
        ];
        $categories = $this->_get_category($category_condition);
        foreach($products as &$product) {
            $category_id = $product['category_id'];
            if(isset($categories[$category_id])) {
                $product['path'] = $categories[$category_id];
            }
        }
        unset($product);
        return $products;
    }

    private function _get_category($category_condition) {
        $categories = $this->MCategory->get_lists('path, id', $category_condition);
        return array_column($categories, 'path', 'id');
    }
}

/* End of file fix_catemap.php */
/* Location: ./application/controllers/fix_catemap.php */
