<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Job extends MY_Controller {

    public function __construct () {
        parent::__construct();

        $this->load->model(
            array(
                'MJob'
            )
        );

        $this->load->library(
            array(
                'beanstalk'
            )
        );
    }

    /**
     * 大厨网基础服务队列统一投放入口
     * @param $tube string 任务容器
     * @param $data array  要投放的队列数据
     * @param $priority int 1024 优先级
     * @param $delay int 0 延迟
     * @param $ttr int 60 最大处理时间
     */
    public function put()
    {
        if(empty($_POST['data']) || empty($_POST['tube'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'message' => 'put data empty'
                )
            );
        }
        $tube      = $_POST['tube'];
        $data      = $_POST['data'];
        $priority  = !empty($_POST['priority']) ? $_POST['priority'] : 0;
        $delay     = !empty($_POST['delay']) ? $_POST['delay'] : 0;
        $ttr       = !empty($_POST['ttr']) ? $_POST['ttr'] : 30;

        try {
            $job_id = $this->beanstalk->pheanstalk->useTube($tube)->put(json_encode($data), $priority, $delay, $ttr);
            // 存放在数据库
            $this->MJob->create(
                array(
                    'job_id'   => $job_id,
                    'tube'     => $tube,
                    'data'     => $data,
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
        $this->_return_json(
            array(
                'status' => 0,
                'job_id' => $job_id,
                'message' => 'put success ,wait for handling...'
            )
        );
    }

    /**
     * 大厨网基础服务队列job状态查询
     */
    public function stats_job()
    {
        if(empty($_POST['job_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'message' => "empty joib_id"
                )
            );
        }
        $job_id = $_POST['job_id'];

        try {
            $job    = $this->beanstalk->job($job_id);
            $result = $this->beanstalk->pheanstalk->statsJob($job);
            // todo update the t_queue_jobs table 
        } catch (Exception $e) {
            // todo log
            var_dump($e);
        }

        // message of job
        $this->_return_json(
            array(
                'status' => 0,
                'message' => 'success',
                'info'    => $result
            )
        );
    }


    /**
     *
     * 大厨网基础服务队列任务撤销接口
     * @param $job_id int 任务id 
     */
    public function job_delete()
    {
        $job_id = $_POST['job_id'];

        try {
            $job = $this->beanstalk->job($job_id, null);
            // todo update the t_queue_jobs table

            $this->beanstalk->delete($job);
        } catch (Exception $e) {
            // todo log 
            var_export($e);
        }

        $this->_return_json(
            array(
                'status'  => 0,
                'job_id'  => $job_id,
                'message' => "job deleteed success"
            )
        );
    }
}

/* End of file job.php */
/* Location: ./application/controllers/job.php */       
