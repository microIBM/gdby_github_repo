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
    public function lists() {
        // 默认是北京的
        $this->_deal_location_id();
        if(!empty($this->post['searchVal'])) {
            $where['like'] = array('name' => $this->post['searchVal']);
            $where['location_id'] = $this->post['location_id'];
            $data = $this->format_query('/catemap/search', array('where' => $where));
        } else {
            $data = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数错误'
            );
        }
        $this->_return_json($data);
    }

    private function _deal_location_id() {
        if(empty($this->post['locationId'])) {
            $this->post['location_id'] = C('open_cities.beijing.id');
        } else {
            $this->post['location_id'] = $this->post['locationId'];
            unset($this->post['locationId']);
        }
    }

    public function save() {
        if(empty($this->post['id'])) {
            $msg = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数缺少'
            );
        } else {
            $this->_deal_location_id();
            $msg = $this->format_query('/catemap/save', $this->post);
        }
        $this->_return_json($msg);
    }

    public function create() {
        // 查名称有没有相同的
        // 默认是北京的
        $this->_deal_location_id();
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
