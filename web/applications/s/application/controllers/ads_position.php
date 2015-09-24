<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 广告位
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-4-24
 */
class Ads_position extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('MAds_position');
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建广告位
     */
    public function create() {
        $this->_unique_name($_POST['title']);
        $req_time = $this->input->server('REQUEST_TIME');
        $data = array(
            'title' => $_POST['title'],
            'status' => $_POST['status'],
            'created_time' => $req_time,
            'updated_time' => $req_time
        );
        if( $id = $this->MAds_position->create($data) ) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '广告位' . $_POST['title'] . '创建成功'
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '广告位创建失败'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 保存更新
     */
    public function save() {
        $up_data = array(
            'title' => $_POST['title'],
            'status' => $_POST['status'],
            'created_time' => $this->input->server('REQUEST_TIME')
        );
        $affect = $this->MAds_position->update_info($up_data, array('id' => $_POST['id']));
        if($affect) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '更新成功'
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '更新失败'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取列表信息
     */
    public function lists() {
        $where = isset($_POST['where']) ? $_POST['where'] : '';
        $orderBy = isset($_POST['orderBy']) ? $_POST['orderBy'] : array('created_time' => 'DESC');
        $page = $this->get_page();
        // 获取全部
        if(isset($_POST['getAll']) && $_POST['getAll']) {
            $page = array(
                'offset' => 0,
                'page_size' => 0
            );
        }
        $total = $this->MAds_position->count($where);
        $data = $this->MAds_position->get_lists(
            '*',
            $where,
            $orderBy,
            $page['offset'],
            $page['page_size']
        );
        if($data) {
            $this->_deal_data($data);
            $response = array(
                'status' => C('tips.code.op_success'),
                'total' => $total,
                'list' => $data
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有数据'
            );
        }
        $this->_return_json($response);
    }

    private function _deal_data(&$data) {
        foreach($data as &$v) {
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
        }
    }

    public function set_status() {
        $data = array(
            'status' => $_POST['status'],
            'updated_time' => $this->input->server('REQUEST_TIME')
        );
        $where = array(
            'id' => $_POST['id']
        );
        $this->MAds_position->update_info($data, $where);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg' => '设置成功'
            )
        );
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 确保名称唯一
     */
    private function _unique_name($name) {
        if(!empty($name)) {
            $data = $this->MAds_position->get_one('id', array('title' => $name));
            if($data) {
                $response = array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => $name . '广告位已存在'
                );
            } else {
                $response = FALSE;
            }
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' =>  '广告位名称必填'
            );

        }
        if(is_array($response)) {
            $this->_return_json($response);
        }
    }
}

/* End of file ads_position.php */
/* Location: ./application/controllers/ads_position.php */
