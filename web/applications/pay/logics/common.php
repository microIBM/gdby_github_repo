<?php

require_once '../../config/header.php';
require_once 'lib/http.php';
require_once 'lib/helper.php';
require_once 'lib/log.php';

if (defined('PAY_ENV') && PAY_ENV == 'test') {
    require_once '../../config/test.php';
}else{
    require_once '../../config/wxpay.php';
}

/**
 * 业务逻辑处理基类
 * @author yuanxiaolin@dachuwang.com
 */
class Common {
    
    /**
     * 接口调用的统一封装
     * @param string $url 接口地址
     * @param unknown $post_data post参数
     * @param string $type 请求类型
     * @throws Exception
     * @return Ambigous <multitype:, mixed>
     * @author yuanxiaolin@dachuwang.com
     */
    public static function DoApi ($url = '', $post_data = array(), $type = "GET",$cookie = array()) {
        
        $response = array ();
        if (empty($url)) {
            throw new Exception('url required,but empty be given');
        } else {
            $full_respose = Http::request($url, $post_data, $type,$cookie);
            $response = json_decode($full_respose, TRUE);
            
            if ($response === null) {
                $log = self::LogInit();
                $log::ERROR(sprintf('api call error|api_url:%s|api_back:%s', $url, $full_respose));
            }
        }
        
        return $response != null ? $response : array ();
    }
    
    /**
     * 初始化日志记录对象
     * @return Log
     * @author yuanxiaolin@dachuwang.com
     */
    public static function LogInit () {
        $logHandler = new CLogFileHandler(Config::PAY_LOG_PATH . date('Y-m-d') . '.log');
        return Log::Init($logHandler, Config::PAY_LOG_LEVER);
    }
    
    public static function MakeSign($data = array()){
        
        $must = array('appid' =>'','noncestr' =>'','package'=>'','partnerid'=>'','prepayid'=>'','timestamp'=>'');
        $must_arr = array();
        if(is_array($data) && !empty($data)){
            foreach ( $data as  $key => $value){
                if (in_array($key, $must)) {
                     $must_arr[$key] = $value;
                }
            }
        }
        //签名步骤一：按字典序排序参数
        ksort($must_arr);
        $string = self::ToUrlParams($must_arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".WxPayConfig::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
    /**
     * 组装生成签名所需的URL参数
     * @param unknown $data
     * @return string
     * @author yuanxiaolin@dachuwang.com
     */
    public static function ToUrlParams($data = array()){
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
        
    }
    
    /**
     * 接口成功返回的封装
     * @param unknown $data
     * @author yuanxiaolin@dachuwang.com
     */
    public static function Success($data = array()){
        $return['status'] = Config::API_SUCCESS_CODE;
        $return['msg'] = 'success';
        $return['data'] = $data;
        echo json_encode($return);exit;
    }
    
    /**
     * 接口失败返回的封装
     * @param unknown $data
     * @author yuanxiaolin@dachuwang.com
     */
    public static function Failed($data = array()){
        $return['status'] = Config::API_FAILED_CODE;
        $return['msg'] = 'error';
        $return['data'] = $data;
        echo json_encode($return);exit;
    }
}