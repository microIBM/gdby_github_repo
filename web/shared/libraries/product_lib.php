<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 通用product 处理
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Product_lib {

    public $units = array();
    private $_customer_visiable_config = array();
    public function __construct() {

        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MProduct',
                'MCategory',
                'MLine',
                'MBucket',
                'MSku',
                'MLocation'
            )
        );
        $this->units = C('unit');
        $this->_customer_visiable_config = C('customer_visiable');
        $this->visiables = C('visiable');
        $this->customer_type = C('customer.type');
        $this->show_storage_condition = C('limit_storage.show_storage_condition');
        $this->CI->load->library(array('redisclient', 'userauth', 'product_price'));
        $this->CI->load->helper(array('img_zoom'));
    }
    // 商城需要
    public function format_shop_product_list($products) {
        // 格式化分类信息
        $products = $this->_format_category_info($products);
        // 格式化单位信息
        $products = $this->_format_unit_info($products);
        // 格式化商品售卖区域
        $products = $this->_format_location_info($products);
        // 格式化storage 的描述
        $products = $this->_format_storage_cn($products);
        // 格式化规格信息
        $products = $this->_format_spec_info($products);
        // 格式化商品图片信息
        $products = $this->_format_img_info($products);
        // 格式化价格信息
        $products = $this->_format_price_info($products);
        return $products;
    }
    // 格式化线路中文信息 商城不需要
    private function _format_line_info($products) {
        $line_ids = array_column($products, 'line_id');
        if($line_ids) {
            $product_line_ids = [];
            foreach($line_ids as $line_id) {
                $product_line_ids = array_unique(array_merge($product_line_ids, explode(',', $line_id)));
            }
            if($product_line_ids) {
                $lines = $this->CI->MLine->get_lists(
                    'name, id', ['in' => ['id' => $product_line_ids]]
                );
                $lines = array_column($lines, NULL, 'id');
                foreach($products as &$product) {
                    if(!empty($product['line_id'])){
                        $product_line_ids = explode(',', $product['line_id']);
                        $product['line_cn'] = '';
                        foreach($product_line_ids as $product_line_id) {
                            $product['line_cn'] .= $lines[$product_line_id]['name'] . ';';
                        }
                    }
                }
                unset($product);
            }
        }
        return $products;
    }
        // 格式化分类信息 商城待考虑
    private function _format_category_info($products) {
        $cate_ids = array_values(array_unique(array_column($products, "category_id")));
        $categories = $this->CI->MCategory->get_lists(
            'id, path', array('in' => array('id' => $cate_ids))
        );
        $cate_paths = array_column($categories, "path", "id");
        foreach($products as &$product) {
            if(!empty($cate_paths[$product['category_id']])) {
                $product['category_path'] = $cate_paths[$product['category_id']];
            }
        }
        unset($product);
        return $products;
    }
    // 格式化单位信息 商城需要
    private function _format_unit_info($products) {
        foreach($products as &$product) {
            $product['unit'] = $this->get_unit_name($product['unit_id']);
            $product['close_unit'] = $product['unit_id'];
            $product['close_unit_name'] = $product['unit'];
        }
        unset($product);
        return $products;
    }

    // 显示库存信息 商城不需要
    private function _format_show_storage($products) {
        foreach($products as &$product) {
            if($product['storage'] == -1 || $product['storage'] >= $this->show_storage_condition) {
                $product['storage_cn'] = '足量库存';
            } else {
                $product['storage_cn'] = '剩余' . $product['storage'] . $product['unit'];
            }
            $product['buy_limit_cn'] = empty($product['buy_limit']) ? '不限购' : $product['buy_limit'] . $product['unit'];
        }
        unset($product);
        return $products;
    }
    // 格式化规格信息 商城需要
    private function _format_spec_info($products) {
        foreach($products as &$product) {
            if(is_string($product['spec'])) {
                $product['spec'] = json_decode($product['spec'], TRUE);
            }
            $product['spec'] = $this->_check_unique_spec($product['spec']);
            $index = array_search('规格', array_column($product['spec'], 'name'));
            $product['spec_info'] = is_bool($index) ? array() : $product['spec'][$index];
            $product['show_spec_info'] = [];
            if($product['spec_info']) {
                $product['spec_info']['name'] = '规格';
                if(!empty($product['spec_info']['val'])) {
                    // 数据结构需要统一下哦，下次开发接口，务必接口返回都是统一的类型（安卓APP）
                    $product['show_spec_info'] = array($product['spec_info']);
                }
            }
        }
        unset($product);
        return $products;
    }
    // 格式化客户类型信息 商城不需要
    private function _format_customer_type_info($products) {
        $customer_type_cn = C('customer.type.normal.name');
        foreach($products as &$product) {
            $product['customer_type_cn'] = $customer_type_cn;
        }
        unset($product);
        return $products;
    }
    // 格式化商品图片的大小 商城需要
    private function _format_img_info($products) {
        $list_sku_numbers = array_unique(array_column($products, 'sku_number'));
        // 获取sku信息
        $sku_lists = $this->CI->MSku->get_lists__Cache30(
            'pic_ids, sku_number, net_weight',
            array(
                'in' => array(
                    'sku_number' => $list_sku_numbers
                )
            )
        );
        $sku_list_ids = array_column($sku_lists, 'pic_ids');
        $new_sku = array_column($sku_lists, NULL, 'sku_number');
        $buckets = [];
        if($sku_list_ids) {
            $pic_ids = [];
            foreach($sku_list_ids as $pic_id) {
                $pic_ids = array_unique(array_merge($pic_ids, explode(',', $pic_id)));
            }
            $buckets = $this->CI->MBucket->get_lists(
                'id, pic_url',
                array(
                    'in' => array('id' => $pic_ids)
                ),
                array(
                    'id' => 'ASC'
                )
            );
            $buckets = array_column($buckets, NULL, 'id');
        }

        foreach($products as &$product) {
            $product['net_weight'] = 0;
            if(isset($new_sku[$product['sku_number']])) {
                $product['pic_ids'] = $new_sku[$product['sku_number']]['pic_ids'];
                $product['net_weight'] = $new_sku[$product['sku_number']]['net_weight'];
                if(!empty($product['pic_ids']) && $buckets) {
                    $pic_ids_arr = explode(',', $product['pic_ids']);
                    $pictures = [];
                    foreach($pic_ids_arr as $pic_id) {
                        if(isset($buckets[$pic_id])) {
                            $pictures[] = $buckets[$pic_id];
                        }
                    }
                    $product['pictures'] = img_zoom($pictures, '-240-');
                    $product['big_imgs'] = img_zoom($pictures, '-600-');
                }
            }
        }
        unset($product);
        return $products;
    }

    private function _format_price_info($products) {
        foreach($products as &$product) {
            $product['price'] = sprintf("%.2f", ($product['price'] / 100));
            $product['updated_origin_time'] = $product['updated_time'];
            $product['updated_time'] = date('Y-m-d H:i:s', $product['updated_time']);
            $product['market_price'] = sprintf("%.2f", ($product['market_price'] / 100));
            $product['single_price'] = $product['price'];
            $net_weight = empty($product['net_weight']) ? 0 : $product['net_weight'];
            $product['net_weight_price'] = empty($net_weight) ? '' : sprintf("%.2f", ($product['price'] / $net_weight)) . ' 元/斤';
            unset($product['net_weight']);
        }
        unset($product);
        return $products;
    }
    // 格式化商品可见性信息 商城不需要
    private function _format_visiable_info($products) {
        $visiables = array_column($this->visiables, NULL, 'id');
        foreach($products as &$product) {
            $product['visiable_cn'] = $visiables[$product['visiable']]['name'];
            $customer_visiable_cn = $this->get_customer_visiable_cn($product['customer_visiable']);
            if($customer_visiable_cn) {
                $product['visiable_cn'] = $customer_visiable_cn . '-' . $product['visiable_cn'];
                unset($product['customer_visiable']);
            }
        }
        unset($product);
        return $products;
    }
    // 地理位置信息 商城不需要
    private function _format_location_info($products) {
        $list_location_id = array_column($products, 'location_id');
        $locations = $this->CI->MLocation->get_lists('id, name', array('in' => array('id' => $list_location_id)));
        $locations = array_column($locations, 'name', 'id');
        foreach($products as &$product) {
            $location_name = isset($locations[$product['location_id']]) ? $locations[$product['location_id']] : '';
            $product['location_name'] = $location_name;
        }
        unset($product);
        return $products;
    }

    public function format_product_data(&$products) {
        $products = $this->format_shop_product_list($products);
        return $products;
    }

    public function format_data_by_line_id($cur, &$lists) {
        $is_login = $cur ? TRUE : FALSE;
        if($is_login) {
            $line_ids = array($cur['line_id']);
        } else {
            $line_ids = array(0);
        }
        $new_lists = [];
        foreach($lists as $key => $v) {
            $ori_lines = explode(',', $v['line_id']);
            if($v['line_id'] != 0) {
                if(!$inter = array_intersect($ori_lines, $line_ids)) {
                    unset($lists[$key]);
                    continue;
                }
            }
            $new_lists[] = $v;
        }
        $lists = $new_lists;
    }


    /**
     * @author: liaoxianwen@ymt360.com
     * @param $is_shop boolean false 为后台管理商品列表使用
     * @description 格式化商品列表细心
     */
    public function format_sms_product_data(&$products) {
        // 格式化分类信息
        $products = $this->_format_category_info($products);
        // 格式化单位信息
        $products = $this->_format_unit_info($products);
        // 格式化地理位置信息
        $products = $this->_format_location_info($products);
        // 格式化线路信息
        $products = $this->_format_line_info($products);
        // 格式化规格信息
        $products = $this->_format_spec_info($products);
        // 格式化商品图片信息
        $products = $this->_format_img_info($products);
        // 格式化可见性信息
        $products = $this->_format_visiable_info($products);
        // 格式化用户类型信息
        $products = $this->_format_customer_type_info($products);
        // 格式化显示库存信息
        $products = $this->_format_show_storage($products);
        // 格式化价格信息
        $products = $this->_format_price_info($products);
        return $products;
    }

    // 设置storage 描述
    private function _format_storage_cn($products) {
        foreach($products as &$product) {
            $product['storage_cn'] = '足量库存';
            if($product['storage'] >= 0) {
                $product['storage_cn'] = '库存剩余' . $product['storage'] . $product['unit'];
            }
        }
        unset($product);
        return $products;
    }
    // 确保spec 唯一
    private function _check_unique_spec($spec) {
        $name_arr = $new_spec = array();
        if($spec) {
            foreach($spec as $v) {
                if(isset($v['name']) && $v['name'] != '单价') {
                    if(!in_array($v['name'], $name_arr)) {
                        $new_spec[] = $v;
                    }
                    $name_arr[] = $v['name'];
                }
            }
        }
        return $new_spec;
    }

    public function get_unit_id($name) {
        $id = $this->units[0]['id'];
        foreach($this->units as $v) {
            if($v['name'] == $name) {
                $id = $v['id'];
            }
        }
        return $id;
    }
    public function get_unit_name($id) {
        $new_units = array_column($this->units, NULL, 'id');
        return isset($new_units[$id]['name']) ? $new_units[$id]['name'] : '';
    }

    public function get_customer_visiable_cn($customer_visiable) {
        $customer_visiables = array_column($this->_customer_visiable_config, NULL, 'value');
        return isset($customer_visiables[$customer_visiable]['name']) ? $customer_visiables[$customer_visiable]['name'] : '';
    }

    public function set_product_fields($products) {
        $product_list = [];
        foreach($products as $product) {
            $product_list[] = array(
                'sku_number' => $product['sku_number'],
                'collect_type' => $product['collect_type']
            );
        }
        return $product_list;
    }

    public function set_default_check_storage_list($storage_info, &$set_lists) {
        if(!empty($storage_info['list'])) {
            $column_list = array_column($storage_info['list'], NULL, 'sku_number');
            foreach($set_lists as &$set_list) {
                if(isset($column_list[$set_list['sku_number']])) {
                    $set_list['storage'] = $column_list[$set_list['sku_number']]['storage'];
                }
            }
            unset($set_list);
        }
    }
}

/* End of file product.php */
/* Location: ./application/controllers/product.php */
