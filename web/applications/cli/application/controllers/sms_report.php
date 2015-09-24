<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sms_report extends MY_Controller {

    public function __construct () {
        parent::__construct();

        $this->load->library(
            array(
                'email',
            )
        );

        $this->load->model(
            array(
                'MSms_log',
            )
        );
    }


    public function run()
    {
        // read sms_log ;
        $last_day_min = strtotime(date('Y-m-d 00:00:00') . '-1 day');
        $last_day_max = strtotime(date('Y-m-d 23:59:59') . '-1 day');
        $succss_sql   = "SELECT * FROM t_sms_log where send_response = 0 AND created_time BETWEEN '$last_day_min' AND  '$last_day_max'";
        $other_sql    = "SELECT * FROM t_sms_log where send_response != 0 AND created_time BETWEEN '$last_day_min' AND  '$last_day_max'";

        $success_result = $this->db->query($succss_sql);
        $other_result = $this->db->query($other_sql);
        $success_num = $success_result->num_rows();
        $other_num = $other_result->num_rows();

        // report by email
        $this->email->from('report@dachuwang.com', '短信服务日报');
        $this->email->to('jishu@dachuwang.com');
        $this->email->subject('短信服务报告');
        $this->email->message("昨天(00:00 - 23:59)共发送短信" . ($success_num + $other_num) . "条<br/>" . '发送成功：' . $success_num . "\n发送失败:" . $other_num );

        $this->email->send();
    }

}

/* End of file sms_report.php */
/* Location: ./application/controllers/sms_report.php */
