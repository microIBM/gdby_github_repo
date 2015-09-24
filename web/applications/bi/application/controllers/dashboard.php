<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * dashboard for mobile
 * @author zhangxiao@dachuwang.com
 * @version 1.0.0
 * @since 2015-05-21
 */

class Dashboard extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->load->view('dashboard', $this->data);
    }

    public function order() {
        $this->load->view('dashboard_order', $this->data);
    }

    public function customer() {
        $this->load->view('dashboard_cus', $this->data);
    }

    public function cus_price() {
        $this->load->view('dashboard_cus_price', $this->data);
    }
}