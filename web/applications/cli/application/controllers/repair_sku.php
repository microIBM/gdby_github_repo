<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 修复sku_number
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-3-25
 */
class Repair_sku extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MSku',
                'MOrder',
                'MProperty',
                'MOrder_detail'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 将product表的数据复制到sku表中，生产新的sku_number
     */
    public function repair_t_sku() {
        $data = $this->MProduct->get_lists("id, status, title, spec, category_id");
        foreach($data as $v) {
            $req_time = $this->input->server('REQUEST_TIME');
            $property = json_decode($v['spec'], TRUE);
            $single_price_property = array();
            // 修复规格里面的单价属性
            if($property) {
                foreach($property as $pro_val) {
                    if(isset($pro_val['name'])) {
                        if($pro_val['name'] == '单价') {
                            $single_price_property = $pro_val;
                        }
                    }
                }
            }
            $single_price = 0;
            $close_unit = 0;
            if($single_price_property) {
                $units = C('unit');
                $prop_arr = explode('/', $single_price_property['val']);
                if(isset($prop_arr[1])) {
                    $close_unit_name = $prop_arr[1];
                    foreach($units as $unit_val) {
                        if($unit_val['name'] == $close_unit_name) {
                            $close_unit = $unit_val['id'];
                        }
                    }
                    $single = explode('元', $prop_arr[0]);
                    $single_price = $single[0] * 100;
                }
            }
            $sku_data = array(
                'id'           => $v['id'],
                'name'         => $v['title'],
                'spec'         => $v['spec'],
                'sku_number'   => set_sku($v['id']),
                'error_code'   => C('status.common.success'),
                'category_id'  => $v['category_id'],
                'status'       => C('status.common.success'),
                'created_time' => $req_time,
                'updated_time' => $req_time
            );
            $up_data = array(
                'single_price' => $single_price,
                'close_unit'   => $close_unit,
                'sku_number' => set_sku($v['id'])
            );
            if(intval($v['status']) === 0) {
                $up_data['is_active'] = 0;
            } else {
                $up_data['is_active'] = 1;
            }
            // 更新product 的sku号
            $this->MProduct->update_info($up_data, array('id' => $v['id']));
            // 创建新的sku信息
            $this->MSku->create($sku_data);
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 修复order detail 表
     */
    public function repair_order_detail() {
        $order_detail = $this->MOrder_detail->get_lists('id, order_id, product_id');
        $not_found = array();
        foreach($order_detail as $v) {
            $product = $this->MProduct->get_one('category_id,sku_number, single_price, close_unit, unit_id', array('id' => $v['product_id']));
            if($product) {
                $up_data = array(
                    'sku_number'   => $product['sku_number'],
                    'single_price' => $product['single_price'],
                    'close_unit'   => $product['close_unit'],
                    'unit_id'      => $product['unit_id'],
                    'category_id'  => $product['category_id']
                );
                $order_info = $this->MOrder->get_one('status', array('id' => $v['order_id']));
                if($order_info) {
                    $up_data['status'] = $order_info['status'];
                }
                $this->MOrder_detail->update_info($up_data, array('id' => $v['id']));
            } else {
                $not_found[] = $v;
            }
        }
        echo '<pre>';var_dump($not_found);echo 'success';
    }

    /**
     * 清理所有t_product表里spec中的单价属性
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-04-06
     */
    public function clear_product_spec() {
        $products = $this->MProduct->get_lists('id, spec, single_price');
        foreach($products as $item) {
            if($item['single_price'] == 0) {
                continue;
            }
            $spec = $item['spec'];
            echo "FIX:\t{$item['id']}";
            if(!empty($spec)) {
                $spec = json_decode($spec, TRUE);
                $new_spec = array();
                foreach($spec as $v) {
                    if($v['name'] != '单价') {
                        $new_spec[] = $v;
                    } else {
                        echo "\t{$v['name']}:{$v['val']}";
                    }
                }
                $new_spec = json_encode($new_spec);
                $count1 = $this->MProduct->update_info(array('spec' => $new_spec), array('id' => $item['id']));
                $count2 = $this->MOrder_detail->update_info(array('spec' => $new_spec), array('product_id' => $item['id']));
                echo "\t{$count1}\t{$count2}\r\n";
            }
        }
    }
}

/* End of file repair_sku.php */
/* Location: ./application/controllers/repair_sku.php */
