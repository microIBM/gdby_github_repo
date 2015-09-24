<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MY_Controller extends CI_Controller {
    public $post = array();
    protected $_service_url = '';

    public function __construct() {

        parent::__construct();
        $this->_service_url = C('service.s');
        $this->data['base_url']    = C('config.base_url');
        $this->data['img_url']    = C('config.img_url');
        $this->data['api_url'] = C('config.api_url');
        $this->data['web_url'] = C('config.web_url');
        $this->data['css_version'] = C('version.version.css');
        $this->data['js_version'] = C('version.version.js');
        $this->load->library(array('UserAuth', 'Http'));
        $this->data['user_info'] = $this->userauth->current(FALSE);

        if(empty($this->data['user_info'])){
                header("location:". $this->data['base_url'].'/user');exit;
        }

        $this->post = json_decode(file_get_contents("php://input"), TRUE);
        // 从post中json字符串中解析出变量并合并到$_POST
        if(!empty($this->post)) {
            $this->post = xss_clean($this->post);
            $_POST = array_merge($_POST, $this->post);
        }

        $this->data['left_nav'] = $this->create_left_nav();
        $this->data['top_nav'] = $this->create_top_nav();
        $this->data['city_map'] = $this->create_cities_map();

        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     * @param: array arr 需要转成json的数组
     */
    public function _return_json($arr) {
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

    /**
     * ajax请求通用返回接口
     * @author: yugang@ymt360.com
     * @param: result 操作结果
     * @since 2015-01-27
     */
    public function _return($result, $success_msg = '操作成功', $failure_msg = '操作失败') {
        if ($result) {
            $this->_return_json(
                array(
                    'status'    => C('status.req.success'),
                    'msg'       => $success_msg,
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status'    => C('status.req.failed'),
                    'msg'       => $failure_msg,
                )
            );
        }
    }
    
    /**
     * ajax请求通用返回接口, 并返回数据
     * @author: zhangxiao@dachuwang.com
     * @param: result 操作结果
     * @since 2015-07-24
     */
    public function _return_result($result = '', $success_msg = '请求成功', $failure_msg = '请求失败') {
        if ($result) {
            $this->_return_json(
                array(
                    'status'    => C('status.req.success'),
                    'msg'       => $success_msg,
                    'result'    => $result
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status'    => C('status.req.failed'),
                    'msg'       => $failure_msg,
                )
            );
        }
    }

    /**
     * 进行权限验证
     * @author yugang@ymt360.com
     * @since 2015-01-23
     */
    public function check_validation($resource, $operation, $module = '', $is_customer = TRUE) {
        $result = $this->userauth->check_validation($resource, $operation, $module, $is_customer);
        if($result == C('status.auth.login_timeout')) {
            $this->_return_json(
                array(
                    'status' => C('status.auth.login_timeout'),
                    'msg'    => '登录超时，请重新登录',
                )
            );
        } elseif ($result == C('status.auth.forbidden')) {
            header('HTTP/1.0 403 Forbidden');
            echo 'You are forbidden!';
            exit;
        }
    }

    /**
     * 导出csv数据报表
     * @author yugang@ymt360.com
     * @since 2015-01-30
     */
    public function export_csv($title_arr, $data, $file_path = 'default.csv') {
        array_unshift($data, $title_arr);
        // 在公共的方法中使用导出时间不限制
        // 以防止数据表太大造成csv文件过大,造成超时
        set_time_limit(0);
        $temp_file_path = '/tmp/export/'. uniqid() . '.csv';
        $file = fopen($temp_file_path, 'w');
        $keys = array_keys($title_arr);
        foreach($data as $item) {
            // 对数组中的内容按照标题的顺序排序，去除不需要的内容
            $info = array();
            foreach ($keys as $v) {
                $info[$v] = isset($item[$v]) ? $item[$v] : '';
            }
            fputcsv($file, $info);
        }
        fclose($file);
        //在win下看utf8的csv会有点问题
        $str = file_get_contents($temp_file_path);
        $str = iconv('UTF-8', 'GBK', $str);
        unlink($temp_file_path);
        // 下载文件
        $this->load->helper('download');
        force_download($file_path, $str);
    }

    /**
     * 进行表单验证
     * @author yugang@ymt360.com
     * @description 进行表单验证，如果失败返回错误提示
     */
    public function validate_form() {
        if ($this->form_validation->run() === FALSE) {
            $this->_return_json(
                array(
                    'status'  => C('status.req.invalid'),
                    'msg'     => '请填写完整必填的信息', // 表单验证错误提示信息validation_errors()
                )
            );
        }
    }

    /**
     * 获取分页相关参数
     * @author yugang@ymt360.com
     * @since 2015-02-03
     */
    public function get_page() {
        $page = empty($_POST['currentPage']) ? 1 : $_POST['currentPage'];
        $page_size = empty($_POST['itemsPerPage']) ? 10 : $_POST['itemsPerPage'];
        $offset = $page_size * ($page - 1);
        $page = array(
            'page'      => $page,
            'offset'    => $offset,
            'page_size' => $page_size,
        );

        return $page;
    }

    /**
     * @author: liaoxianwen@dachuwang.com
     * @version 2015-07-24 11:11:59 yelongyi@dachuwang.com
     * @param string $uri_string 查询地址字符串，一般是内网控制器地址
     * @param array $data 请求的参数
     * @param bool $debug 是否开启debug模式
     * @param bool $cross 是否使用网址全名，如果使用，则可以请求特定域名的接口
     * @return array|mixed 返回结果
     */
    public function format_query($uri_string, $data = array(), $debug = FALSE, $cross = FALSE) {
        if(!$cross){
            $uri_string = $this->_service_url . '/' . $uri_string;
        }
        $return_data = $this->http->query($uri_string, $data);
        $data = json_decode($return_data, TRUE);
        if($debug) {
            echo $return_data;
        }
        if($data) {
            return $data;
        } else {
            var_dump($data);die;
        }
    }

    protected function create_left_nav(){

        $params = $this->get_query_params();
        $query['city_id'] = $params['city_id'];
        $query['site_id'] = $params['site_id'];
        $query['menue_id'] = $params['menue_id'];
        $query['tab_id'] = $params['tab_id'];

        $this->load->config('nav');
        $left_nav = $this->config->item('left_nav');

        $menues = array();
        if (!empty($left_nav)) {
            foreach ($left_nav as $key => $value){
                $query['menue_id'] = $value['id'];
                $menues[$key]['id'] = $value['id'];
                $menues[$key]['title'] = $value['title'];
                if(!$value['whole']){
                    $query['city_id'] = C('open_cities.beijing.id');
                } else {
                    $query['city_id'] = C('open_cities.quanguo.id');
                }
                $menues[$key]['url'] = $this->data['base_url'].'/'.$value['url'].'?'.http_build_query($query);
                $menues[$key]['base_url'] = $this->data['base_url'].'/'.$value['url'];
                $menues[$key]['class'] = 'inactive';
                $menues[$key]['whole'] = $value['whole'];
                if($params['menue_id'] == $value['id']){
                    $menues[$key]['class'] = 'active';
                }
            }
        }
        return $menues;
    }

    protected function create_top_nav(){

        $params = $this->get_query_params();
        $query['city_id'] = $params['city_id'];
        $query['site_id'] = $params['site_id'];
        $query['menue_id'] = $params['menue_id'];

        $query['tab_id'] = $params['tab_id'];

        $this->load->config('nav');
        $top_nav = $this->config->item('top_nav');
        $left_nav = $this->config->item('left_nav');
        $menues = array();

        if(!empty($top_nav)){
            foreach ($left_nav as $key => $value){
                if($params['menue_id'] == $value['id']){
                    foreach ($top_nav as $k => $v){
                        $query['site_id'] = $k;
                        $menues[$k]['title'] = $v;
                        $menues[$k]['base_url'] = $this->data['base_url'].'/'.$value['url'];
                        $menues[$k]['url'] = $menues[$k]['base_url'].'?'. http_build_query($query);
                        $menues[$k]['class'] = 'inactive';
                        if($params['site_id'] == $k){
                            $menues[$k]['class'] = 'active';
                        }
                    }
                }
            }
        }
        return $menues;
    }
    protected function create_cities_map(){

        $params = $this->get_query_params();
        $query['city_id'] = $params['city_id'];
        $query['site_id'] = $params['site_id'];
        $query['menue_id'] = $params['menue_id'];
        $query['tab_id'] = $params['tab_id'];

        $this->load->config('bi_cities');
        $cities = $this->config->item('cities');
        $left_menue = $this->create_left_nav();

        $request_uri = $this->input->server('REQUEST_URI');
        if($pos = strpos($request_uri,'?')){
            $request_uri = substr($request_uri, 0, $pos);
        }
        $base_url = C('config.base_url').$request_uri;
        $city_list = array();
        $break = FALSE;
        if(!empty($cities)){
            foreach ($cities as $key => $value){
                $query['city_id'] = $value['id'];
                $city_list[$key] = $value;
                $city_list[$key]['url'] = $base_url.'?'.http_build_query($query);
                $city_list[$key]['current'] = FALSE;
                if($params['city_id'] == $value['id']){
                    $city_list[$key]['current'] = TRUE;
                    $this->data['current_city'] = $value;
                }
            }

            if(!empty($left_menue)){
                foreach ($left_menue as $k => $v){
                    if ($query['menue_id'] ==$v['id'] && !$v['whole']) {
                        unset($city_list[0]);
                        if($params['city_id'] == C('open_cities.quanguo.id')){
                            $this->data['current_city'] = $city_list[1];
                        }

                    }
                }
            }

        }
        return $city_list;
    }
    protected function get_query_params(){
        $city_id      = $this->input->get('city_id');
        $site_id      = $this->input->get('site_id');
        $menue_id     = $this->input->get('menue_id');
        $tab_id       = $this->input->get('tab_id');
        $offest       = $this->input->get('offset');
        $page         = $this->input->get('page');


        $data['city_id']   = $city_id ?: C('open_cities.quanguo.id'); //默认全国
        $data['site_id']   = $site_id ?: C('app_sites.chu.id'); //默认大厨
        $data['menue_id']  = $menue_id ?: 1; //默认第一个菜单
        $data['tab_id']    = $tab_id ?: 1; //默认第一个tab
        $data['page']      = $page ? $page : 1; // 默认显示第一页
        $data['offset']    = $offest ? $offest : 10; // 默认每页10条纪录
        return $data;
    }
    
    protected function _get_privilege($role_id) {
        $data = $this->userauth->CI->MRole->get_one('*', array('id' => $role_id));
        if(empty($data)) {
            return FALSE;
        }

        // 根据权限ID获取权限列表
        $where['in'] = array('id' => explode(',', $data['pri_id']));
        $where['status'] = 1;
        $where['module'] = 'bi';
        $privilege = $this->userauth->CI->MPrivilege->get_lists('id, module, name, resource, operation, level', $where);
        if(empty($privilege)) {
            // 获取当前系统的权限为空，表明该用户所属角色没有操作该系统的权限
            header('HTTP/1.0 403 Forbidden');
            echo 'You are forbidden!';
            exit;
        }

        return $privilege;
    }

    protected function privilege2meues($privilege) {
        $params            = $this->get_query_params();
        $query['city_id']  = $params['city_id'];
        $query['site_id']  = $params['site_id'];
        $query['menue_id'] = $params['menue_id'];
        $query['tab_id']   = $params['tab_id'];

        $module_info       = $this->format_query('white_module/get_own_module', array('mobile' => $this->data['user_info']['mobile']));
        if (empty($module_info)) {
            return $module_info;
        }

        $menues = array();
        if (!empty($privilege)) {
            static $i = 1;
            foreach ($privilege as $key => $value){
                if ($value['level'] !== '1') {
                    continue;
                }
                foreach ($module_info as $k => $v) {
                    if ($value['name'] == $v['module_name']) {
                        $query['menue_id'] = $i;
                        $menues[$key]['id'] = $value['id'];
                        $menues[$key]['title'] = $value['name'];
                        $menues[$key]['url'] = $this->data['base_url'].'/'.$value['resource'].'/'.$value['operation'].'?'.http_build_query($query);
                        $menues[$key]['base_url'] = $this->data['base_url'].'/'.$value['resource'].'/'.$value['operation'];
                        $menues[$key]['class'] = 'inactive';
                        if($params['menue_id'] == $query['menue_id']){
                            $menues[$key]['class'] = 'active';
                        }
                        ++ $i;
                    }
                }
            }
        }

        return $menues;
    }

    protected function get_city($cities = array()) {
        // 获取当前用户可操作的城市
        if (empty($cities)) {
            return $cities;
        }
        $user_own_city = array();
        foreach ($cities as $key => $value) {
            if ($value['id'] == $this->data['user_info']['province_id']) {
                array_push($user_own_city, $cities[$key]);
            }
        }
        return $user_own_city;
    }
}













