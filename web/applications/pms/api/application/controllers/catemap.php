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
        $this->load->model(array('MWorkflow_log'));
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
        $location = $this->format_query('/location/get_child');
        $data['list'] = $list['list'];
        $data['sites'] = $list['sites'];
        $data['location'] = $location['list'];
        $data['customer_type_options'] = array_values(C('customer.type'));
        $this->_return_json($data);
    }

    public function lists() {
        // 默认是北京的
        $this->_deal_location_id();
       if(!empty($this->post['searchVal'])) {
            $where['like'] = array('name' => $this->post['searchVal']);
            $where['location_id'] = intval($this->post['location_id']);
            $where['customer_type'] = empty($this->post['customerType']) ? C('customer.type.normal.value') : intval($this->post['customerType']);
            $where['site_id'] = empty($this->post['siteType']) ? C('site.code.dachu.id') : intval($this->post['siteType']);
            $data = $this->format_query('/catemap/search', array('where' => $where));
        } else {
            $this->post['no_cache'] = FALSE;
            //客户类型
            $this->post['customer_type'] = empty($this->post['customerType']) ? C('customer.type.normal.value') : intval($this->post['customerType']);
            //站点类型
            $this->post['site_id'] = empty($this->post['siteType']) ? C('site.code.dachu.id') : intval($this->post['siteType']);
            if(isset($this->post['customerType'])) {
                unset($this->post['customerType']);
            }
            if(isset($this->post['siteType'])) {
                unset($this->post['siteType']);
            }
            $data = $this->format_query('/catemap/backend_list', $this->post);
        }
        $location = $this->format_query('/location/get_child');
        $data['location'] = $location['list'];
        $data['customer_type_options'] = array_values(C('customer.type'));
        $data['site_type_options'] = array_values(C('site.code'));
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
        $this->check_validation('product', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        if(empty($this->post['id'])) {
            $msg = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '参数缺少'
            );
        } else {
            $this->_deal_location_id();
            $this->MWorkflow_log->record_op_log($this->post['id'], C('status.common.success'), $cur, '映射分类数据保存', json_encode($this->post), C('workflow_log.edit_type.catemap'));
            $msg = $this->format_query('/catemap/save', $this->post);
        }
        $this->_return_json($msg);
    }

    public function create() {
        $this->check_validation('product', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        // 查名称有没有相同的
        // 默认是北京的
        $this->_deal_location_id();
        $data = $this->format_query('/catemap/create' ,$this->post);
        if(isset($data['id'])) {
            $this->MWorkflow_log->record_op_log($data['id'], C('status.common.success'), $cur, '映射分类添加', json_encode($this->post), C('workflow_log.edit_type.catemap'));
        }
        $this->_return_json($data);
    }

    public function set_status() {
        $this->check_validation('product', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $set = array(
            'where' => array('id' => $this->post['id']),
            'status' => $this->post['status']
        );
        $data = $this->format_query('/catemap/set_status' , $set);
        $this->MWorkflow_log->record_op_log($this->post['id'], $this->post['status'], $cur, '映射分类状态设置', json_encode($this->post), C('workflow_log.edit_type.catemap'));
        $this->_return_json($data);
    }
}

/* End of file catemap.php */
/* Location: ./application/controllers/catemap.php */
