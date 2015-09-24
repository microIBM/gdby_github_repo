<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @description 从BD私海把超限和超时的客户放到公海
 * @author liudeen@dachuwang.com
 * @since 2015-06-13
 */
class Clear_customer_from_private_to_shared extends MY_Controller {
    public function __construct () {
        parent::__construct();
        //构造函数调用model或者library
        $this->load->model(
            ['MUser', 'MCustomer', 'MCustomer_potential', 'MCustomer_transfer_log']
        );
    }

    /*
     * @description 获取bd列表
     * @return array bd列表(get_lists获取的结果)
     */
    private function _get_bd() {
        $where = ['status' => 1];
        $fields = ['id', 'role_id', 'max_customer', 'max_potential_customer', 'customer_protect', 'potential_customer_protect'];
        return $this->MUser->get_lists($fields, $where);
    }

    /*
     * @description 获取归属于bd的新注册customer
     * @param int $bd_id, int $ctype bd的id,要获取的客户类型,0->潜在 1->正式
     * @return array
     */
    private function _get_customer_of_bd($bd_id, $ctype) {
        $model = $ctype === 0 ? 'MCustomer_potential' : 'MCustomer';
        $where = [
            'invite_id' => $bd_id,
            'in' => [
                'status' => [C('customer.status.new.code'), C('customer.status.undone.code')]
            ]
        ];
        $fields = ['id','role_id','last_transfer_time'];
        return $this->$model->get_lists($fields, $where);
    }

    /*
     * @description 检查客户是否需要被放到公海
     * @param array $customer, integer $ctype 包含客户信息的一个数组(get_one的返回值)
     * @return boolean
     */
    private function _check_if_need_clean(array $bd, array $customer, $ctype) {
        $protect_time = $ctype === 0 ? $bd['potential_customer_protect'] : $bd['customer_protect'];
        if($customer['last_transfer_time'] + $protect_time >= time()) {
            return TRUE;
        }
        return FALSE;
    }

    /*
     * @description 把客户放到公海
     * @param array $customer, integer $ctype 客户的信息,客户种类
     * @return boolean 操作结果
     */
    private function _put_customer_into_shared(array $customer, $ctype) {
        $model = $ctype === 0 ? 'MCustomer_potential' : 'MCustomer';
        $where = ['id' => $customer['id'], 'invite_id !=' => -1];
        $update = ['invite_id' => -1];
        if($this->$model->update_info($update, $where)) {
            return TRUE;
        }
        return FALSE;
    }

    /*
     * @description 记录操作转移日志
     * @param array $bd, array $customer, int $ctype bd的信息 客户的信息 操作类型(潜在/已开通)
     * @return void
     */
    private function _log_transfer_info(array $bd, array $customer, $ctype, $remark=NULL) {
        $method = $ctype === 0 ? 'record_potential' : 'record_customer';
        $dest = [
            'id' => -1, //公海
            'role_id' => 0 //公海的role_id
        ];
        $this->MCustomer_transfer_log->$method($bd, $dest, $customer['id'], NULL, $remark);
    }

    /*
     * @description 检查bd是否客户容量超限
     * @param array $bd, integer $ctype bd的信息,客户类型
     * @return integer 超出的数量，未超出则为0
     */
    private function _check_if_own_too_many_customer(array $bd, $ctype) {
        $customers_num = count($this->_get_customer_of_bd($bd['id'], $ctype));
        $param = $ctype === 0 ? 'max_potential_customer' : 'max_customer';
        if($customers_num > $bd[$param]) {
            return $customers_num - $bd[$param];
        }
        return 0;
    }

    /*
     * @description 从min和max中随机生成num个不同的数
     * @param integer $min,integer $max,integer $num 最小值，最大值，个数
     * @return array/boolean
     */
    private function _get_random_list($min, $max, $num) {
        if($num > $max-$min+1) {
            return FALSE;
        } else if($num === $max-$min+1) {
            $arr = [];
            for($i=$min; $i<=$max; $i++) {
                $arr[] = $i;
            }
            return $arr;
        }
        $arr = [];
        for($i=0; $i<$num; $i++) {
            while(1) {
                $tmp = rand($min, $max);
                if(!in_array($tmp, $arr)) {
                    $arr[] = $tmp;
                    break;
                }
            }
        }
        return $arr;
    }

    /*
     * @description 随机把客户放到公海
     * @param array $bd, integer $num, integer $ctype bd信息,需要随机放进公海的人数,客户类型
     * @return boolean 操作结果
     */
    private function _random_put_customer_into_shared(array $bd, $num, $ctype) {
        if($num <= 0 ) {
            return FALSE;
        }
        $customers = $this->_get_customer_of_bd($bd['id'], $ctype);
        //获取需要放到公海的客户下标的数组
        $list = $this->_get_random_list(0, count($customers)-1, $num);
        foreach($list as $item) {
            $this->_put_customer_into_shared($customers[$item], $ctype);
            $this->_log_transfer_info($bd, $customers[$item], $ctype, '由于客容量达上限,系统随机放到公海');
        }
        return TRUE;
    }

    /*
     * @description 把bd旗下超时或者超限的客户放到公海
     * @param array $bd,int $ctype bd信息,客户类型 0->潜在 1->正式
     * @return void
     */
    private function _daily_task(array $bd, $ctype) {
        //检查客户数是否超限
        $num = $this->_check_if_own_too_many_customer($bd, $ctype);
        //随机放$num个客户到公海
        $this->_random_put_customer_into_shared($bd, $num, $ctype);
        //获取bd旗下的客户
        $customers = $this->_get_customer_of_bd($bd['id'], $ctype);
        foreach($customers as $customer) {
            //检查客户是否需要被放到公海
            if($this->_check_if_need_clean($bd, $customer, $ctype)) {
                //把客户放到公海
                $this->_put_customer_into_shared($customer, $ctype);
                //记录日志
                $this->_log_transfer_info($bd, $customer, $ctype);
            }
        }
    }

    /*
     * @description 执行每日把bd旗下超时的客户放到公海的任务
     * @return void
     */
    public function do_task() {
        echo "正在执行...慢慢等吧...\n";
        $bds = $this->_get_bd();
        $num = count($bds);
        $current = 0;
        foreach($bds as $bd) {
            //潜在客户
            $this->_daily_task($bd, 0);
            //正式客户
            $this->_daily_task($bd, 1);
            $current ++;
            echo '已完成'.($current/$num * 100)."%\n";
        }
        echo "任务完成\n";
    }
}

/* End of file demo.php */
/* Location: ./application/controllers/demo.php */
