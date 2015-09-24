<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 工作流日志操作model
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-04-03
 */
class MWorkflow_log extends MY_Model {
    use MemAuto;
    private $table = 't_workflow_log';
    public function __construct() {
        parent::__construct($this->table);

    }

    /**
     * 记录订单工作流日志
     * @author yugang@dachuwang.com
     * @since 2015-04-07
     */
    public function record_order($obj_id, $operate_type, $operator = NULL, $remark = '') {
        $code_with_cn = array_values(C('order.status'));
        $codes        = array_column($code_with_cn, 'code');
        $msg          = array_column($code_with_cn, 'msg');
        $order_status_dict = array_combine($codes, $msg);
        $operator = array(
            'ip'            => empty($operator['ip']) ? 0 : $operator['ip'],
            'name'          => empty($operator['name']) ? '' : $operator['name'],
            'id'            => empty($operator['id']) ? 0 : $operator['id'],
            'role_id'       => empty($operator['role_id']) ? 0 : $operator['role_id']
        );

        $data = array(
            'obj_id'        => $obj_id,
            'log_info'      => $order_status_dict[$operate_type],
            'operate_type'  => $operate_type,
            'log_ip'        => $operator['ip'],
            'remark'        => $remark,
            'operator_type' => $operator['role_id'],
            'operator'      => $operator['name'],
            'operator_id'   => $operator['id'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
            'status'        => C('status.common.success'),
            'edit_type'     => C('workflow_log.edit_type.order'),
        );

        return $this->create($data);
    }

    /**
     * 记录工作人员备注日志
     * @author yugang@dachuwang.com
     * @since 2015-06-03
     */
    public function record_order_comment($obj_id, $operator = NULL, $remark = '') {
        $operator = array(
            'ip'            => empty($operator['ip']) ? 0 : $operator['ip'],
            'name'          => empty($operator['name']) ? '' : $operator['name'],
            'id'            => empty($operator['id']) ? 0 : $operator['id'],
            'role_id'       => empty($operator['role_id']) ? 0 : $operator['role_id']
        );
        $data = array(
            'obj_id'        => $obj_id,
            'log_info'      => C('order.comment.msg'),
            'operate_type'  => C('order.comment.code'),
            'log_ip'        => $operator['ip'],
            'remark'        => $remark,
            'operator_type' => $operator['role_id'],
            'operator'      => $operator['name'],
            'operator_id'   => $operator['id'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
            'status'        => C('status.common.success'),
            'edit_type'     => C('workflow_log.edit_type.order'),
        );

        return $this->create($data);
    }

    public function record_pick_task($obj_id, $operate_type, $operator = NULL, $remark = '', $log_info = '') {
        $operator = array(
            'ip'            => empty($operator['ip']) ? 0 : $operator['ip'],
            'name'          => empty($operator['name']) ? '' : $operator['name'],
            'id'            => empty($operator['id']) ? 0 : $operator['id'],
            'role_id'       => empty($operator['role_id']) ? 0 : $operator['role_id']
        );
        $data = array(
            'obj_id'        => $obj_id,
            'log_info'      => $log_info,
            'operate_type'  => $operate_type,
            'log_ip'        => $operator['ip'],
            'remark'        => $remark,
            'operator_type' => $operator['role_id'],
            'operator'      => $operator['name'],
            'operator_id'   => $operator['id'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
            'status'        => C('status.common.success'),
            'edit_type'     => C('workflow_log.edit_type.pick_task')
        );
        return $this->create($data);
    }

    public function record_wave($obj_id, $operate_type, $operator = NULL, $remark = '', $log_info = '') {
        $operator = array(
            'ip'            => empty($operator['ip']) ? 0 : $operator['ip'],
            'name'          => empty($operator['name']) ? '' : $operator['name'],
            'id'            => empty($operator['id']) ? 0 : $operator['id'],
            'role_id'       => empty($operator['role_id']) ? 0 : $operator['role_id']
        );
        $data = array(
            'obj_id'        => $obj_id,
            'log_info'      => $log_info,
            'operate_type'  => $operate_type,
            'remark'        => $remark,
            'log_ip'        => $operator['ip'],
            'operator_type' => $operator['role_id'],
            'operator'      => $operator['name'],
            'operator_id'   => $operator['id'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
            'status'        => C('status.common.success'),
            'edit_type'     => C('workflow_log.edit_type.wave')
        );

        return $this->create($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 记录操作日志
     * @param $obj_id 操作商品或者订单的id
     * @param $operate_type 需要考虑增加状态值
     * @param $operator 操作人
     * @param $remark 操作日志备注
     * @param $log_info 操作记录信息
     */
    public function record_op_log($obj_id, $operate_type, $operator = NULL, $remark = '', $log_info = '', $edit_type = 0) {
        $operator = array(
            'ip'            => empty($operator['ip']) ? 0 : $operator['ip'],
            'name'          => empty($operator['name']) ? '' : $operator['name'],
            'id'            => empty($operator['id']) ? 0 : $operator['id'],
            'role_id'       => empty($operator['role_id']) ? 0 : $operator['role_id']
        );

        $data = array(
            'obj_id'        => $obj_id,
            'log_info'      => $log_info,
            'operate_type'  => $operate_type,
            'remark'        => $remark,
            'log_ip'        => $operator['ip'],
            'operator_type' => $operator['role_id'],
            'operator'      => $operator['name'],
            'operator_id'   => $operator['id'],
            'created_time'  => $this->input->server('REQUEST_TIME'),
            'updated_time'  => $this->input->server('REQUEST_TIME'),
            'status'        => C('status.common.success'),
            'edit_type'     => $edit_type
        );

        return $this->create($data);
    }

    /**
     * 获取订单各个状态的处理时间, 这里按正序排序是如果一个子单同样的状态有2条记录,那么取最近的时间.
     * @param int $stime 查询限制时间段
     * @param int $etime
     * @param array $order_status
     * @return array
     */
    public function get_order_time($stime, $etime, $order_status){
        return $this->get_lists(['obj_id', 'operate_type', 'created_time'], ['edit_type' => C('workflow_log.edit_type.order'), 'created_time >=' => $stime, 'created_time <=' => $etime, 'in' => ['operate_type' => $order_status]], ['created_time' => 'ASC']);
    }

}
/* End of file mworkflow_log.php */
/* Location: :./application/models/mworkflow_log.php */
