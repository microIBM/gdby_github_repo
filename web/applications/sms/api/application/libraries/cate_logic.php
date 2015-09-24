<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cate_logic {

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array("MCategory")
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取顶级分类，然后将其中的所有的分类都取出来
     */

    public function lists($post = array()) {
        $where = array(
            'upid'  => 0,
            'status'=> intval(C('status.common.success'))
        );
        $top_cate = $this->CI->MCategory
            ->get_lists__Cache60('path, name, id', $where, array('weight'    => 'DESC'));
        // 如果是二级分类，那么隐藏，显示直接三级分类
        $top_ids = array_column($top_cate, 'id');
        $second_cate = $this->get_child($top_ids);
        // 三级分类
        $second_ids = array_column($second_cate, 'id');
        $third_cate = $this->get_child($second_ids);
        
        return array(
            'top_cate'   => $top_cate,
            'second_cate'=> $second_cate,
            'third_cate' => $third_cate
        );
    }

    public function get_child($up_ids) {
        $childs = array();
        if(!empty($up_ids)) {
            $childs = $this->CI->MCategory ->get_lists__Cache60("path, name, id",
                array(
                    'in' => array(
                        'upid'  => $up_ids
                    ),
                    'status'    => intval(C('status.common.success'))
                ),
                array('weight'  => 'DESC')
            );
        }
        return $childs;
    }
}

/* End of file  cate_logic.php*/
/* Location: :./application/libraries/cate_logic.php/ */
