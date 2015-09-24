<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 修复sku_number
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-3-25
 */
class Fix_product extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MProduct_snapshot',
            )
        );
    }
    public function fix_storage() {
        $update_data = array('storage' => -1);
        $where = array('status !=' => 0);
        $this->MProduct->update_info($update_data, $where);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 修复line_id错误数据
     */
    public function fix_line_id() {
        $update_data = array('line_id' => 0);
        $where = array('line_id >' => 0);
        $this->MProduct->update_info($update_data, $where);
    }

    public function fix_snapshot() {
        $this->db->query("insert into `t_product_snapshot`
            (
                `product_id`,
                `sku_number`,
                `title`,
                `category_id`,
                `user_id`,
                `unit_id`,
                `created_time`,
                `updated_time`,
                `adv_words`,
                `price`,
                `market_price`,
                `single_price`,
                `spec`,
                `status`,
                `close_unit`,
                `is_round`,
                `location_id`,
                `storage`,
                `deposit`,
                `buy_limit`,
                `line_id`,
                `visiable`,
                `customer_type`,
                `collect_type`,
                `customer_visiable`
            )
            SELECT `t_product`.`id`,
             `t_product`.`sku_number`,
             `t_product`.`title`,
             `t_product`.`category_id`,
             `t_product`.`user_id`,
             `t_product`.`unit_id`,
             `t_product`.`created_time`,
             `t_product`.`updated_time`,
             `t_product`.`adv_words`,
             `t_product`.`price`,
             `t_product`.`market_price`,
             `t_product`.`single_price`,
             `t_product`.`spec`,
             `t_product`.`status`,
             `t_product`.`close_unit`,
             `t_product`.`is_round`,
             `t_product`.`location_id`,
             `t_product`.`storage`,
             `t_product`.`deposit`,
             `t_product`.`buy_limit`,
             `t_product`.`line_id`,
             `t_product`.`visiable`,
             `t_product`.`customer_type`,
             `t_product`.`collect_type`,
             `t_product`.`customer_visiable`
           FROM `t_product` where is_active = 0 and customer_type = 1");
        }

    public function fix_snap_product_id($location_id) {
        $snap_products = $this->MProduct_snapshot->get_lists('id, sku_number', array('location_id' => $location_id, 'customer_type' => 1));
        // 找出当前还处在上架的商品
        $valid_products = $this->MProduct->get_lists('id, sku_number', array('is_active' => C('status.product.up'), 'location_id' => $location_id, 'customer_type' => 1));
        $snapshots = [];
        foreach($snap_products as $snap) {
            $key = $snap['sku_number'];
            foreach($valid_products as $product) {
                $product_key = $product['sku_number'];
                if($key == $product_key) {
                    $snapshot['product_id'] = $product['id'];
                    $snapshot['id'] = $snap['id'];
                    $snapshots[] = $snapshot;
                }
            }
        }
        $rows = $this->db->update_batch('t_product_snapshot', $snapshots, 'id');
        var_dump($rows);die;
        echo '更新成功' . $rows;

    }
}

/* End of file repair_sku.php */
/* Location: ./application/controllers/repair_sku.php */
