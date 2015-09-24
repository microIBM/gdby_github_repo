<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @description 订单拆分模块
 * @author caochunhui@dachuwang.com
 */
class Order_split {

    public function __construct () {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MOrder_type_config',
                'MCustomer',
                'MCategory',
                'MProduct',
            )
        );
        $this->collect_type_pre = C('foods_collect_type.type.pre_collect.value');
        $this->collect_type_now = C('foods_collect_type.type.now_collect.value');
    }

    /**
     * 获取订单类型列表
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-07-01
     */
    public function get_config() {
        $order_type_config = $this->CI->MOrder_type_config->get_lists(
            "id as code, type_name as msg",
            array(
                'status' => C('status.common.success'),
            )
        );
        return $order_type_config;
    }

    //把所有的现采的类型找出来
    private function _pickout_collect_now_products($products = array()) {
        if (empty($products)) {
            return array(
                array(),array()
            );
        }
        $product_ids = array_column($products, 'id');
        $pid_map_collect_type = array();
        if($product_ids) {
            $pid_map_collect_type = $this->CI->MProduct->get_lists(
                'id,collect_type',
                array(
                    'in' => array('id' => $product_ids)
                )
            );
        }
        $pid_map_ctype = $pid_map_collect_type ? array_column($pid_map_collect_type, 'collect_type', 'id') : array();
        $pickouts = [];
        foreach($products as $idx => $product) {
            if (isset($pid_map_ctype[$idx]) && $pid_map_ctype[$idx] == $this->collect_type_now) {
                $pickouts[] = $product;
                unset($products[$idx]);
            }
        }
        return array($pickouts, $products);
    }

    /*
     *@description 取完现采只好把所有的数据都设置预采
     */
    private function _pickout_collect_pre_products($products = array()) {
        if (empty($products)) {
            return array(
                array(),array()
            );
        }
        //把所有的想都设置未预采，这个函数要保证在现采之后运行
        $pickouts = [];
        foreach($products as $idx => $product) {
            $pickouts[] = $product;
            unset($products[$idx]);
        }
        return array($pickouts, $products);
    }

    /**
     * @description 拆分出特定sku_number的products
     * 比以category做判断的优先级高
     */
    private function _pickout_special_sku_products($sku_numbers = array(), $products = array()) {
        if(empty($sku_numbers)) {
            return array(
                array(), $products
            );
        }
        $pickouts = [];
        foreach($products as $idx => $product) {
            if(in_array($product['sku_number'], $sku_numbers)) {
                $pickouts[] = $product;
                unset($products[$idx]);
            }
        }

        return array($pickouts, $products);
    }

    /*
     * @description 拆出特定品类下的商品作为子订单
     *  比如爆款，比如水果
     * @TODO 需要优化
     */
    private function _pickout_special_category_products($category_ids = array(), $products = array()) {
        if(empty($category_ids)) {
            return array(
                array(), $products
            );
        }
        $pickouts = [];

        //如果path里包含传入category_ids的其中一个，那么认为符合条件
        $category_ids_calc = [];
        $categories = $this->CI->MCategory->get_children_ids(
            $category_ids
        );
        $category_ids_calc = array_column($categories, 'id');

        foreach($products as $idx => $product) {
            if(in_array($product['category_id'], $category_ids_calc)) {
                $pickouts[] = $product;
                unset($products[$idx]);
            }
        }
        return array($pickouts, $products);
    }

    public function group_products($user_id = 0, $products = array()) {
        if (empty($user_id) || empty($products)) {
            return array(
                1 => $products
            );
        }
        $user = $this->CI->MCustomer->get_one(
            'province_id',
            array(
                'id' => $user_id
            )
        );
        $city_id = empty($user['province_id']) ? 0 : $user['province_id'];

        $rules = $this->CI->MOrder_type_config->get_lists(
            '*',
            array(
                'in' => array(
                    'city_id' => array(
                        0, //全国
                        $city_id
                    )
                ),
                'status' => 1
            ),
            array(
                'score' => 'DESC'
            )
        );
        $result = array();
        $remaining_prods = array_column($products, NULL, 'id');
        //现采预采来查分订单,要保证现采优先级第一，预采优先级第二
        //取出现采的其他的都是预采
        foreach($rules as $rule) {
            if ($remaining_prods) {
                $order_type = $rule['id'];
                $collect_type = $rule['collect_type'] ? $rule['collect_type'] : $this->collect_type_pre;
                if ($collect_type == $this->collect_type_now) {
                    list($pickouts, $remaining_prods) = $this->_pickout_collect_now_products($remaining_prods);
                    if ( ! empty($pickouts)) {
                        $result[$order_type] = $pickouts;
                    }
                }
                if ($collect_type == $this->collect_type_pre) {
                    list($pickouts, $remaining_prods) = $this->_pickout_collect_pre_products($remaining_prods);
                    if ( ! empty($pickouts)) {
                        $result[$order_type] = $pickouts;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @description 分组商品
     */
    public function group_products_bak($user_id = 0, $products = array()) {
        if(empty($user_id) || empty($products)) {
            return array(
                1 => $products
            );
        }

        $user = $this->CI->MCustomer->get_one(
            'province_id',
            array(
                'id' => $user_id
            )
        );
        $city_id = empty($user['province_id']) ? 0 : $user['province_id'];

        $rules = $this->CI->MOrder_type_config->get_lists(
            '*',
            array(
                'in' => array(
                    'city_id' => array(
                        0, //全国
                        $city_id
                    )
                ),
                'status' => 1
            ),
            array(
                'score' => 'DESC'
            )
        );

        $result = array();
        $remaining_prods = $products;

        //必须保证除了普通订单规则之外，其它规则的sku_numbers和category_ids不为空
        foreach($rules as $rule) {
            $order_type = $rule['id'];
            if(!empty($rule['sku_numbers'])) {
                $sku_numbers = explode('.', $rule['sku_numbers']);
                list($pickouts, $remaining_prods) = $this->_pickout_special_sku_products($sku_numbers, $remaining_prods);
                if(!empty($pickouts)) {
                    $result[$order_type] = $pickouts;
                }
                continue;
            }
            if(!empty($rule['category_ids'])) {
                $category_ids = explode('.', $rule['category_ids']);
                list($pickouts, $remaining_prods) = $this->_pickout_special_category_products($category_ids, $remaining_prods);
                if(!empty($pickouts)) {
                    $result[$order_type] = $pickouts;
                }
                $result[$order_type] = $pickouts;
                continue;
            }
            $result[$order_type] = $remaining_prods;
        }
        return $result;

    }

}

/* End of file order_split.php */
/* Location: ./application/controllers/order_split.php */
