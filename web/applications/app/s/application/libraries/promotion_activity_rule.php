<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * @version 1.0.0
 */
//成功返回id,失败返回false
class Promotion_activity_rule {
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'mdiscount_rule',
                'mfull_minus_rule',
                'mfull_gift_rule',
                'mimmediate_minus_rule',
            )
        );
    }
    private function _create_full_minus_rule($rule = null) {
        if (!$rule || !is_array($rule)) {
            return false;
        }
        $data = array();
        $data['title'] = isset($rule['title']) ? $rule['title'] : '';
        $data['promotion_id'] = isset($rule['promotion_id']) ? $rule['promotion_id'] : 0;
        $data['require_amount'] = isset($rule['require_amount']) ? $rule['require_amount'] : 0;
        $data['minus_amount'] = isset($rule['minus_amount']) ? $rule['minus_amount'] : 0;
        $data['sku_number'] = isset($rule['sku_number']) ? $rule['sku_number'] : 0;
        $data['category_id'] = isset($rule['category_id']) ? $rule['category_id'] : 0;
        $data['min_quantity'] = isset($rule['min_quantity']) ? $rule['min_quantity'] : 0;
        $data['status'] = isset($rule['status']) ? $rule['status'] : 0;
        $data['created_time'] = isset($rule['created_time']) ? $rule['created_time'] : 0;
        $data['update_time'] = isset($rule['update_time']) ? $rule['update_time'] : 0;
        $rule_id = $this->CI->MFull_minus_rule->create($data);
        return $rule_id;
    }

    private function _create_full_gift_rule($rule = null) {
        if (!$rule || !is_array($rule)) {
            return false;
        }
        $data = array();
        $data['title'] = isset($rule['title']) ? $rule['title'] : '';
        $data['promotion_id'] = isset($rule['promotion_id']) ? $rule['promotion_id'] : 0;
        $data['sku_number'] = isset($rule['sku_number']) ? $rule['sku_number'] : 0;
        $data['require_amount'] = isset($rule['require_amount']) ? $rule['require_amount'] : 0;
        $data['min_quantity'] = isset($rule['min_quantity']) ? $rule['min_quantity'] : 0;
        $data['max_quantity'] = isset($rule['max_quantity']) ? $rule['max_quantity'] : 0;
        $data['category_id'] = isset($rule['category_id']) ? $rule['category_id'] : 0;
        $data['gift_sku_number'] = isset($rule['gift_sku_number']) ? $rule['gift_sku_number'] : 0;
        $data['gift_coupon_id'] = isset($rule['gift_coupon_id']) ? $rule['gift_coupon_id'] : 0;
        $data['status'] = isset($rule['status']) ? $rule['status'] : 0;
        $data['created_time'] = isset($rule['created_time']) ? $rule['created_time'] : 0;
        $data['update_time'] = isset($rule['update_time']) ? $rule['update_time'] : 0;
        $rule_id = $this->CI->MFull_gift_rule->create($data);
        return $rule_id;
    }

    private function _create_immediate_minus_rule($rule = null) {
        if (!$rule || !is_array($rule)) {
            return false;
        }
        $data = array();
        $data['title'] = isset($rule['title']) ? $rule['title'] : '';
        $data['promotion_id'] = isset($rule['promotion_id']) ? $rule['promotion_id'] : 0;
        $data['sku_number'] = isset($rule['sku_number']) ? $rule['sku_number'] : 0;
        $data['category_id'] = isset($rule['category_id']) ? $rule['category_id'] : 0;
        $data['minus_amount'] = isset($rule['minus_amount']) ? $rule['minus_amount'] : 0;
        $data['discount'] = isset($rule['discount']) ? $rule['discount'] : 0;
        $data['min_quantity'] = isset($rule['min_quantity']) ? $rule['min_quantity'] : 0;
        $data['max_quantity'] = isset($rule['max_quantity']) ? $rule['max_quantity'] : 0;
        $data['status'] = isset($rule['status']) ? $rule['status'] : 0;
        $data['created_time'] = isset($rule['created_time']) ? $rule['created_time'] : 0;
        $data['update_time'] = isset($rule['update_time']) ? $rule['update_time'] : 0;
        $rule_id = $this->CI->MImmediate_minus_rule->create($data);
        return $rule_id;
    }

    private function _create_discount_rule($rule = null) {
        if (!$rule || !is_array($rule)) {
            return false;
        }
        $data = array();
        $data['title'] = isset($rule['title']) ? $rule['title'] : '';
        $data['promotion_id'] = isset($rule['promotion_id']) ? $rule['promotion_id'] : 0;
        $data['sku_number'] = isset($rule['sku_number']) ? $rule['sku_number'] : 0;
        $data['category_id'] = isset($rule['category_id']) ? $rule['category_id'] : 0;
        $data['require_min_amount'] = isset($rule['require_min_amount']) ? $rule['require_min_amount'] : 0;
        $data['require_max_amount'] = isset($rule['require_max_amount']) ? $rule['require_max_amount'] : 0;
        $data['discount'] = isset($rule['discount']) ? $rule['discount'] : 0;
        $data['status'] = isset($rule['status']) ? $rule['status'] : 0;
        $data['created_time'] = isset($rule['created_time']) ? $rule['created_time'] : 0;
        $data['update_time'] = isset($rule['update_time']) ? $rule['update_time'] : 0;
        $rule_id = $this->CI->MDiscount_rule->create($data);
        return $rule_id;
    }
}
