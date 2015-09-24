<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MUser_app_binding'
            )
        );
    }

    /**
     * @description 初始化设备，传回设备的imei码，去app_binding表查询是否有相关记录
     */
    public function init_device() {
        if(empty($_POST['IMEI']) || empty($_POST['app_type'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty imei/app_type'
                )
            );
        }
        $imei_code = $_POST['IMEI'];
        $user_id = empty($_POST['user_id']) ? 0 : intval($_POST['user_id']);
        $app_type_id = intval($_POST['app_type']);

        $binding = $this->MUser_app_binding->get_one(
            '*',
            array(
                'imei_code'   => $imei_code,
                'app_type_id' => $app_type_id
            )
        );

        $time = $this->input->server('REQUEST_TIME');

        if(empty($binding)) {
            $user_encrypt = $this->_create_user_encrypt($imei_code);
            $binding_data = array(
                'imei_code'    => $imei_code,
                'user_id'      => $user_id,
                'app_type_id'  => $app_type_id,
                'created_time' => $time,
                'updated_time' => $time,
                'user_encrypt' => $user_encrypt,
                'user_encrypt_expire' => $time
            );
            $id = $this->MUser_app_binding->create(
                $binding_data
            );
        } else {
            $id = $binding['id'];
            $user_encrypt = $binding['user_encrypt'];
            if($time - $binding['user_encrypt_expire'] >  3600) {
                $user_encrypt = $this->_create_user_encrypt($imei_code);
                $data = array(
                    'user_encrypt'        => $user_encrypt,
                    'user_encrypt_expire' => $time
                );
                $this->MUser_app_binding->update($id, $data);
            }
        }

        $binding_id = $id;

        $this->_return_json(
            array(
                'status' => 0,
                'app_uid' => $binding_id,
                'user_encrypt' => $user_encrypt
            )
        );

    }

    /**
     * @description 生成用户密钥
     */
    private function _create_user_encrypt($imei_code) {
        $salt = $this->input->server('REQUEST_TIME'). mt_rand(1, 1000);
        return md5(md5($imei_code). $salt);
    }

    /**
     * @description 绑定用户user_id和app_uid
     */
    public function bind_app_and_user() {
        $user_id = empty($_POST['user_id']) ? 0 : intval($_POST['user_id']);
        $app_uid = empty($_POST['app_uid']) ? 0 : intval($_POST['app_uid']);
        if(!$user_id || !$app_uid) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty user_id or app_uid'
                )
            );
        }
        $this->MUser_app_binding->update_info(
            array(
                'user_id' => $user_id
            ),
            array(
                'id' => $app_uid
            )
        );
        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'bind success'
            )
        );
    }

    /**
     * 苹果设备向服务器传送设备ID，供推送使用
     * @author fengzongbao@dachuwang.com
     */
    public function init_ios()
    {
        $user_id      = $this->input->post('user_id');
        $device_token = $this->input->post('device_token');

        if(empty($user_id) || empty($device_token)) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'user_id or device_token is empty'
                )
            );
        }

        // 去掉首尾尖括号  中间的空格
        $device_token = preg_replace('/\s+|\>|\</', '', $device_token);

        // 将苹果设备token更新到数据库
        $result = $this->MUser_app_binding->update_info(
            array(
                'device_token' => $device_token
            ),
            array(
                'user_id' => $user_id
            )
        );

        if($result) {
            $this->_return_json(
                array(
                    'status' => 0,
                    'data'   => $this->input->post(),
                    'msg'    => 'success'
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status' => -1,
                    'data'   => $this->input->post(),
                    'msg'    => 'update failed'
                )
            );
        }
    }
}

/* End of file device.php */
/* Location: ./application/controllers/device.php */
