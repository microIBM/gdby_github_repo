<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Http {

    public function __construct() {
        $this->CI = &get_instance();
    }
   /**
    * @author: liaoxianwen@ymt360.com
    * @description curl 查询内网服务
    */
    public function query($url, $data = array()) {
        $ch = curl_init ();
        if(is_array($data)  || is_object($data)){
            if(empty($data['timeout'])) {
                $timeout = 5;
            } else {
                $timeout = intval($data['timeout']);
            }
            $data = http_build_query($data);
        }
        $timeout = empty($timeout) ? 5 : $timeout;
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data);
        $headers = [];
        if(isset($_SERVER['HTTP_USER_AGENT']))
        {
            $headers[]='User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
        }
        if(isset($_SERVER['HTTP_SOCKETLOG']))
        {
            $headers[]='Socketlog: '.$_SERVER['HTTP_SOCKETLOG'];
        }
        if(!empty($headers)) {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        }
        $return = curl_exec ( $ch );
        if($return === false) {
            echo 'Curl error: ' . curl_error($ch);
            return FALSE;
        }
        curl_close ( $ch );
        return $return;
    }

}

/* End of file  cate_logic.php*/
/* Location: :./application/libraries/cate_logic.php/ */
