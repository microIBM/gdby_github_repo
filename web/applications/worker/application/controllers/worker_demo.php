<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @description 这个文件是所有消费者worker的示例程序
 * @author caochunhui@dachuwang.com
 */
class Worker_demo extends MY_Controller {

    /**
     * tube 
     */
    public $tube;

    /**
     * 当前出队列的job
     */
    public $job;

    public function __construct () {
        parent::__construct();
        $this->load->library(array(
            'beanstalk',
        ));

        $this->tube = substr(__CLASS__, 7);
    }

    public function run() {
        $this->job     = $this->beanstalk->pheanstalk->watch($this->tube)->reserve();
        $payload       = json_decode($this->job->getData(), true);
        var_dump($payload);
        $this->beanstalk->pheanstalk->delete($this->job);

        //处理100个请求后自动退出，用supervisor自动拉起
       /* $cnt = 0;
        while(TRUE) {
            $cnt++;
            //业务代码start
            echo 'working now';

            //业务代码end
            if($cnt >= 100) {
                return;
            }
            sleep(1);
        }*/
    }


    
    /**
     * 投放
     *
     */
    public function put()
    {
        $data = array(
            'phone'   => mt_rand(10000000000, 19999999999),
            'message' => 'hello test',
        );
        $pri = 1024;
        $ttl = 10;
        $beanstalk = $this->beanstalk;
        var_dump($data);
        $job_id = $beanstalk->pheanstalk->useTube($this->tube)->put(json_encode($data), $pri, $ttl);
        var_dump($job_id);
    }

}

/* End of file worker_demo.php */
/* Location: ./application/controllers/worker_demo.php */
