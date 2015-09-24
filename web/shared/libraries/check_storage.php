<?php
//检查库存基类
class Check_storage {
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model(array('MLine', 'MProduct', 'MStock'));
        $this->CI->load->library(array('redisclient', 'userauth'));
        $this->collect_type_now = C('foods_collect_type.type.now_collect.value');
        $this->collect_type_pre = C('foods_collect_type.type.pre_collect.value');
        $this->op_failed        = C('tips.code.op_failed');
        $this->op_success       = C('tips.code.op_success');
    }

    //检查库存
    //title,id,location_id,quantity,category_id,sku_number
    public function check($products) {
        if ($products) {
            $products = is_array($products) ? $products : array($products);
            $this->_set_products($products);
            $product_filted_virtual = $this->_check_virtual_storage($this->products);
            $product_filted_filter  = $this->_filter($product_filted_virtual);
            $products_not_have_storage = $this->_check_real_storage($product_filted_filter);
            if ($products_not_have_storage) {
                $this->display_storage_info($products_not_have_storage);
            }
        }
    }

    private function _set_products($products) {
        $this->products = $products;
        $this->product_ids = array_column($this->products, 'id');
        $this->pid_map_product = array_combine($this->product_ids, $this->products);
    }

    //过滤规则，现采的和北仓的不走实时库存
    private function _filter($products) {
        $filted_products = array();
        $cur = $this->_user_info_with_ip();
        $line_id = empty($cur['line_id']) ? 0 : $cur['line_id'];
        $warehouse_id = $this->CI->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );
        $warehouse_id = empty($warehouse_id) ? 0 : $warehouse_id['warehouse_id'];
        //如果这个用户是来自北仓那么就不进行实时库存检查
        $config_warehouse = C('storage_check.warehouse_id');
        $warehouse_ids    = array_column($config_warehouse, 'id');
        if (in_array($warehouse_id, $warehouse_ids)) {
            $filted_products = $products;
        } else {
            $product_info = $this->CI->MProduct->get_lists(
                'id,collect_type',
                array(
                    'in' => array('id' => $this->product_ids)
                )
            );
            $pid_map_collect_type = array_column($product_info, 'collect_type', 'id');
            foreach($products as $product) {
                $product_id = $product['id'];
                if (isset($pid_map_collect_type[$product_id]) && $pid_map_collect_type[$product_id] == $this->collect_type_now) {
                    continue;
                }
                $filted_products[] = $product;
            }
        }
        return $filted_products;
    }

    private function _set_products_data($products) {
        $this->products        = $products;
        $this->product_ids     = array_column($this->products, 'id');
        $this->pid_map_product = array_combine($this->product_ids, $this->products);
    }
    /**
     * 获取用户ip信息
     *
     * @author : caiyilong@ymt360.com
     * @version : 1.0.0
     * @since : 2015-05-12
     */
    private function _user_info_with_ip() {
        $cur = $this->CI->userauth->current(TRUE);
        if (empty($cur)) {
            return $cur;
        }
        $ip = $this->CI->input->ip_address();
        $cur['ip'] = $ip;
        return $cur;
    }

    protected function _return_json($arr) {
        if(in_array($this->CI->input->server("HTTP_ORIGIN"), C("allowed_origins"))) {
            header('Access-Control-Allow-Origin: ' . $this->CI->input->server("HTTP_ORIGIN"));
        } else {
            header('Access-Control-Allow-Origin: http://www.dachuwang.com');
        }
        header('Content-Type: application/json');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: X-Requested-With');
        header('Cache-Control: no-cache');
        echo json_encode($arr);exit;
    }

    //返回错误信息
    protected function _return_failed($msg = '') {
        $this->_return_json(
            array(
                'status' => $this->op_failed,
                'msg'    => $msg
            )
        );

    }

    //返回正常信息
    protected function _return_success($msg = '', $info = array()) {
        $this->_return_json(
            array(
                'status' => $this->op_success,
                'msg'    => $msg,
                'info'   => $info
            )
        );
    }

    /*
     *@description 检查购买的商品在虚拟内存中够不够
     *@return 返回在虚拟库存中库存不够的商品id
     */
    //如果传递过来的参数本身就使一个数组
    private function _check_virtual_storage($products) {
        if (empty($products)) {
            return array();
        }
        $products_storage_not_enough = array();
        $product_ids = array_column($products, 'id');
        foreach($product_ids as $product_id) {
            $redis_data['storage'] = $this->CI->redisclient->hget($product_id, 'storage');
            //如果库存中有，但是不足
            if ( ! is_bool($redis_data['storage']) && $this->pid_map_product[$product_id]['quantity'] > $redis_data['storage']) {
                $this->_return_failed('虚拟库存不足');
            } else if ($redis_data['storage'] === FALSE) {
            //虚拟库存中没有,要去实时库存中检查下
                $products_storage_not_enough[] = $this->pid_map_product[$product_id];
            }
        }
        //其他的都是虚拟库存有的，虚拟库存的优先级比较高
        //返回那些虚拟库存中没有的，需要去实时库存中检查的
        return $products_storage_not_enough;
    }

    /*
     *@description 检查实时库存
     *@return 返回实时库存不足的商品
     */
    protected function _check_real_storage($products) {
        //如果实时库存的开关关了，那么不执行检查
        if (C('realtime_stock.switch') != 'on' || empty($products)) {
            return array();
        }
        $cur     = $this->_user_info_with_ip();
        $line_id = $cur ? $cur['line_id'] : 0;
        $city_id = $cur ? $cur['province_id'] : 0;

        if ( ! in_array($city_id, C('realtime_stock.cities'))) {
            return array();
        }

        //根据用户的信息去查找用户的线路，更加用户的线路信息去查找仓库信息
        $warehouse_id = $this->CI->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );
        $warehouse_id = empty($warehouse_id) ? 0 : $warehouse_id['warehouse_id'];
        $product_ids = array_column($products, 'id');
        //每个货号－－》商品
        $sku_map_prod   = array_column($products, NULL, 'sku_number');
        $sku_nums       = array_column($products, 'sku_number');
        $sku_nums       = $sku_nums ? $sku_nums : array(0);
        $quantity_in_db = $this->CI->MStock->get_lists(
            '*',
            array(
                'warehouse_id' => $warehouse_id,
                'in' => array(
                    'sku_number' => $sku_nums
                )
            )
        );
        //货品在库存中的库存信息
        $sku_map_stock   = array_column($quantity_in_db, NULL, 'sku_number');
        $no_record_limit = C('realtime_stock.no_record_limit');
        //超过库存的商品
        $products_over_storage = array();
        foreach($sku_nums as $sku) {
            //如果没有存储信息
            if ( ! isset($sku_map_stock[$sku])) {
                if($no_record_limit == "on") {
                    $stock_can_be_sold = 0;
                } else {
                    continue;
                }
            } else {
                //如果有库存信息
                $storage_info = $sku_map_stock[$sku];
                $stock_can_be_sold = $storage_info['in_stock'] - $storage_info['stock_locked'];
            }
            //提交的购买数
            $post_quantity = $sku_map_prod[$sku]['quantity'];
            //如果购买的数量超过库存那么返回超过库存的product_ids
            if ($stock_can_be_sold < $post_quantity) {
                $products_over_storage[] = $sku_map_prod[$sku];
            }
        }
        return $products_over_storage;
    }

    //打印库存不足信息
    //如果够不打印任何信息
    protected function display_storage_info($products) {
        if ($products) {
            $titles = implode(',', array_column($products, 'title'));
            $this->_return_failed($titles . ' 当前库存不足');
        }
    }
    //列表页面显示库存信息
    //那些被过滤掉的商品
    public function check_list_storage(&$products) {
        $cur = $this->_user_info_with_ip();
        if (empty($products) || empty($cur) || C('realtime_stock.switch') != 'on') {
            return ;
        }
        $line_id = $cur['line_id'];
        $city_id = $cur['province_id'];
        if ( ! in_array($city_id, C('realtime_stock.cities'))) {
            return ;
        }
        $warehouse_id = $this->CI->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );
        $warehouse_id = empty($warehouse_id) ? 0 : $warehouse_id['warehouse_id'];
        $sku_numbers  = array_column($products, 'sku_number');
        $sku_to_prod  = array_column($products, NULL, 'sku_number');
        $quantity_in_db = $this->CI->MStock->get_lists(
            '*',
            array(
                'warehouse_id' => $warehouse_id,
                'in' => array('sku_number' => $sku_numbers)
            )
        );
        $skus = array_column($quantity_in_db, 'sku_number');
        $sku_to_stock = array_column($quantity_in_db, NULL, 'sku_number');
        $no_record_limit = C('realtime_stock.no_record_limit');
        $is_continue = $this->_list_filter($products);
        // add by xianwen 控制显示库存的一个阈值
        $show_storage_condition = C('limit_storage.show_storage_condition');
        foreach($products as &$item) {
            if ($item['storage'] != -1 || $is_continue[$item['id']]) {
                continue;
            }
            if ( ! isset($sku_to_stock[$item['sku_number']])) {
                if ($no_record_limit == 'on') {
                    $stock_can_be_sold = 0;
                } else {
                    continue;
                }
            }
            $stock = $sku_to_stock[$item['sku_number']];
            $stock_can_be_sold = $stock['in_stock'] - $stock['stock_locked'];
            if ($stock_can_be_sold < $show_storage_condition) {
                $item['storage'] = $stock_can_be_sold > 0 ? $stock_can_be_sold : 0;
                $item['storage_cn'] = '剩余' . $item['storage'] . $item['unit'];
            } else {
                $item['storage'] = -1;
                $item['storage_cn'] = '足量库存';
            }
        }
        unset($item);
    }

    //如果对应的商品id返回true则在显示的时候直接过滤掉
    private function _list_filter($products) {
        $cur = $this->_user_info_with_ip();
        $line_id = empty($cur['line_id']) ? 0 : $cur['line_id'];
        $warehouse_id = $this->CI->MLine->get_one(
            'warehouse_id',
            array(
                'id' => $line_id
            )
        );
        $warehouse_id = empty($warehouse_id) ? 0 : $warehouse_id['warehouse_id'];
        $config_warehouse = C('storage_check.warehouse_id');
        $warehouse_ids    = array_column($config_warehouse, 'id');
        $flag = FALSE;
        if (in_array($warehouse_id, $warehouse_ids)) {
            $flag = TRUE;
        }
        $is_continue = array();
        foreach($products as $product) {
            if($product['storage'] >= 0) {
                // 若设置了虚拟库存，那么就走虚拟库存
                $is_continue[$product['id']] = TRUE;
            } else {
                //如果是北仓的，那么都要走实时库存
                if ($flag) {
                    $is_continue[$product['id']] = FALSE;
                    //如果不是北仓的
                } else {
                    if ($product['collect_type'] == $this->collect_type_now) {
                        $is_continue[$product['id']] = TRUE;
                    } else {
                        $is_continue[$product['id']] = FALSE;
                    }
                }
            }
        }
        return $is_continue;
    }
}
?>
