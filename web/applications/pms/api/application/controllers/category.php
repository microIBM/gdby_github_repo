<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的类型控制器
 * @author: liaoxianwen@dachuwang.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Category extends MY_Controller {
    private $_page_size = 10;
    public function __construct() {
        parent::__construct();
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description
     */
    public function lists() {
        $post = $this->post;
        $return_data = $this->format_query('/category/lists', $post);
        // 获取规格
        $property_data = $this->format_query('/property/lists');
        // 整合规格
        if(isset($return_data['list'])) {
            $return_data['list']['units'] = C('unit');
        }
        $this->_return_json($return_data);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 查看子分类
     */
    public function get_category_list() {
        $page = empty($this->post['page']) ? 1 : $this->post['page'];
        if(isset($this->post['upid'])) {
            $this->post['upid'] = intval($this->post['upid']);
        } else {
            $this->post['upid'] = 0;
        }
        if(isset($this->post['name'])) {
            $where['like'] = array('name'   => $this->post['name']);
        }
        if(!isset($this->post['seccate'])) {
            $where['upid'] = $this->post['upid'];
        }
        $tips = $this->format_query('/category/get_child_list', array('where' => $where, 'page' => $page));
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 分类添加
     */
    public function save() {
        //$this->check_validation('category', 'create');
        // $cur = $this->userauth->current();
        $data = $this->format_query('/category/create', $this->post);
        $this->_return_json($data);
    } /**
     * @author: liaoxianwen@dachuwang.com
     * @description 更新某个分类信息
     */
    public function update_category() {
        // $this->check_validation('category', 'edit');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('edit_id', '分类id', 'trim|intval|required');
        // $this->form_validation->set_rules('mobile', '手机号', 'trim|required');
        $this->form_validation->set_rules('upid', '上级id', 'trim|required');
        if($this->form_validation->run() === FALSE) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg'    => C('tips.msg.op_fail')
                )
            );
        }
        $data = $this->format_query('/category/save', $this->post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 获取单个信息,将对应的分类一级二级都取
     */
    public function get_info() {
        if(!empty($this->post['id'])) {
            $tips =  $this->format_query('/category/info', array('id' => $this->post['id']));
        } else {
            $tips = array(
                'status'   => C('tips.code.op_failed'),
                'msg'      => C('tips.msg.op_fail')
            );
        }
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 删除
     */
    public function del() {
        // $this->check_validation('category', 'delete');
        $info = array(
            'status'    => C('tips.code.op_failed'),
            'message'   => '删除失败'
        );
        $where['id'] = $this->post['id'];
        $info = $this->format_query('/category/set_status', array('where' => $where, 'status' => C('status.common.del')));
        $this->_return_json($info);
    }

    /**
     * @author: liaoxianwen@dachuwang.com
     * @description 启用被禁用的类别
     */
    public function reuse() {
        // $this->check_validation('category', 'edit');
        $where['id'] = $this->post['id'];
        $info = $this->format_query('/category/set_status', array('where' => $where, 'status' => C('status.common.success')));;
        if(intval($info['status']) === intval(C('tips.code.op_success'))) {
            $info['msg']   ='启用成功';
        } else {
            $info['msg'] = '启用失败';
        }
        $this->_return_json($info);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 前端分类映射
     */
    public function map() {
        $data = $this->format_query('/category/map');
        $location = $this->format_query('/location/get_child');
        $data['location'] = $location['list'];
        $data['customer_type_options'] = array_values(C('customer.type'));
        $this->_return_json($data);
    }

}
/* End of file category.php */
/* Location: :./application/controllers/category.php */
