<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * 修复sku_number
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-3-25
 */
class Repair_suborder extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MOrder',
                'MSuborder',
                'MOrder_detail'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 凌晨3-4点跑脚本
     */
    public function done() {
        set_time_limit(0);
        // truncate suborder 表
        $this->_truncate_suborder();
        // 修复order_de
        $this->_repair_order_detail();
        // 同步order 数据到suborder
        $this->_copy_order_to_sub();

    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 修复order detail 表
     */
    private function _truncate_suborder() {
        $this->db->query('TRUNCATE TABLE t_suborder');
    }

    private function _copy_order_to_sub() {
        $this->db->query("insert into `t_suborder`
            (
            `order_id`,
            `order_number`,
            `username`,
            `user_id`,
            `remarks`,
            `status`,
            `created_time`,
            `updated_time`,
            `total_price`,
            `deal_price`,
            `city_id`,
            `market_id`,
            `site_src`,
            `sign_msg`,
            `deliver_time`,
            `deliver_date`,
            `line_id`,
            `location_id`,
            `minus_amount`,
            `promo_event_rule_id`,
            `sale_id`,
            `sale_role`,
            `dist_id`,
            `dist_order`,
            `wave_id`,
            `pick_task_id`,
            `order_type`,
            `deliver_fee`,
            `final_price`,
            `promotion_id`,
            `pay_type`,
            `pay_status`,
            `customer_coupon_id`,
            `customer_type`,
            `service_fee_rate`,
            `service_fee`
        )
        SELECT `t_order`.`id`,
        `t_order`.`order_number`,
        `t_order`.`username`,
        `t_order`.`user_id`,
        `t_order`.`remarks`,
        `t_order`.`status`,
        `t_order`.`created_time`,
        `t_order`.`updated_time`,
        `t_order`.`total_price`,
        `t_order`.`deal_price`,
        `t_order`.`city_id`,
        `t_order`.`market_id`,
        `t_order`.`site_src`,
        `t_order`.`sign_msg`,
        `t_order`.`deliver_time`,
        `t_order`.`deliver_date`,
        `t_order`.`line_id`,
        `t_order`.`location_id`,
        `t_order`.`minus_amount`,
        `t_order`.`promo_event_rule_id`,
        `t_order`.`sale_id`,
        `t_order`.`sale_role`,
        `t_order`.`dist_id`,
        `t_order`.`dist_order`,
        `t_order`.`wave_id`,
        `t_order`.`pick_task_id`,
        `t_order`.`order_type`,
        `t_order`.`deliver_fee`,
        `t_order`.`final_price`,
        `t_order`.`promotion_id`,
        `t_order`.`pay_type`,
        `t_order`.`pay_status`,
        `t_order`.`customer_coupon_id`,
        `t_order`.`customer_type`,
        `t_order`.`service_fee_rate`,
        `t_order`.`service_fee`
        FROM `t_order`");
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    private function _repair_order_detail() {
        $this->db->query('UPDATE t_order_detail set suborder_id = order_id');
    }
}

/* End of file repair_sku.php */
/* Location: ./application/controllers/repair_sku.php */
