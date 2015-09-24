<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 规格控制器
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Property extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }
    // 获取规格展示类型
    public function get_type() {
        $properties = C('property.type'); 
        $new_prop = array();
        foreach($properties as $v) {
            $new_prop[] = $v;
        }
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success'),
                'list'      => $new_prop
            )
        );
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 添加
     */
    public function create() {
        $post = $this->post;
        $tips = $this->format_query('/property/create', $post);
        $this->_return_json($tips);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 查询多条数据
     */
    public function lists() {
        $post = $this->post;
        $data = $this->format_query('/property/lists', $post);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 启用
     */
    public function reuse() {
        if(isset($this->post['id'])) {
            $tips = $this->format_query('/property/set_status', array('status' => C('property.status.success'), 'id' => $this->post['id']));
       } else {

            $tips = array(
                'status'    => C('tips.code.op_failed'),
                'msg'       => C('tips.msg.reuse_fail')
            );
        }
        $this->_return_json($tips);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 禁用
     */
    public function del() {
        if(isset($this->post['id'])) {
            $tips = $this->format_query('/property/set_status', array('status' => C('property.status.del'), 'id' => $this->post['id']));
        } else {
            $tips = array(
                'status'    => C('tips.code.op_failed'),
                'msg'       => C('tips.msg.del_fail')
            );
        }
        $this->_return_json($tips);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品规格获取
     */
    public function cate_prop_list() {
        if(isset($this->post['category_id'])) {
            $data = $this->format_query('/property/get_cate_properties', array('id' => $this->post['category_id']));
        } else {
            $data = array(
                'status'    => C('tips.code.op_failed'),
                'msg'       => C('tips.msg.op_fail')
            );
        }
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个属性
     */
    public function prop_single() {
        if(isset($this->post['id'])) {
            $single = $this->MProperty
                ->get_one('*', array('id'   => $this->post['id']));
            if($single) {
                $moptions = $this->MOptions
                    ->get_lists("*", array('property_id' => $single['id']));
                $cate = $this->MCategory
                    ->get_one("*", array('id'   => $single['category_id']));
                // 获取顶级
                $pathArr = explode(".", trim($cate['path'], "."));
                $topid = $pathArr[0];
                $top_category = $this->MCategory
                    ->get_one('*', array('id'   => $topid));
                // 获取二级
                $second = $pathArr[1];
                $second_category = $this->MCategory
                    ->get_one('*', array('id'    => $second));
                // 三级
                $single['options'] = $moptions;
                $single['top_category'] = $top_category;
                $single['second_category'] = $second_category;
                $single['self'] = $cate;
                $tips = array(
                    'status'    => TRUE,
                    'info'      => $single
                );
            } else {
                $tips = array(
                    'status'    => FALSE,
                    'msg'       => C('tips.msg.op_fail')
                );
            }
        } else {
            $tips = array(
                'status'    => FALSE,
                'msg'       => C('tips.msg.op_fail')
            );
        }
        $this->_return_json($tips);
    }
}
/* End of file property.php */
/* Location: ./application/controllers/property.php */
