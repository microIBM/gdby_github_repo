<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MJob extends MY_Model {

    private $table = 't_queue_jobs';

    public function __construct() {
        parent::__construct($this->table);
    }


    /**
     * 基础服务队列统一投放队列接口
     * @author fengzongbao@dachuwang.com
     */
    public function create_job($tube, $data, $priority=1024, $delay=0, $ttr=60)
    {
        try {
            $job_id = $this->beanstalk->pheanstalk->useTube($tube)->put(json_encode($data), $priority, $delay, $ttr);
            // 存放在数据库
            $this->create(
                array(
                    'job_id'   => $job_id,
                    'tube'     => $tube,
                    'data'     => json_encode($data),
                    'priority' => $priority,
                    'delay'    => $delay,
                    'ttr'      => $ttr,
                    'created_time' => $this->input->server('REQUEST_TIME')
                )
            );
        } catch (Exception $e) {
            // todo 统一日志记录
            var_dump($e);
        }

        return $job_id;
    }

    /**
     * 基础服务队列统一更新入口
     * @author fengzongbao@dachuwang.com
     */
    public function update_job($data, $job_id)
    {
        try {
            $job    = $this->beanstalk->job($job_id);
            $result = $this->beanstalk->pheanstalk->statsJob($job);
            // todo update the t_queue_jobs table 
        } catch (Exception $e) {
            // todo log
            var_dump($e);
        }

        $data = array(
            'state'         => $result['state'],
            'age'           => $result['age'],
            'time_left'     => $result['time-left'],
            'reserves'      => $result['reserves'],
            'timeouts'      => $result['timeouts'],
            'releases'      => $result['releases'],
            'buries'        => $result['buries'],
            'kicks'         => $result['kicks'],
            'worker_result' => json_encode($data),
            'updated_time'  => $this->input->server('REQUEST_TIME')
        );

        $this->update_info($data, array('job_id' => $job_id));
    }


    /**
     * 基础队列服务任务统一删除
     * @author fengzongbao@dachuwang.com
     */
    public function job_delete($job_id)
    {
        $this->update_job('job deleted', $job_id);
        $job = $this->beanstalk->job($job_id);
        $this->beanstalk->pheanstalk->delete($job);
    }

}

/* End of file mjob.php */
/* Location: :./application/models/mjob.php */
