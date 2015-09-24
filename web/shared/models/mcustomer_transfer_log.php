<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户移交日志操作model
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-05-05
 */
class MCustomer_transfer_log extends MY_Model {
    use MemAuto;
    private $table = 't_customer_transfer_log';
    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 记录客户移交日志
     * @author yugang@dachuwang.com
     * @since 2015-05-07
     */
    public function record($user_id, $cids, $operator = NULL, $remark = '') {
        if (is_array($cids)) {
            $cid_arr = $cids;
        } else {
            $cid_arr = explode(',', $cids);
        }
        $customer_list = $this->MCustomer->get_lists('*', array('in' => array('id' => $cid_arr)));
        // 获取客户所属销售的字典
        $src_bd_ids = array_column($customer_list, 'invite_id');
        $src_am_ids = array_column($customer_list, 'am_id');
        $src_sale_ids = array_merge($src_bd_ids, $src_am_ids);
        $src_sale_ids[] = $user_id;
        $src_sale_ids = array_filter($src_sale_ids);
        $src_sale_ids = array_unique($src_sale_ids);
        $src_sale_list = $this->MUser->get_lists('*', array('in' => array('id' => $src_sale_ids)));
        if(!empty($src_sale_list)) {
            $sale_dict = array_combine(array_column($src_sale_list, 'id'), $src_sale_list);
        }else{
            $sale_dict = [];
        }
        $log_list = array();
        foreach ($customer_list as $customer) {
            if($customer['status'] == C('customer.status.allocated.code')) {
                $src_id = $customer['am_id'];
            } else {
                $src_id = $customer['invite_id'];
            }

            $data = array(
                'cid'          => $customer['id'],
                'src_id'       => $src_id,
                'src_role'     => isset($sale_dict[$src_id]) ? $sale_dict[$src_id]['role_id'] : 0,
                'dest_id'      => $user_id,
                'dest_role'    => isset($sale_dict[$user_id]) ? $sale_dict[$user_id]['role_id'] : 0,
                'operator'     => !empty($operator) ? $operator['name'] : '',
                'operator_id'  => !empty($operator) ? $operator['id'] : 0,
                'log_ip'       => !empty($operator) ? $operator['ip'] : 0,
                'remark'       => $remark,
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
                'status'       => C('status.common.success'),
                'ctype'        => C('customer_transfer_log.ctype.customer.code'),
            );
            $log_list[] = $data;
        }
        $result = 0;
        if (!empty($log_list)) {
            $result = $this->create_batch($log_list);
        }
        return $result;
    }

    /**
     * 记录潜在客户移交日志
     * @author yugang@dachuwang.com
     * @since 2015-06-12
     */
    public function record_potential($user_id, $cids, $operator = NULL, $remark = '') {
        $log_list = array();
        if (is_array($cids)) {
            $cid_arr = $cids;
        } else {
            $cid_arr = explode(',', $cids);
        }
        $customer_list = $this->MPotential_customer->get_lists('*', array('in' => array('id' => $cid_arr)));
        // 获取客户所属销售的字典
        $src_sale_ids = array_column($customer_list, 'invite_id');
        $src_sale_ids[] = $user_id;
        $src_sale_ids = array_filter($src_sale_ids);
        $src_sale_ids = array_unique($src_sale_ids);
        $src_sale_list = $this->MUser->get_lists('*', array('in' => array('id' => $src_sale_ids)));
        if(!empty($src_sale_list)) {
            $sale_dict = array_combine(array_column($src_sale_list, 'id'), $src_sale_list);
        }else{
            $sale_dict = [];
        }
        foreach ($customer_list as $customer) {
            $src_id = $customer['invite_id'];
            $data = array(
                'cid'          => $customer['id'],
                'src_id'       => $src_id,
                'src_role'     => isset($sale_dict[$src_id]) ? $sale_dict[$src_id]['role_id'] : 0,
                'dest_id'      => $user_id,
                'dest_role'    => isset($sale_dict[$user_id]) ? $sale_dict[$user_id]['role_id'] : 0,
                'operator'     => !empty($operator) ? $operator['name'] : '',
                'operator_id'  => !empty($operator) ? $operator['id'] : 0,
                'log_ip'       => !empty($operator) ? $operator['ip'] : 0,
                'remark'       => $remark,
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
                'status'       => C('status.common.success'),
                'ctype'        => C('customer_transfer_log.ctype.potential_customer.code'),
            );
            $log_list[] = $data;
        }

        $result = 0;
        if (!empty($log_list)) {
            $result = $this->create_batch($log_list);
        }
        return $result;
    }

    /**
     * 记录客户移交日志
     * @author liudeen@dachuwang.com
     * @since 2015-06-13
     */
    public function record_customer($src_user, $dest_user, $cid, $operator = NULL, $remark = '') {
        $data = array(
            'cid'          => $cid,
            'src_id'       => empty($src_user) ? 0 : $src_user['id'],
            'src_role'     => empty($src_user) ? 0 :$src_user['role_id'],
            'dest_id'      => empty($dest_user) ? 0 : $dest_user['id'],
            'dest_role'    => empty($dest_user) ? 0 : $dest_user['role_id'],
            'operator'     => empty($operator) ? '' : $operator['name'],
            'operator_id'  => empty($operator) ? 0 : $operator['id'],
            'log_ip'       => empty($operator) ? 0 : $operator['ip'],
            'remark'       => $remark,
            'created_time' => $this->input->server('REQUEST_TIME'),
            'updated_time' => $this->input->server('REQUEST_TIME'),
            'status'       => C('status.common.success'),
            'ctype'        => C('customer_transfer_log.ctype.customer.code'),
        );

        return $this->create($data);
    }
}

/* End of file mcustomer_transfer_log.php */
/* Location: :./application/models/mcustomer_transfer_log.php */
