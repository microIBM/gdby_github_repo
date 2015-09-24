<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * @description 支付流水相关接口
 * @author yuanxiaolin@dachuwang.com
 */
class Pay_bills extends MY_Controller {
    
    public function __construct () {
        parent::__construct();
        $this->load->model('MPay_bills', 'paybills');
    }
    
    /**
     * 添加支付流水接口
     * @method POST
     * @param array(order_id,pay_type,pay_status,pay_discount,transaction_id,trade_bo,totla_fee,cash_fee,full_data)
     * @see http://doku.dachuwang.com
     * @return json string
     * @author yuanxiaolin@dachuwang.com
     */
    public function add_bill () {
        
        $bills = $this->input->post('add_fields');
        if (! empty($bills)) {
            $bills_data = unserialize(str_replace("\\", '', $bills));
            // log_message('debug','bills_data:'.json_encode($bills_data));
        }
        
        if (! empty($bills_data) && ! empty($bills_data['full_data'])) {
            $bills_data['full_data'] = json_encode($bills_data['full_data']);
            $affects = $this->paybills->create($bills_data);
        } else {
            throw new Exception('bills data required,bug empty be given');
        }
        
        if ($affects) {
            $this->_return_json(array (
                'status' => 0,
                'msg' => $affects 
            ));
        } else {
            $this->_return_json(array (
                'status' => - 1,
                'msg' => 'update failed' 
            ));
        }
    }

}

/* End of file pay_bills.php */
/* Location: ./application/controllers/pay_bills.php */
