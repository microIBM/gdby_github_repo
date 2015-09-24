<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * app版本更新
 * @author changshaoshuai@dachuwang.com
 * @since 2015-07-15
 */
class Apk extends MY_Controller {
    private $logined = FALSE;  //登录状态

    public function __construct() {
        parent::__construct();
        $this->load->model('MApk');
        $this->check_logined();
    }

    /**
     * 登录
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-14
     */
    public function login() {
        $return = array(
            'status' => 0,
            'msg' => '登录成功！'
        );
        if(empty($_POST)) {
            $this->_return_json(
                array('msg' => '参数不能为空!')
            );
        }
        $data = $this->input->post();
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $password = isset($data['password']) ? $data['password'] : '';
        //手机号验证
        if($mobile != C('apk.mobile')) {
            $this->_return_json(
                array('msg' => '手机号错误！')
            );
        }
        $login_result = $this->userauth->login($mobile, $password, FALSE, 'appstore'); 
        if(empty($login_result)) {
            $this->_return_json(
                array('登录失败！')
            );
        }

        $this->logined = TRUE;
        //版本显示页面
        $this->version_list();
    }

    /**
     * 显示版本登录页面
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-14
     */
    public function version_login() {
        $this->load->view('versionLogin');
    }

    /**
     * 显示版本提交页面
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-10
     */
    public function version_sub() {
        if($this->logined === FALSE) {
            return;
        }
        $this->load->view('versionSub');
    }

    /**
     * 显示版本信息
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-13
     */
    public function version_list() {
        if($this->logined === FALSE) {
            return;
        }

        $result['data'] = $this->MApk->get_lists(
            '',
            '',
            array('id' => 'desc')
        );
        $this->load->view('versionList', $result);
    }

    /**
     * 版本编辑
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-13
     */
    public function version_edit() {
        if($this->logined === FALSE) {
            return;
        }

        if(count($this->uri->segments) < 3) {
            $this->_return_json(
                array('msg' => '操作非法！')
            );
        }

        $data = array(
            'id' => $this->uri->segments['3'],
        );
        $result['data'] = $this->MApk->get_one(
            '*',
            array(
                'id' => $data['id']
            )
        );
        if(empty($result['data'])) {
            $this->_return_json(
                array('msg' => '不存在该版本!')
            );
        }
        $this->load->view('versionEdit', $result);
    }

    /**
     * 上传app包
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-10
     */
    public function add_version() {
        if($this->logined === FALSE) {
            return;
        }

        $return = array('status' => -1);
        $data = $this->input->post();
        if(empty($data) || !isset($data['ver_name']) || mb_strlen($data['ver_name']) > 50) {
            $return['msg'] = '版本名称不能为空或版本号过长';
            $this->_return_json($return);
        }

        if(empty(intval($data['ver_num']))) {
            $return['msg'] = '版本号只能填写数字';
            $this->_return_json($return);
        }

        if($data['update_type'] != 0 && $data['update_type'] != 1) {
            $return['msg'] = '更新类型只能填0或者1';
            $this->_return_json($return);
        }

        if(mb_strlen($data['update_content']) > 250) {
            $return['msg'] = '更新内容过长';
            $this->_return_json($return);
        }
        if($data['client_type'] == '0') {
            //安卓
            $down_url = $this->upload_file($_FILES);
            if(empty($down_url)) {
                $return['status'] = -2;
                $return['msg'] = '文件上传权限受限制!';
                $this->_return_json($return);
            }
        } else if ($data['client_type'] == '1') {
            //IOS
            if(empty(trim($data['down_url']))) {
                $return['msg'] = 'IOS下载地址不能为空!';
                $this->_return_json($return);
            }
            $down_url = trim($data['down_url']);
        }

        $data = array(
            'client_type'  => $data['client_type'],
            'version_name' => trim($data['ver_name']),
            'version_num'  => trim($data['ver_num']),
            'update_type'  => $data['update_type'],
            'update_txt'   => trim($data['update_content']),
            'down_url'     => $down_url,
            'dateline'     => $this->input->server('REQUEST_TIME'),
            'update_time'  => $this->input->server('REQUEST_TIME')
        );
        $id = $this->MApk->create($data);
        if($id > 0) {
            $this->version_list();
        }
    }


    /**
     * 检查app版本
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-13
     */
    public function check_app_ver() {
        $return = array(
            'status' => 0,
            'msg' => 'app版本数据'
        );
        $data = $this->input->post();
        if(!isset($data['token']) || !isset($data['versionCode']) || !isset($data['packageName'])) {
            $return['status'] = -1;
            $return['msg'] = 'error';
            $this->_return_json($return);
        }
        //校验token
        $token = md5($data['versionCode'] . $data['packageName']);
        if($token != $data['token']) {
            $this->_return_json(
                array(
                    'status' => -2,
                    'msg' => 'token不一致！'
                )
            );
        }

        if(!isset($data['clientType'])) {
            $this->_return_json(
                array(
                    'status' => -3,
                    'msg'    => '缺少clientType参数'
                )
            );
        }

        $client_type = trim($data['clientType']);
        $app_name = trim($data['packageName']);
        $app_num  = $data['versionCode'];
        $result = $this->MApk->get_lists(
            array('update_type', 'update_txt', 'down_url'),
            array(
                'client_type'  => $client_type,
                'version_name' => $app_name,
                'version_num > ' => $app_num,
                'status'       => 0
            ),
            array('id' => 'desc')
        );
        if(empty($result)) {
            $this->_return_json(
                array(
                    'status' => 0,
                    'update_type'   => C('apk.update_effect.no'),  //0强制更新, 1建议更新, 2不更新
                    'msg'    => '无须更新'

                )
            );
        }
        $down_url = $result['0']['down_url'];
        $update_txt = $result['0']['update_txt'];
        $update_type = C('apk.update_effect.suggest'); //1
        foreach($result as $val) {
            if($val['update_type'] == 0) {
                $update_type = C('apk.update_effect.enforce');  //0
                break;
            }
        }
        $this->_return_json(
            array(
                'status' => 0,
                'update_txt' => $update_txt,
                'update_type' => $update_type,
                'down_url' => $down_url
            )
        );
    }

    /**
     * 禁用
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-14
     */
    public function del_version() {
        if($this->logined === FALSE) {
            return;
        }

        if(count($this->uri->segments) < 4) {
            $this->_return_json(
                array('msg' => '操作非法')
            );
        }

        $id = $this->uri->segments['3'];
        //0:执行禁用操作 1:执行解禁操作
        $status = $this->uri->segments['4'] == '1' ? '0' : 1;
        $data = array(
            'status' => $status
        );
        $this->MApk->update_by('id', $id, $data);
        $this->version_list();
    }

    /**
     * 修改版本信息
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-13
     */
    public function update_version() {
        if($this->logined === FALSE) {
            return;
        }

        $return = array(
            'status' => 0,
            'msg' => '修改成功！'
        );
        $data = $this->input->post();
        $id = $data['id'];
        if(empty($id)) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg' => '修改失败!'
                )
            );
        }
        if(mb_strlen($data['ver_name']) > 50) {
            $this->_return_json(
                array('msg' => '版本名称过长!')
            );
        }
        if(empty($data['ver_num'])) {
            $this->_return_json(
                array('msg' => '版本号必须为数字')
            );
        }
        if($data['update_type'] != '0' && $data['update_type'] != '1') {
            $this->_return_json(
                array('msg' => '更新类型有误')
            );
        }

        if(empty($data['update_content']) || mb_strlen($data['update_content']) > 500) {
            $this->_return_json(
                array('msg' => '更新内容长度不合适!')
            );
        }

        $result = array(
            'client_type'  => trim($data['client_type']),
            'version_name' => trim($data['ver_name']),
            'version_num'  => intval(trim($data['ver_num'])),
            'update_type'  => $data['update_type'],
            'update_txt'   => trim($data['update_content']),
            'update_time'  => $this->input->server('REQUEST_TIME')
        );

        if($data['client_type'] == 1) {
            //IOS
            if(empty(trim($data['down_url']))) {
                $this->_return_json(
                    array('msg' => 'IOS下载地址不能为空！')
                );
            }
            $result['down_url'] = trim($data['down_url']);
        }

        $this->MApk->update_by('id', $id, $result);
        $this->version_list();
    }

    /**
     * 文件上传
     * @author changshaoshuai@dachuwang.com
     */
    private function upload_file($data) {
        if( ! empty($data['userfile']['name']))  {
            if($data['userfile']['error'] > 0) {
                switch($data['userfile']['error']) {
                    case 1:
                        echo '文件大小超过了限制';
                        break;
                    case 2:
                        echo '文件大小超过浏览器的限制';
                        break;
                    case 3:
                        echo '文件部分被上传';
                        break;
                    case 4:
                        echo '没有找到要上传的文件';
                        break;
                    case 5:
                        echo '服务器临时文件丢失，请重新上传';
                        break;
                    case 6:
                        echo '文件写入临时文件夹出错';
                        break;
                }
                exit;
            } else {
                $file = $data['userfile']['name'];
                //判断文件类型
                $is_apk = end(explode('.', $file)) == 'apk' ? TRUE : FALSE;
                if(! $is_apk && $data['userfile']['type'] != 'application/octet-stream') {
                    echo '仅支持apk包上传！';
                    exit;
                }
                //判断文件是否已经存在
                $target_file = FCPATH.'resource' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $file;
                if( ! file_exists($target_file)) {
                    $res = move_uploaded_file($data['userfile']['tmp_name'], $target_file);
                    if($res) {
                        return $_SERVER['HTTP_HOST'] . DIRECTORY_SEPARATOR . 'resource' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $file;
                    }
                } else {
                    echo "<script>alert('您上传的文件已经存在!')</script>";
                }
            }
        } else {
            echo "<script>alert('请上传文件!')</script>";
        }
    }

    /**
     * 检查登录状态
     * @author changshaoshuai@dachuwang.com
     * @since 2015-07-20
     */
    private function check_logined() {
        $method = $this->router->method;
        if($method == 'check_app_ver' || $method == 'login') {
            return;
        }
        $is_logined = $this->userauth->current(FALSE);
        if(! empty($is_logined)) {
            $this->logined = TRUE;
            return;
        }
        $this->version_login();
    }

}

/* End of file apk.php */
/* Location: ./application/controllers/apk.php */
