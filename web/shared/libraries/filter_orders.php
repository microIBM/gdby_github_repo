<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 过滤采购商,规则制订
 * 方案：可疑维度
 *
 */
class Filter_orders {
    // 默认值
    private $_abnormal_price = array(
        'lt'    => array(
            'val'   => 2000000,
            'msg'   => '20000元以上大额订单'
        ),
        'gt'   => array(
            'val'   => 1000,
            'msg'   => '10元以下订单小额'
        )
    );
    // 根据不同的市场来定位对应的可疑订单规则
    private $_abnormal_money_arr = array();

    public function __construct () {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MOrder',
                'MUser'
            )
        );
        $this->_abnormal_money_arr = C('abnormal_rules.order.money.market_id');
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 通过订单来进行异常分析
     */
    public function users() {
        // 加载规则
        $orderBy = array(
            'uid'   => 'DESC'
        );
        // 按时间查询，默认是三天内的
        $current_time = $this->CI->input->server('REQUEST_TIME');
        $where = array(
            'status !='     => C('status.order.del'),
            'mindate >=' => $current_time - 3 * 24 * 60 * 60,
            'mindate <=' => $current_time
        );
        $data = $this->CI->MOrder->get_lists('*', $where, $orderBy);
        // 统计可疑uid
        $uids = array();
        // 查出这些这些订单的supply_uid,然后根据对应的市场来筛选
        if($data) {
            $supply_ids = array_unique(array_column($data, 'supply_uid'));
            $supply_info = $this->CI->MUser
                ->get_lists(
                    'id, market_id',
                    array(
                        'in' => array(
                            'id'   => $supply_ids
                        )
                    )
                );
            if($supply_info) {
                $supply_id_col = array_column($supply_info, 'id');
                // 以id为建
                $supply_combin_col = array_combine($supply_id_col, $supply_info);
                // 需要统计几笔可疑订单
                foreach($data as $v) {
                    $v['market_id'] = $supply_combin_col[$v['supply_uid']]['market_id'];
                    $result = $this->is_abnormal($v);
                    if($result['status']) {
                        // 异常订单那么就将用户归纳到这里面
                        $uids[$v['uid']][] = array(
                            'msg'   => $result['msg'],
                            'order_id'  => $v['id'],
                            'id'    => $v['uid']
                        );
                    }
                }
            }
        }
        return $uids;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测是否金额异常
     * 应该可以支持多种适配了，
     * 异常就显示几种异常情况
     */
    public function is_abnormal($order_info) {
        $money = $order_info['total_price'];
        $market_id = $order_info['market_id'];
        $data = $this->_money($money, $market_id);
        return $data;
    }
    // 规则一金额异常
    // 1.在新发地或其他一批生成的20000元或以上的订单
    // 2.在上农批或其他二批生成的5000元或以上的订单
    // 3.所有10元以下的订单
    private function _money($money, $market_id) {
        $abnormal = $this->_abnormal_money_arr;
        $money_arr = $this->_abnormal_price;
        if($money) {
            // market_id 是否开通
            if(isset($abnormal[$market_id])) {
                $money_arr['lt']= $abnormal[$market_id]['lt'];
            }
            $money_arr['gt'] = $money_arr['gt'];
            if($money <= $money_arr['gt']['val'] ) {
                return array(
                    'status' => TRUE,
                    'msg'    => $money_arr['gt']['msg']
                );
            }
            if($money >= $money_arr['lt']['val']) {
                return array(
                    'status'  => TRUE,
                    'msg'     => $money_arr['lt']['msg']
                );
            }
            return array(
                'status' => FALSE,
                'msg'    => ''
            );
        } else {
            return array(
                'status' => TRUE,
                'msg'    => $money_arr['gt']['msg']
            );
        }
    }
}
/* End of file filter_orders.php */
/* Location: ./application/library/filter_orders.php */
