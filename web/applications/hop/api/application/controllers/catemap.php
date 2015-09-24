<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 分类映射
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Catemap extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个信息
     */
    public function info() {
        // 系统
         
        $list = $this->format_query('/category/map');
        // 一级分类
        // 二级分类
        $data = $this->format_query('/catemap/info', $this->post);
        $data['list'] = $list['list'];
        $data['sites'] = $list['sites'];
        $this->_return_json($data);
    }

    public function lists() {
        $data = $this->format_query('/catemap/lists', $this->post);
        $this->_return_json($data);
    }

    public function save() {
        if(empty($this->post['id'])) {
            $msg = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数缺少'
            );
        } else {
            $msg = $this->format_query('/catemap/save', $this->post);
        }
        $this->_return_json($msg);
    }

    public function create() {
        // 查名称有没有相同的

        $data = $this->format_query('/catemap/create' ,$this->post);
        $this->_return_json($data);
    }

    public function set_status() {
        $set = array(
            'where' => array('id' => $this->post['id']),
            'status' => $this->post['status']
        );
        $data = $this->format_query('/catemap/set_status' , $set);
        $this->_return_json($data);
    }
}

/* End of file catemap.php */
/* Location: ./application/controllers/catemap.php */
