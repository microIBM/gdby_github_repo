<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . "/third_party/Requests-1.6.0/Requests.php";

class Dachu_request {

    public function __construct () {
        Requests::register_autoloader();
        $this->_options = array(
            'timeout' => 100,
        );
    }

    public function request($request_type = 'post',$url = '', $data = array(), $headers = array()) {
        $options = $this->_options;
        $res = [
            'status' => -1,
            'res' => []
        ];

        try{
            switch($request_type) {
            case 'post':
                $response = Requests::post($url, $headers, $data, $options);
                break;
            case 'get':
                $response = Requests::request($url, $headers, $data, Requests::GET, $options);
                break;
            default:
                $response = Requests::post($url, $headers, $data, $options);
                break;
            }

            if($response->status_code == 200) {
                $res = [
                    'status' => 0,
                    'msg' => 'success',
                    'res' => trim($response->body),
                ];
            } else {
                $res = [
                    'status' => -1,
                    'msg' => 'fail',
                    'res' => trim($response->body),
                ];
            }

        } catch(Exception $e) {
            $res = [
                'status' => -1,
                'msg' => $e->getMessage(),
                'res' => '',
            ];
        }
        return $res;
    }

    public function post($url = '', $data = [], $headers = []) {
        return $this->request('post', $url, $data, $headers);
    }

    public function get($url = '', $data = [], $headers = []) {
        return $this->request('get', $url, $data, $headers);
    }
}

/* End of file dachu_request.php */
/* Location: ./application/controllers/dachu_request.php */
