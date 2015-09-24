<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
class Billing_job extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'MBilling',
            'MOrder',
            'MCustomer',
            'MSuborder',
            'MOrder_detail',
            'MBilling_log'
        ));
        $this->load->library(array(
            'redisclient'
        ));
    }

    /**
     * 产生账单job
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function generate_billing()
    {
        set_time_limit(0);
        // only generate once a day
        // if (!$this->_can_execute_job()) {
        // //TODO
        // return;
        // }
        $date = date('Y-m-d');
        // 如果cli命令行传递了时间，则按照该时间来生成对账单
        if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 3) {
            $date = $_SERVER['argv'][3];
        }
        
        $unbalance_lists = $this->MBilling->get_lists("*", array("status" => C("billing.status.disabled.code")));
        
        $current_week = date('l', strtotime($date));
        $current_day = date('d', strtotime($date));
        $customer_list = $this->MCustomer->get_lists(array(
            'id',
            'mobile',
            'billing_cycle',
            'check_date',
            'pay_date'
        ), array(
            'account_type' => C('customer.account_type.parent.value'),
            'billing_cycle != ' => "none",
            'billing_cycle != ' => '',
            'status >' => C('customer.status.invalid.code'),
            'is_active' => 1
        ));
//         $customer_ids = array_column($customer_list, 'id');
//         $max_lists = $this->MBilling->get_lists(array("max(id) as max_id", "customer_id"), array('in'=> array('customer_id'=>$customer_ids)), array(), array('customer_id'));
//         $max_ids = array_column($max_lists, 'max_id');

        $customer_list_of_key = array();
        foreach ($customer_list as $customer) {
            $customer_list_of_key[$customer['id']] = $customer;
        }
        $time = strtotime($date);
        // determine if arrive the time of generate billing
        foreach ($unbalance_lists as $key => $value) {
            $billing_cycle = trim($value['billing_cycle']);
            $theory_start = $value['theory_start'];
            $theory_end = $value['theory_end'];
            $start_time = $value['start_time'];
            $end_time = $value['end_time'];
            $check_date = $value['check_date'];
            $end_time = strtotime("+1 day", $end_time);
            $expire_time = $value['expire_time'];
            $billing_id = $value['id'];
            $pay_date = $customer_list_of_key[$value['customer_id']]['pay_date'];
            $customer_mobile = $customer_list_of_key[$value['customer_id']]['mobile'];
            $customer_id =  $value['customer_id'];
            
            if ($time > $value['end_time']) {
                $this->_calc_price($billing_id,$customer_mobile, $customer_id, $start_time, $end_time);
            }
            
            if ($billing_cycle == 'day') {
                if ($time <=  $theory_end) {
                    continue;
                }
                $new_theory_start = strtotime("+1 day", $theory_start);
                $new_theory_end = strtotime("+1 day", $theory_end);
                $expire_time = strtotime("+2 day", $expire_time);
                $this->_persist_data($new_theory_start, $new_theory_end,  $new_theory_start,  $new_theory_end, $billing_cycle, $customer_id,  $expire_time, $check_date, $pay_date);
            }elseif ($billing_cycle == 'week') {
                if ($value['end_time'] >= $time) {
                    continue;
                }
                if ($this->get_week($current_week) == $this->get_week($check_date)) {
                    $new_theory_start = strtotime("+7 day", $theory_start);
                    $new_theory_end = strtotime("+7 day", $theory_end);
                    $expire_time = strtotime("+7 day", $expire_time);
                    $this->_persist_data($new_theory_start, $new_theory_end,  $new_theory_start,  $new_theory_end, $billing_cycle, $customer_id,  $expire_time, $check_date, $pay_date);
                }
            }elseif ($billing_cycle == 'half_month') {
                if ($value['end_time'] >= $time) {
                    continue;
                }
                $check_date_array = explode(",", $check_date);
                $first_check_date = $check_date_array[0];
                $next_check_date = $check_date_array[1];
                if ((trim($first_check_date) == trim($current_day)) || (trim($next_check_date) == trim($current_day))) {
                    $curr_year = date('Y', strtotime($date));
                    $curr_month = date('m', strtotime($date));
                    $first_check_date_a = strtotime($curr_year . "-" . $curr_month . "-" . $first_check_date);
                    $next_check_date_a = strtotime($curr_year . "-" . $curr_month . "-" . $next_check_date);
                    if ($current_day == $first_check_date) {
                        $new_theory_start = $first_check_date_a;
                        $new_theory_end = strtotime("-1 day", $next_check_date_a);
                    } else if ($current_day == $next_check_date) {
                        $new_theory_start = $next_check_date_a;
                        $new_theory_end = strtotime("+1 month -1 day", $first_check_date_a);
                    }
                    $check_time = strtotime("+1 day", $new_theory_end);
                    $expire_time = strtotime("+$pay_date day", $check_time);
                    $this->_persist_data($new_theory_start, $new_theory_end,  $new_theory_start,  $new_theory_end, $billing_cycle, $customer_id,  $expire_time, $check_date, $pay_date);
                }
            }elseif ($billing_cycle == 'month') {
                if ($value['end_time'] >= $time) {
                    continue;
                }
                // 正常执行客户的账期
                if (trim($current_day) == trim($check_date)) {
                    $this->_calc_price($billing_id,$customer_mobile, $customer_id, $start_time, $end_time);
                    $new_theory_start = strtotime("+1 month", $theory_start);
                    $new_theory_end = strtotime("+1 month", $theory_end);
                    $expire_time = strtotime("+1 month", $expire_time);
                    $this->_persist_data($new_theory_start, $new_theory_end,  $new_theory_start,  $new_theory_end, $billing_cycle, $customer_id,  $expire_time, $check_date, $pay_date);
                }
            }
//             $billing_num_info = $this->MBilling->get_one(array(
//                 "max(billing_num) as zero_point"
//             ), array());
//             if (isset($billing_num_info['zero_point']) && (trim($billing_num_info['zero_point'] != ''))) {
//                 $start_point = intval($billing_num_info['zero_point']) + 1;
//             } else {
//                 $start_point = 1;
//             }
            // 检查该时间段内是否已经生成了账单
//             $exists_billing = $this->MBilling->get_one(array(
//                 'id'
//             ), array(
//                 "customer_id" => $customer_id,
//                 "start_time" => $new_theory_start,
//                 "end_time" => $new_theory_start
//             ));
//             if ($exists_billing > 0) {
//                 return;
//             }
        }

        $this->_change_billing_expire($time);
        // $this->_lock_execute_job();
    }
    
    private function _persist_data($new_theory_start, $new_theory_end,  $new_theory_start,  $new_theory_end, $billing_cycle, $customer_id,  $expire_time, $check_date, $pay_date){
        $createdData = array(
            'start_time' => $new_theory_start,
            'end_time' => $new_theory_end,
            'theory_start' => $new_theory_start,
            'theory_end' => $new_theory_end,
            'status' => C('billing.status.disabled.code'),
            'updated_time' => time(),
            'created_time' => time(),
            'billing_cycle' => $billing_cycle,
            'billing_num' => '',
            'customer_id' => $customer_id,
            'total_price' => 0,
            'expire_time' => $expire_time,
            'expire_status' => 0,
            'check_date' => $check_date,
            'pay_date' => $pay_date
        );
        $this->MBilling->create($createdData);
    }
    
    private function  _calc_price($billing_id,$customer_mobile, $customer_id, $start_time, $end_time) {
        $child_ids = $this->MCustomer->get_lists(array(
            'id'
        ), array(
            'parent_mobile' => $customer_mobile,
            'status >' => C('customer.status.invalid.code')
        ));
        $child_ids = array_column($child_ids, 'id');
        array_push($child_ids, $customer_id);
        $order_info = $this->MSuborder->get_one(array(
            "sum(deal_price) as sum_price"
        ), array(
            "deliver_date >=" => $start_time,
            "deliver_date <" => $end_time,
            'in' => array(
                'user_id' => $child_ids
            )
        ));

        if ($order_info['sum_price'] >0) {
            $this->MBilling->update($billing_id, array('total_price'=> $order_info['sum_price'], 'status'=>C('billing.status.unpay.code'), 'updated_time'=> time()));
        }else{
            $this->MBilling->update($billing_id, array('total_price'=> $order_info['sum_price'], 'status'=>C('billing.status.invalid.code'), 'updated_time'=> time()));
        }
    }

    /**
     * 更改账单预期未付状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    protected function _change_billing_expire($time)
    {   
        $last_month_time = strtotime(date('Y-m-d', strtotime('-1 month -5 day', $time)));
        $billing_list = $this->MBilling->get_lists(array(
            'id',
            'expire_status',
            'expire_time',
            'expire_tag'
        ), array(
            'created_time >=' => $last_month_time,
            'in' => array('status'=> array(C('billing.status.unpay.code'), C('billing.status.prepay.code')))
        ));
        foreach ($billing_list as $key => $value) {
            $expire_time = $value['expire_time'];
            $expire_status = $value['expire_status'];
            $expire_tag = $value['expire_tag'];
            $id = $value['id'];
            if (($expire_time <= $time) && ($expire_tag != C('billing.expire_tag.tag_expire.code'))) {
                $this->MBilling->update($id, array(
                    'expire_tag' => C('billing.expire_tag.tag_expire.code'),
                    'expire_status' => C('billing.expire_status.yes.code'),
                    'updated_time' => $time
                ));
            }
        }
    }

    private function _read_redis_data($customer_id)
    {
        $redis = $this->redisclient;
        $billing_cycle_info = $redis->hget('billing_cycle_change', $customer_id);
        $billing_cycle_info = json_decode($billing_cycle_info, true);
        return $billing_cycle_info;
    }

    /**
     * 账单状态改变记录器
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @param int $billing_id
     * @param string $content
     * @param int $author_id
     * @param string $author_name
     * @param string $role_name
     * @param int $role_id
     * @param bool $auto
     */
    private function _billing_status_logger($billing_id, $content, $author_id, $author_name, $role_name, $role_id, $auto)
    {
        $time = time();
        $create_data = array(
            'billing_id' => $billing_id,
            'content' => $content,
            'author_id' => $author_id,
            'author_name' => $author_name,
            'role_name' => $role_name,
            'role_id' => $role_id,
            'updated_time' => $time,
            'created_time' => $time,
            'auto' => $auto
        );
        $this->MBilling_log->create($create_data);
    }

    /**
     * 获取数字的一周当中的第几天
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     * @param string $week
     */
    protected function get_week($week)
    {
        $local_week = array(
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6,
            'Sunday'    => 7
        );
        return $local_week[$week];
    }

    private function _can_execute_job()
    {
        $redis = $this->redisclient;
        $lock = $redis->get('generate_billing_lock');
        if (isset($lock) && $lock != '') {
            $time = strtotime(date('Y-m-d'));
            if ($lock == $time) {
                return false;
            }
        }
        return true;
    }

    private function _lock_execute_job()
    {
        $redis = $this->redisclient;
        $time = strtotime(date('Y-m-d'));
        $lock = $redis->set('generate_billing_lock', $time);
        return true;
    }
}
