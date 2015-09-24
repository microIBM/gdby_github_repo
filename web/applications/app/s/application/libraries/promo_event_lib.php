<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author caochunhui@dachuwang.com
 * @description 促销活动的library
 */

class Promo_event_lib {

    public function __construct () {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MPromo_event',
                'MPromo_event_rule',
            )
        );
    }

    /**
     * @description 获取有效当前的活动
     * @modified by caiyilong@dachuwang.com AT 2015-05-12
     */
    public function get_event($param = array()) {
        $event = array();
        $where = array();
        $event = $this->CI->MPromo_event->get_one(
            '*',
            array(
                'id'            => $event_id,
                'status'        => 1,
                //'start_time <=' => $request_time,
                //'end_time >='   => $request_time
            )
        );
        if(empty($event)) {
            return $event;
        }

        $param = array(
            'event_id' => $event_id
        );

        $rules = $this->event_rules($param);
        $event['rules'] = $rules;
        return $event;
    }

    /**
     * @description 获取指定活动的rule_map
     */
    public function event_rules($param = array()) {
        $event_ids = $param['event_id'];
        if(is_array($event_ids)) {
            $rules = $this->CI->MPromo_event_rule->get_lists(
                '*',
                array(
                )
            );
        } else {
            $rules = $this->CI->MPromo_event_rule->get_lists(
                '*',
                array(
                )
            );
        }

        $rule_map = [];
        foreach($rules as $rule) {
            //json字段
            $rule['rule_json'] = json_decode($rule['rule_json'], TRUE);
            if($rule['rule_type'] == C('promo_event_rule.type.meet_amount_and_minus.code')) {
                $rule['rule_json']['return_profit'] = $rule['rule_json']['return_profit']/100;
                $rule['rule_json']['require_rmb'] = $rule['rule_json']['require_rmb']/100;
            }
            $event_id = $rule['promo_event_id'];
            if(isset($rule_map[$event_id])) {
                $rule_map[$event_id][] = $rule;
            } else {
                $rule_map[$event_id] = array(
                    $rule
                );
            }
        }
        return $rule_map;
    }

}

/* End of file promo_event.php */
/* Location: ./application/controllers/promo_event.php */
