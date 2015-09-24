<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @description 共海私海API
 * @author liudeen@dachuwang.com
 */

class Shared_customer extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MPotential_customer',
                'MCustomer',
                'MUser',
                'MCustomer_transfer_log',
            )
        );
    }

    //封装结果输出
    private function _assemble_res($status=0, $msg='', $arr) {
        $result = array(
            'status' => $status,
            'msg' => $msg
        );
        if(isset($arr['total'])) {
            $result['total'] = $arr['total'];
            $result['list'] = $arr['res'];
        } else {
            $result['list'] = $arr;
        }
        return $result;
    }

    //封装错误输出
    private function _assemble_err($status=-1, $msg='error') {
        return array(
            'status' => $status,
            'msg' => $msg
        );
    }

    private function _check_login() {
        $cur = $this->userauth->current(FALSE);
        if(!$cur) {
            return FALSE;
        }
        return $cur;
    }

    private function _send_query($action, $pdata) {
        $result = $this->format_query('/shared_customer/'.$action, $pdata);
        if($result === null) {
            throw new Exception('接口调用失败');
        } else {
            return $result;
        }
    }

    public function potential_lists() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    public function new_register_lists() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    public function potential_change_shared() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            // 记录移交日志
            $customer = $this->MPotential_customer->get_one('id, invite_id', ['id' => $_POST['cid']]);
            $src_user = $this->MUser->get_one('*', ['id' => $customer['invite_id']]);
            $dest_user = ['id' => C('customer.public_sea_code'), 'role_id' => 0];
            $this->MCustomer_transfer_log->record_potential(C('customer.public_sea_code'), $_POST['cid'], $cur);

            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    public function new_register_change_shared() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            // 记录移交日志
            $this->MCustomer_transfer_log->record(C('customer.public_sea_code'), $_POST['cid'], $cur);
            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    public function potential_change_private() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            // 记录移交日志
            $src_user = ['id' => C('customer.public_sea_code'), 'role_id' => 0];
            $dest_user = $cur;
            $this->MCustomer_transfer_log->record_potential($cur['id'], $_POST['cid'], $cur);

            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }

    public function new_register_change_private() {
        try {
            $cur = $this->_check_login();
            if(!$cur) {
                throw new Exception('未登录');
            }
            // 记录移交日志
            $this->MCustomer_transfer_log->record($cur['id'], $_POST['cid'], $cur);
            $_POST['bd_id'] = $cur['id'];
            $result = $this->_send_query(__FUNCTION__, $_POST);
            $this->_return_json($result);
        } catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }
}
/* End of file user.php */
/* Location: :./application/controllers/user.php */
