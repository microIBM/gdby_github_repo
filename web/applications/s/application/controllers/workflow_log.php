<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 操作日志逻辑
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-6-2
 */
class Workflow_log extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MWorkflow_log',
                'MRole'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单条信息记录 
     */
    public function info() {
        $edit_type = isset($_POST['edit_type']) ? $_POST['edit_type'] : 0;
        $obj_id = isset($_POST['obj_id']) ? $_POST['obj_id'] : 0;
        $log_info = $this->MWorkflow_log->get_lists__Cache60('*', array('edit_type' => $edit_type, 'obj_id' => $obj_id), array('updated_time' => 'DESC'));
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '没有该记录操作日志'
        );
        if($log_info) {
            $this->_format_log_info($log_info);
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => 'success',
                'list' => $log_info
            );
        }
        $this->_return_json($response);
    }

    private function _format_log_info(&$log_list) {
        // 角色ID和名称字典
        $role_list  = $this->MRole->get_lists__Cache120('id, name', array('status' => C('status.common.success')));
        $role_ids   = array_column($role_list, 'id');
        $role_names = array_column($role_list, 'name');
        $role_dict  = array_combine($role_ids, $role_names);
        foreach ($log_list as &$log) {
            $log['created_time'] = date('Y-m-d H:i:s', $log['created_time']);
            $log['operator_type_cn'] = isset($role_dict[$log['operator_type']]) ? $role_dict[$log['operator_type']] : '';
        }
    }
}

/* End of file workflow_log.php */
/* Location: ./application/controllers/workflow_log.php */
