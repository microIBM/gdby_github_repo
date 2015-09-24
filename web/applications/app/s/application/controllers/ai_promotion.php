<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Ai_promotion extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLocation',
                'MAi_promotion',
            )
        );
        $this->load->library(
            array(
                'Promotion_activity_rule'
            )
        );
    }

    //预留接口：更新活动规则
    public function update() {
    }

    public function create() {
        //根据传递过来的数据创建活动，创建活动之后更新活动规则
        $req_time = $this->input->server('REQUEST_TIME');
        $data = array(
            'title'               => $_POST['title'],
            'promotion_type'      => $_POST['promotion_type'],
            'site_id'             => $_POST['site_id'],
            'location_id'         => $_POST['location_id'],
            'priority'            => $_POST['priority'],
            'max_times'           => $_POST['max_times'],
            'user_tags'           => $_POST['user_tags'],
            'start_time'          => $_POST['start_time'],
            'end_time'            => $_POST['end_time'],
            'latest_deliver_time' => $_POST['latest_deliver_time'],
            'pay_type'            => $_POST['pay_type'],
            'created_time'        => $req_time,
            'update_time'         => $req_time,
        );
        $promotion_id = $this->MPromotion->create($data);
        //如果活动创建成功了则，再去根据活动类型创建活动规则
        $rule_type = $_POST['rule_type'];
        if ($promotion_id && $rule_type) {
            switch($rule_type) {
                case C('promotion_system.full_minus.type'):
                    $rule['title'] = $_POST['rule_title'];
                    $rule['promotion_id'] = $promotion_id;
                    $rule['require_amount'] = $_POST['require_amount'];
                    $rule['minus_amount'] = $_POST['minus_amount'];
                    $rule['sku_number'] = $_POST['sku_number'];
                    $rule['category_id'] = $_POST['category_id'];
                    $rule['min_quantity'] = $_POST['min_quantity'];
                    $rule['status'] = $_POST['rule_status'];
                    $rule['created_time'] = $_POST['created_time'];
                    $rule['update_time'] = $_POST['update_time'];
                    $this->Promotion_activity_rule->_create_full_minus_rule($rule);
                    break;
                case C('promotion_system.full_gift.type'):
                    $rule['title'] = $_POST['title'];
                    $rule['promotion_id'] = $promotion_id;
                    $rule['require_amount'] = $_POST['require_amount'];
                    $rule['min_quantity']   = $_POST['min_quantity'];
                    $rule['max_quantity']   = $_POST['max_quantity'];
                    $rule['category_id']    = $_POST['category_id'];
                    $rule['sku_number']     = $_POST['sku_number'];
                    $rule['gift_sku_number'] = $_POST['gift_sku_number'];
                    $rule['gift_coupon_id']  = $_POST['gift_coupon_id'];
                    $rule['status']          = $_POST['rule_status'];
                    $rule['created_time']    = $_POST['created_time'];
                    $rule['update_time']     = $_POST['update_time'];
                    $this->Promotion_activity_rule->_create_full_gift_rule($rule);
                    break;
                case C('promotion_system.immediate_minus.type'):
                    $rule['title'] = $_POST['title'];
                    $rule['promotion_id'] = $promotion_id;
                    $rule['minus_amount'] = $_POST['minus_amount'];
                    $rule['sku_number']   = $_POST['sku_number'];
                    $rule['category_id']  = $_POST['category_id'];
                    $rule['min_quantity'] = $_POST['min_quantity'];
                    $rule['max_quantity'] = $_POST['max_quantity'];
                    $rule['status']       = $_POST['rule_status'];
                    $rule['created_time'] = $_POST['created_time'];
                    $rule['update_time']  = $_POST['update_time'];
                    $this->Promotion_activity_rule->_create_immediate_minus_rule($rule);
                    break;
                case C('promotion_system.discount.type'):
                    $rule['title'] = $_POST['title'];
                    $rule['promotion_id'] = $promotion_id;
                    $rule['require_min_amount'] = $_POST['require_min_amount'];
                    $rule['require_max_amount'] = $_POST['require_max_amount'];
                    $rule['sku_number']         = $_POST['sku_number'];
                    $rule['discount']           = $_POST['discount'];
                    $rule['category_id']        = $_POST['category_id'];
                    $rule['status']             = $_POST['rule_status'];
                    $rule['created_time']       = $_POST['created_time'];
                    $rule['update_time']        = $_POST['update_time'];
                    $this->Promotion_activity_rule->_discount($rule);
                    break;
                default:
                    $this->_return_json(
                        array(
                            'status' => C('tips.code.op_success'),
                            'msg'    => '创建失败，暂不支持次活动类型'
                        )
                    );
            }
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_success'),
                    'msg'    => '活动发布成功，默认未上线，请根据需要安排上线。'
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg'    => '创建活动失败'
                )
            );
        }
    }

    public function lists() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : array('created_time' => 'DESC');
        $page = $this->get_page();
        $total = $this->MAi_promotion->count($where);
        $data = $this->MAi_promotion->get_lists(
            '*',
            $where,
            $orderBy,
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $this->_deal_data($data);
            $response = array(
                'status' => C('tips.code.op_success'),
                'total' => $total,
                'list' => $data
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_success'),
                'total'  => 0,
                'list'   => array(),
                'msg'    => '没有数据'
            );
        }
        $this->_return_json($response);
    }

    //todo
    //完善规则显示
    private function _deal_data(&$data) {
        $locations = $this->MLocation->get_lists('id, name', array('upid' => 0));
        $sites = C("site.code");
        $location_map_name = array_column($locations, 'name', 'id');
        $site_map_name = array_column($sites, 'name', 'id');
        foreach($data as &$show_item) {
            $show_item['act_start_time'] = date('Y-m-d', $show_item['start_time']);
            $show_item['act_end_time']   = date('Y-m-d', $show_item['end_time']);
            $show_item['latest_deliver_time'] = date('Y-m-d', $show_item['latest_deliver_time']);
            $show_item['site_cn'] = ! empty($site_map_name[$show_item['site_id']]) ? $site_map_name[$show_item['site_id']]: '全网';
            $show_item['location_cn'] = !empty($location_map_name[$show_item['location_id']]) ? $location_map_name[$show_item['location_id']] : '全地区';
            $show_item['updated_time'] = date('Y-m-d H:i:s', $show_item['update_time']);
        }
        unset($show_item);
    }
}

/* End of file promotion.php */
/* Location: ./application/controllers/promotion.php */
