<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @description CRM对账管理
 * @author liudeen@dachuwang.com
 * @since 2015-07-18
 */

class Accheck extends MY_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MCustomer']);
    }

    public function lists()
    {
        if(empty($_POST['customer_ids'])) {
            $this->assembleError('参数不全');
        }
        foreach($_POST['customer_ids'] as $val) {
            if(!$this->isParentAccount($val)) {
                $this->assembleError($val.'不是子账号');
            }
        }
        $allow_lists = ['area','bd', 'billing_length', 'expire', 'start_time', 'end_time', 'currentPage','itemsPerPage', 'status', 'customer_ids'];
        foreach($_POST as $key => $val) {
            if(!in_array($key, $allow_lists) || empty($_POST[$key])) {
                unset($_POST[$key]);
            }
        }
        $return = $this->format_query('/billing/'.__FUNCTION__, $_POST);
        $this->_return_json($return);
    }

    public function view() {
        if(empty($_POST['id'])) {
            $this->assembleError('参数不全');
        }
        $return = $this->format_query('/billing/'.__FUNCTION__, ['id' => intval($_POST['id'])]);
        $this->_return_json($return);
    }

    public function showConstitute() {
        if(empty($_POST['id'])) {
            $this->assembleError('参数不全');
        }
        $return = $this->format_query('/billing/get_orders_of_store', ['id' => intval($_POST['id'])]);
        $this->_return_json($return);
    }

    private function assembleResults($msg, $list) {
        $this->_return_json([
            'status' => C('status.req.success'),
            'msg' => $msg,
            'list' => $list
        ]);
    }

    private function assembleError($msg) {
        $this->_return_json([
            'status' => C('status.req.failed'),
            'msg' => $msg
        ]);
    }

    private function isParentAccount($id) {
        $cus_info = $this->MCustomer->get_one(['account_type'],['id'=>intval($id)]);
        $account_type = $cus_info['account_type'];
        $parent_type = C('customer.account_type.parent.value');
        if(intval($account_type) === intval($parent_type)) {
            return true;
        }
        return false;
    }
}

/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
