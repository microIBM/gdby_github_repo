<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Saiku extends MY_Controller {
    const WHITE_PINLEI_MODULE = 6; //品类分析白名单模块ID
    const PINLEI = 6; //品类分析页面
    public function __construct () {
        parent::__construct();
        $this->load->helper('url');
    }
    public function index(){
        $data = $this->data;
        $site_id = $this->input->get('site_id');
        $table_id = $this->input->get('table_id');
        $city_id = $this->input->get('city_id');
        $data['current_url'] = current_url();
        $data['site_id']  = $site_id ? $site_id : C('site.dachu');
        $data['city_id']  = $city_id ? $city_id : C('open_cities.quanguo.id'); //默认全国;
        $data['table_id'] = $table_id ? $table_id : SELF::PINLEI;
        $data['tab_id']=0;
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_PINLEI_MODULE, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $this->load->view('saiku',$data);
        }
    }
    public function login(){
        $this->load->view('saiku_login');
    }
}
