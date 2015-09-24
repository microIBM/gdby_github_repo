<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Worker_sms extends MY_Controller {

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
            'email',
            'redisclient',
        ));

        $this->tube = substr(__CLASS__, 7);
    }


    public function run() {
        echo date('Y-m-d H:i:s') . " statrting run \n\n";
        $jobNum = 0;

        while(1) {
            try {
                $this->job     = $this->beanstalk->pheanstalk->watch($this->tube)->reserve();

            } catch (Exception $e) {
                $key = 'benastalkd_exception_' . date('Y-m-d');
                $sent = $this->redisclient->get($key);
                if(!$sent) {
                    $this->email->from('report@dachuwang.com',  '大厨网基础服务监控报告');
                    $this->email->to('service@dachuwang.com');
                    $this->email->cc('fengzongbao@dachuwang.com');
                    $this->email->subject('Beanstalkd 消息队列捕获异常');
                    $msg =  "捕获的异常，详细信息：<br/>" .
                        "File: "    . $e->getFile() . "<br/>" .
                        "Line: "    . $e->getLine() . "<br>" .
                        "Code: "    . $e->getCode() . "<br>" .
                        "Message: " . $e->getMessage() . "<br>" .
                        "Trace: "   . "<br>" . $e->getTraceAsString() . "<br>";
                    $this->email->message($msg);
                    $this->email->send();
                    // 异常计数 有效期24小时
                    $this->redisclient->set($key, 1);
                }

                echo $e->getMessage() . "\r\n";
                return false;
            }

            $payload           = json_decode($this->job->getData(), true);
            $payload['job_id'] = $this->job->getId();
            // 分发请求，调用短信发送接口
            $this->load->library(
                array(
                    'Dachu_request',
                )
            );

            $url = C('service.s') . '/sms/worker_sms_send'; 
            $response = $this->dachu_request->post($url, $payload);

            // 发送成功，删除Job  失败 buried
            if($response && isset($response['status']) && $response['status'] == 0) {
                $this->beanstalk->pheanstalk->delete($this->job);
                echo date('Y-m-d H:i:s') . ' Job Deleted:' . $this->job->getId() . "\n";

            } else {
                echo "request failled !\n";
                echo var_export($response, true);
            }
            $jobNum ++;
            $memory = memory_get_usage();

            echo var_export($payload, true) . "\n";
            echo 'memory:' . $memory . "\n";

            if($memory > 52428800) {
                echo 'exiting run due to memory limit'. "\n";
                return ;
            }
            echo "This Process Handled job No:" . $jobNum . "\n\n";
            if($jobNum > 1000){
                echo "job handle 1000 ,restart";
                return ;
            }
        }
    }
}

/* End of file worker_sms.php */
/* Location: ./application/controllers/worker_sms.php */
