<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 商品关注服务
 * @author: longlijian@dachuwang.com
 */
class Follow_with_interest extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MFollow_with_interest',
                'MProduct',
                'MSku'
            )
        );
        $this->load->helper(array('product'));
        //商品的单位信息
        $this->units = C('unit');
        $this->uid_map_uinfo = array_column($this->units, NULL, 'id');
        $this->product_status = C('product');

        //前期加载慢，但是执行快
        $this->op_failed  = C('tips.code.op_failed');
        $this->op_success = C('tips.code.op_success');

        //商品关注状态
        $this->followed   = 1;
        $this->unfollowed = 0;
    }

    /*
     *@autuor longlijian@dachuwang.com
     *@description 错误返回
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
     *@description 成功返回
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
     *@description 创建一个关注信息
     *@return 返回关注信息id,错误返回－1
     */
    private function _create($follow) {
        //逻辑对偶性
        if ( ! (isset($follow['user_id']) AND isset($follow['product_id']) AND isset($follow['status']))) {
            return -1;
        }
        $insert_follow_info['user_id']    = $follow['user_id'];
        $insert_follow_info['product_id'] = $follow['product_id'];
        $insert_follow_info['status']     = $follow['status'];
        $insert_follow_info['created_time'] = $this->input->server('REQUEST_TIME');
        $insert_follow_info['updated_time'] = $this->input->server('REQUEST_TIME');
        $follow_id = $this->MFollow_with_interest->create($insert_follow_info);
        return $follow_id;
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 更新一条关注信息
     *@return 更新成功返回信息id,错误－1
     */
    private function _update($follow) {
        if ( ! (isset($follow['id']) AND isset($follow['status']))) {
            return -1;
        }
        $ret = $this->MFollow_with_interest->update($follow['id'], $follow);
    }


    /*
     *@author longlijian@dachuwang.com
     *@description 接受两个参数一个是商品的id，一个是商品关注状态
     *处理结果：如果这个用户存在这个商品的信息则更新，如果不存在就插入这条关注信息
     *@return 如果成功返回的是id号,如果失败返回－1
     */
    public function update_or_insert() {
        //这里不可使用逻辑对偶原则，除非确保优先级
        if ( ! empty($_POST['user_id']) AND ! empty($_POST['product_id']) AND isset($_POST['status'])) {
            $follow = $this->MFollow_with_interest->get_one(
                'id',
                array(
                    'user_id'    => $_POST['user_id'],
                    'product_id' => $_POST['product_id'],
                )
            );
            $update_data['user_id']    = $_POST['user_id'];
            $update_data['product_id'] = $_POST['product_id'];
            $update_data['status']     = $_POST['status'];
            $follow AND $update_data['id'] = $follow['id'];
            $msg = $follow ? '更新我的关注成功' : '创建我的关注成功';
            $ret = $follow ? $this->_update($update_data) : $this->_create($update_data);
            $this->_return_success($msg);
        } else {
            $msg   = array();
            $msg[] = empty($_POST['user_id'])    ? '用户id为空' : '';
            $msg[] = empty($_POST['product_id']) ? '关注的商品id为空' : '';
            $msg[] = empty($_POST['status'])     ? '关注的商品状态为空' : '';
            $this->_return_failed(implode('||', array_filter($msg)));
        }
    }

    /*
     *@author longlijian@dachuwang.com
     *@description 获取用户关注商品列表
     *@return 返回这个用户所有关注的商品id
     */
    public function get_follow_list_by_user() {
        if (empty($_POST['user_id'])) {
            $this->_return_failed('用户信息为空');
        }

        $product_ids = $this->MFollow_with_interest->get_lists(
            'product_id',
            array(
                'user_id' => $_POST['user_id'],
                'status'  => $this->followed
            )
        );

        $product_ids OR $this->_return_failed('该用户没有关注的商品');

        //获取所有的关注的商品信息
        $product_id_arr = array_column($product_ids, 'product_id');

        $products = $this->MProduct->get_lists(
            '*',
            array(
                'in' => array('id' => $product_id_arr),
            )
        );

        $this->load->library(array('product_lib'));
        $this->_return_success('获取关注商品信息成功', $products);
    }
}

/* End of file product.php */
/* Location: ./application/controllers/product.php */
