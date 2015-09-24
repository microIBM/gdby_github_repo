<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Monitor extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->library(
            array(
                'xmlrpc',
            )
        );
    }


    public function index()
    {
        $servers = C('supervisor.servers');

        foreach($servers as $serverno => $server) {
            $this->xmlrpc->server($server['url'], $server['port']);
            $this->xmlrpc->method('supervisor.getAllProcessInfo');
            $this->xmlrpc->send_request();
            $data['data'][$serverno] = $this->xmlrpc->display_response();
        }

        $this->load->view('index', $data);
    }

    /**
     *  启动某个进程
     */
    public function start($server, $worker)
    {
        $this->_xmlrpcrequest($server, 'startProcess', array($worker, true));
        redirect(base_url(), 'refresh');
    }


    /**
     * 杀掉某个进程
     */
    public function stop($server, $worker)
    {
        $this->_xmlrpcrequest($server, 'stopProcess', array($worker, true));

        redirect(base_url(), 'refresh');
    }

    /**
     *
     * 日志监听
     */
    public function logtail($server, $worker)
    {
        $result = $this->_xmlrpcrequest($server, 'tailProcessStdoutLog', array($worker, 0, 1000));
        echo "<pre>";
        var_export($result);
        echo "</pre>";
    }



    /**
     *
     * 清除日志
     */
    public function logclear($server, $worker)
    {
        $resutl =  $this->_xmlrpcrequest($server, 'clearProcessLogs', array($worker));
        redirect(base_url(), 'refresh');
    }


    /**
     * 全部启动
     */
    public function startall($server)
    {
        $result = $this->_xmlrpcrequest($server, 'startAllProcesses', array(true));
        redirect(base_url(), 'refresh');
    }

    /**
     *
     * 停止所有进程
     */
    public function stopall($server)
    {
        $result = $this->_xmlrpcrequest($server, 'stopAllProcesses', array(true));
        redirect(base_url(), 'refresh');
    }


    public function test()
    {
        $server = C('supervisor.servers.develop_server');
        $this->xmlrpc->server($server['url'], $server['port']);
        $this->xmlrpc->method('supervisor.tailProcessStdoutLog');

        $request = $this->input->get('worker');
        $this->xmlrpc->request(array('worker_sms', 0, 1000000));
        $this->xmlrpc->send_request();
        $reuslt = $this->xmlrpc->display_response();
        log_message('error', var_export($reuslt, true));

    }

}

/* End of file index.php */
/* Location: ./application/controllers/index.php */
