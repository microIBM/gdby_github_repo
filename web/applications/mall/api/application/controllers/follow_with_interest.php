<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 用户关注（商品)
 * @author: longlijian@dachuwang.com
 */
class Follow_with_interest extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(array('product_price', 'product_lib'));
        //操作状态
        $this->op_failed  = C('tips.code.op_failed');
        $this->op_success = C('tips.code.op_success');
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 错误返回，根据指定的信息返回
     */
    private function _return_failed($msg = '') {
        $this->_return_json(
            array(
                'status' => $this->op_failed,
                'msg'    => $msg
            )
        );
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 成功返回，可指定返回信息和结果
     */
    private function _return_success($msg = '', $info = array()) {
        $this->_return_json(
            array(
                'status' => $this->op_success,
                'msg'    => $msg,
                'info'   => $info
            )
        );
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 传入商品id,和商品关注状态,如果有就更新如果没有就插入
     */
    public function update_or_insert() {
        $cur  = $this->userauth->current(TRUE);
        //只有登录了才能更新或者插入关注

        $cur OR $this->_return_failed('关注之前请先登录');

        if ( ! empty($_POST['product_id']) AND isset($_POST['status'])) {
            $data['user_id']    = $cur['id'];
            $data['product_id'] = $_POST['product_id'];
            $data['status']     = $_POST['status'];
            $result = $this->format_query(
                'follow_with_interest/update_or_insert',
                $data
            );
            //基础服务那边要根据情况返回执行状态，信息为更新成功或者创建成功
            $result['status'] == $this->op_failed ? $this->_return_failed($result['msg']) : $this->_return_success($result['msg']);
        } else {
            $msg   = array();
            $msg[] = empty($_POST['product_id']) ? '没有给定关注的商品' : '';
            $msg[] = isset($_POST['status'])     ? '没有给定商品的状态信息' : '';
            $this->_return_failed(implode('||', array_filter($msg)));
        }
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 获取用户关注的商品列表,个人中心我的关注接口
     */
    public function get_follow_list_by_user() {
        $cur = $this->userauth->current(TRUE);

        $cur OR $this->_return_failed('请登录以后在查询');

        $data['user_id'] = $cur['id'];
        $ret = $this->format_query(
            'follow_with_interest/get_follow_list_by_user',
            $data
        );
        $ret OR $this->_return_failed('查询用户关注数据后台返回为空');
        //根据返回的状态把结果返回给前端
        $ret['status'] == $this->op_failed AND $this->_return_failed($ret['msg']);
        if(!empty($ret['info'])) {
            $ret['info'] = $this->product_price->get_rebate_price($ret['info'], $cur['id'], FALSE);
            $product_list = $this->product_lib->set_product_fields($ret['info']);
            $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
            $this->product_lib->set_default_check_storage_list($check_storage_info, $ret['info']);
            $ret['info'] = $this->product_lib->format_shop_product_list($ret['info']);
        }
        $this->_return_success($ret['msg'], $ret['info']);
    }
}
/* End of file product.php */
/* Location: :./application/controllers/product.php */
