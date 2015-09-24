<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 邮件报表发送
 * @author zhangxiao@dachuwang.com
 */
class Email_report extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(array('email'));
    }

    /*
     * @desc 邮件发送服务接口
     * @author wangzejun@dachuwang.com
     */
    public function send() {
        try {
            $email = $this->format_post();
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

    private function format_post() {
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
        //创建表头
        $table_thead = '<tr>';
        foreach ($table_header as $value) {
            $table_thead .= 
            '<td valign="top" style="height: 12.0px; background-color: #bec0bf; border-style: solid; border-width: 1.0px; border-color: #000000; padding: 4.0px; text-align: center;">
                 <p style="margin: 0px; font-stretch: normal; font-size: 16px; line-height: normal; font-family: Helvetica; min-height: 14px;">'.$value.'<br></p>
             </td>';
        }
        $table_thead .= '</tr>';
        
        //创建表数据
        $table_content = '';
        $count = 1;
        foreach ($content as $row) {
            $table_content .= '<tr>';
            $bgcolor = '';
            if(fmod($count, 2) == 0) {
                $bgcolor = 'background-color: #f5f5f5;';
            }
            foreach ($row as $value) {
                $table_content .= 
                '<td valign="top" style="height: 11.0px; border-style: solid; border-width: 1.0px; border-color: #000000; text-align: center; padding: 4.0px;'.$bgcolor.'">
                     <p style="margin: 0px; font-stretch: normal; font-size: 14px; line-height: normal; font-family: Helvetica; min-height: 14px;">'.$value.'<br></p>
                 </td>';
            }
            $table_content .= '</tr>';
            $count++;
        }

        //生成表格
        $table  = '';
        $table .= '<div><table cellspacing="0" cellpadding="0" style="border-collapse: collapse"><caption align="top" style="margin-bottom: 10px; font-size: 16px;">'.$table_title.'</caption><tbody>';
        $table .= $table_thead.$table_content;
        $table .= '</tbody></table></div>';
        $description = '<div style="margin-top: 10px"><hr>';
        foreach ($desc as $value) {
        	$description .= '<p style="margin-top: 5px;margin-bottom: 5px;">※ '.$value.'</p>';
        }
        $description .= '</div>';
        $table .= $description;
        return $table;
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

/* End of file send_email.php */
/* Location: ./application/controllers/send_email.php.php */