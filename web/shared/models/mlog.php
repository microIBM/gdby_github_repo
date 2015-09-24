<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MLog extends MY_Model {
    use MemAuto;

    private $table = "t_log";

    public function __construct(){
        parent::__construct($this->table);
    }

    /**
     * 记录系统日志
     * @author yugang@dachuwang.com
     * @since 2015-07-06
     */
    public function record($operator) {
        try{
            if (empty($operator)) {
                $operator = ['ip' => '0', 'name' => '', 'id' => 0];
            }
            $data = array(
                'controller'     => $this->router->fetch_class(),
                'method'         => $this->router->fetch_method(),
                'param'          => !empty($_POST) ? json_encode($_POST) : '',
                'host'           => $this->input->server('HTTP_HOST'),
                'user_agent'     => $this->input->user_agent(),
                'request_method' => $this->input->server('REQUEST_METHOD'),
                'is_ajax'        => $this->input->is_ajax_request(),
                'http_headers'   => json_encode($this->input->request_headers()),
                'log_ip'         => !empty($operator['ip']) ? $operator['ip'] : 0,
                'operator'       => !empty($operator['name']) ? $operator['name'] : '',
                'operator_id'    => !empty($operator['id']) ? $operator['id'] : 0,
                'created_time'   => $this->input->server('REQUEST_TIME'),
                'updated_time'   => $this->input->server('REQUEST_TIME'),
                'status'         => C('status.common.success'),
            );
            return $this->create($data);
        } catch (Exception $e){
            error_log('record log error, msg:' . $e.getMesage());
        }
    }
}
/* End of file mlog.php */
/* Location: :./shared/models/mlog.php */
