<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 邮件报表发送
 * @author zhangxiao@dachuwang.com
 * @since 2015-08-13
 */
class Email_report extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(array('email'));
    }

    public function send() {
        try {
            $email = $this->_format_post();
            $this->email->from(C('email.smtp_user'), $email['name']);
            $this->email->to($email['to']);
            $this->email->cc($email['cc']);
            $this->email->subject($email['subject']);

            if(is_array($email['header']) && is_array($email['content']) && is_array($email['desc'])) {
                $this->email->message($this->_generate_table($email['title'], $email['header'], $email['content'], $email['desc']));
            } else {
                throw new Exception('Email header/content/desc should be array');
            }

            if(!$this->email->send()) {
                throw new Exception($this->email->print_debugger());
            }
            $this->success(array(
                'msg' => 'email send!'
            ));
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }

    private function _format_post() {
        $email = array();
        $email['to']      = $this->input->post('to',true);
        $email['cc']      = $this->input->post('cc',true);
        $email['subject'] = $this->input->post('subject', true) ?: '大厨网邮件报告';
        $email['name']    = $this->input->post('name', true) ?: 'dachuwang';
        $email['title']   = $this->input->post('title', true) ?: '大厨网邮件表格';
        $email['header']  = $this->input->post('header', true) ?: array();
        $email['content'] = $this->input->post('content', true) ?: array();
        $email['desc']    = $this->input->post('desc', true) ?: array();
        $config_file      = $this->input->post('config_file', true);

        if($config_file) {
            $email['to']      = C($config_file . '.to');
            $email['cc']      = C($config_file . '.cc');
            $email['name']    = C($config_file . '.name');
            $email['subject'] = C($config_file . '.subject');
        }

        return $email;
    }

    private function _generate_table($table_title = '', $table_header = array(), $content = array(), $desc = array()) {
        $data = array(
            'table_title'  => $table_title,
            'table_header' => $table_header,
            'content'      => $content,
            'desc'         => $desc,
        );
        return $this->load->view('email_report_template', $data, TRUE);
    }


    /**
     * 支持发送多表格的邮件接口
     * @author zhangxiao@dachuwang.com
     */
    public function send_email() {
        try {
            $email = $this->_format_post_multi();
            $this->email->from(C('email.smtp_user'), $email['name']);
            $this->email->to($email['to']);
            $this->email->cc($email['cc']);
            $this->email->subject($email['subject']);

            if(is_array($email['table'])) {
                $this->email->message($this->_generate_multi_table($email['topic'], $email['table'], $email['topic_desc']));
            } else {
                throw new Exception('param table should be array');
            }

            if(!$this->email->send()) {
                throw new Exception($this->email->print_debugger());
            }
            $this->success(array(
                'msg' => 'email send!'
            ));
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }

    private function _format_post_multi() {
        $email = array();
        $email['to']         = $this->input->post('to',true);
        $email['cc']         = $this->input->post('cc',true);
        $email['subject']    = $this->input->post('subject', true) ?: '大厨网邮件报告';
        $email['name']       = $this->input->post('name', true) ?: 'dachuwang';
        $email['topic']      = $this->input->post('topic', true) ?: $email['subject'];
        $email['table']      = $this->input->post('table') ?: array();
        $email['topic_desc'] = $this->input->post('topic_desc', true) ?: array();
        $config_file      = $this->input->post('config_file', true);

        if($config_file) {
            $email['to']      = C($config_file . '.to');
            $email['cc']      = C($config_file . '.cc');
            $email['name']    = C($config_file . '.name');
            $email['subject'] = C($config_file . '.subject');
        }

        return $email;
    }

    private function _generate_multi_table($topic, $table, $topic_desc) {
        $data = array(
            'topic' => $topic,
            'table' => $table,
            'topic_desc' => $topic_desc,
        );
        return $this->load->view('email_multireport_template', $data, TRUE);
    }


    /**
     * 接口调用失败返回数据
     * @param string $message
     * @return json json格式的错误信息
     */
    private function failed($message = '接口调用失败') {
        $this->_return_json(
            array(
                'status'  => C('status.req.failed'),
                'msg' => $message,
            )
        );
    }

    /**
     * 接口调用成功返回数据
     * @param array $data 传入数组格式的数据
     * @return json 返回json格式的数据信息
     */
    private function success(array $data = array()) {
        $data['status'] = C('status.req.success');
        $this->_return_json($data);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     * @param: array arr 需要转成json的数组
     */
    private function _return_json($arr) {
        if(in_array($this->input->server("HTTP_ORIGIN"), C("allowed_origins"))) {
            header('Access-Control-Allow-Origin: ' . $this->input->server("HTTP_ORIGIN"));
        } else {
            header('Access-Control-Allow-Origin: http://www.dachuwang.com');
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: X-Requested-With');
        header('Cache-Control: no-cache');
        echo json_encode($arr);exit;
    }
}

/* End of file email_report.php */
/* Location: ./application/controllers/send_email.php.php */