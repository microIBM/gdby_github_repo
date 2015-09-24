<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* 静态化辅助类
* @author zhangxiao@dachuwang.com
* @version: 1.0.0
* @since: 2015-7-22
*/
class Staticize {
    //访问地址域名
    private $_service_url;
    //获取地址路径
    private $data_req_url;
    //写入地址路径
    private $date_send_url;
    //写入的表的表名
    private $t_name;
    //数据时间开始点
    private $stime;
    //数据时间结束点
    private $etime;
    //数据循环开始点
    private $stime_temp;
    //数据循环结束点
    private $etime_temp;
    //请求的参数
    private $req_params;
    //错误信息
    private $error_msg = array();
    //成功信息
    private $success_msg = array(
        'status'       => 0,
        'type'         => '',
        'insert_count' => 0,
        'update_count' => 0,
        'params'       => array()
    );

    const ONE_DAY   = 86400;
    const ONE_WEEK  = 604800;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library('Http');
        $this->_cli_service_url = C('service.cli');
    }

    /**
    * 按天静态化数据
    * @author zhangxiao@dachuwang.com
    */
    public function by_day() {
        $stime_min = strtotime(date('Y-m-d', $this->stime));
        $etime_max = strtotime(date('Y-m-d', $this->etime).' 23:59:59');

        for($i = $stime_min; $i <= $etime_max; $i += self::ONE_DAY) {
            $this->success_msg['insert_count'] = 0;
            $this->success_msg['update_count'] = 0;
            $this->stime_temp = $i;
            $this->etime_temp = $i + self::ONE_DAY;
            echo date('Y-m-d', $i);
            $data_req = $this->_get_data();
            print_r($data_req);
            if($data_req['status'] === 0) {
                $post_param = array(
                    'table' => $this->t_name,
                    'data'  => $data_req['data']
                );
                $return = $this->_send_data($post_param);
                print_r($return);
                if($return['status'] !== 0) {
                    $this->_assemble_error_msg('staticize/by_day', '写入数据');
                    log_message('error', 'cli--by_day--'.$this->date_send_url.'--'.print_r($return, true));
                } else {
                    $this->success_msg['insert_count'] += $return['insert_count'];
                    $this->success_msg['update_count'] += $return['update_count'];
                    $this->success_msg['params'] = array_merge($this->req_params, array(
                        'stime' => $this->stime_temp,
                        'etime' => $this->etime_temp,
                        'operation' => '按天'
                    ));
                }
            } else {
                $this->_assemble_error_msg('staticize/by_day', '获取数据');
                log_message('error', 'cli--by_day--'.$this->data_req_url.'--'.print_r($data_req, true));
            }

        }
        if(!empty($this->error_msg)){
            return $this->_error_msg($this->error_msg);
        }else{
            $this->success_msg['type'] = $return['type'];
            return $this->_success_msg();
        }
    }

    /**
    * 按周静态化数据
    * @author zhangxiao@dachuwang.com
    */
    public function by_week() {
        $stime_min = $this->_get_week_time($this->stime)['stime'];
        $etime_max = $this->_get_week_time($this->etime)['etime'];

        for($i = $stime_min; $i < $etime_max; $i += self::ONE_WEEK) {
            $this->success_msg['insert_count'] = 0;
            $this->success_msg['update_count'] = 0;
            $this->stime_temp = $i;
            $this->etime_temp = $i + self::ONE_WEEK;
            echo date('Y-m-d', $i);
            $data_req = $this->_get_data();
            print_r($data_req);
            if($data_req['status'] === 0) {
                if(empty($data_req)){
                    continue;//传入的category没有数据,跳过处理
                }
                $return = $this->_send_data(array(
                    'table' => $this->t_name,
                    'data'  => $data_req['data']
                ));
                print_r($return);
                if($return['status'] !== 0) {
                    $this->_assemble_error_msg('staticize/by_week', '写入数据');
                    log_message('error', 'cli--by_week--'.$this->date_send_url.'--'.print_r($return, true));
                } else {
                    $this->success_msg['insert_count'] += $return['insert_count'];
                    $this->success_msg['update_count'] += $return['update_count'];
                    $this->success_msg['params'] = array_merge($this->req_params, array(
                        'stime' => $this->stime_temp,
                        'etime' => $this->etime_temp,
                        'operation' => '按周'
                    ));
                }
            } else {
                $this->_assemble_error_msg('staticize/by_week', '获取数据');
                log_message('error', 'cli--by_week--'.$this->data_req_url.'--'.print_r($data_req, true));
            }
        }
        if(!empty($this->error_msg)){
            return $this->_error_msg($this->error_msg);
        }else{
            $this->success_msg['type'] = $return['type'];
            return $this->_success_msg();
        }
    }

    /**
    * 按月静态化数据
    * @author zhangxiao@dachuwang.com
    */
    public function by_month() {
        $stime_min = strtotime(date('Y-m' ,$this->stime));
        $etime_max = strtotime("+1 month", strtotime(date('Y-m' ,$this->etime)));

        while($stime_min < $etime_max) {
            $this->success_msg['insert_count'] = 0;
            $this->success_msg['update_count'] = 0;
            $this->stime_temp = $stime_min;
            $this->etime_temp = strtotime("+1 month", $stime_min);
            echo date('Y-m-d', $this->stime_temp);
            $data_req = $this->_get_data();
            print_r($data_req);
            if($data_req['status'] === 0) {
                $return = $this->_send_data(array(
                    'table' => $this->t_name,
                    'data'  => $data_req['data']
                ));
                print_r($return);
                if($return['status'] !== 0) {
                    $this->_assemble_error_msg('staticize/by_month', '写入数据');
                    log_message('error', 'cli--by_month--'.$this->date_send_url.'--'.print_r($return, true));
                } else {
                    $this->success_msg['insert_count'] += $return['insert_count'];
                    $this->success_msg['update_count'] += $return['update_count'];
                    $this->success_msg['params'] = array_merge($this->req_params, array(
                        'stime' => $this->stime_temp,
                        'etime' => $this->etime_temp,
                        'operation' => '按月'
                    ));
                }
            } else {
                $this->_assemble_error_msg('staticize/by_month', '获取数据');
                log_message('error', 'cli--by_month--'.$this->data_req_url.'--'.print_r($data_req, true));
            }
            $stime_min = $this->etime_temp;
        }
        if(!empty($this->error_msg)){
            return $this->_error_msg($this->error_msg);
        }else{
            $this->success_msg['type'] = $return['type'];
            return $this->_success_msg();
        }
    }
    
    /**
     * 组装错误信息
     * @author zhangxiao@dachuwang.com
     */
    private function _assemble_error_msg ($method, $operation) {
        array_push($this->error_msg, array(
            'req_params' => $this->req_params,
            'stime'      => $this->stime_temp,
            'etime'      => $this->etime_temp,
            'method'     => $method,
            'operation'  => $operation
        ));
    }

    /**
     * 获取某个时间所在周的周一的时间戳和周日的时间戳
     * @author zhangxiao@dachuwang.com
     */
    private function _get_week_time($stime_stamp) {

        $day_week_num = date('w', $stime_stamp);
        if($day_week_num == 0) {
            $day_diff_monday = $day_week_num + 6;
        } else {
            $day_diff_monday = $day_week_num - 1;
        }
        $week_stime = strtotime("-".$day_diff_monday." day", $stime_stamp);
        $week_etime = strtotime("+6 days" ,$week_stime);
        return array(
                'stime' => $week_stime,
                'etime' => $week_etime
        );
    }

    /**
     * 返回错误信息
     * @author zhangxiao@dachuwang.com
     */
    private function _error_msg($return) {
        return array(
            'status'     => -1,
            'msg'        => $return,
        );
    }

    /**
     * 返回成功信息
     * @author zhangxiao@dachuwang.com
     */
    private function _success_msg() {
        return $this->success_msg;
    }

    /**
     * 获取数据
     * @author zhangxiao@dachuwang.com
     */
    private function _get_data() {
        $params = array_merge($this->req_params, array(
            "stime" => $this->stime_temp,
            "etime" => $this->etime_temp
        ));

        $data = $this->cli_query($this->data_req_url, $params);
        return $data;
    }

    /**
     * 写入数据
     * @author zhangxiao@dachuwang.com
     */
    private function _send_data($data_req) {
        $return = $this->cli_query($this->date_send_url, $data_req);
        return $return;
    }

    /**
     * 设置：请求参数
     * @author zhangxiao@dachuwang.com
     */
    public function set_request_params($params = array()) {
        $this->req_params = $params;
    }

    /**
     * 设置：获取数据的地址
     * @author zhangxiao@dachuwang.com
     */
    public function set_data_req_url($data_req_url) {
        $this->data_req_url = $data_req_url;
    }

    /**
     * 设置：发送数据的地址
     * @author zhangxiao@dachuwang.com
     */
    public function set_data_send_url($date_send_url) {
        $this->date_send_url = $date_send_url;
    }

    /**
     * 设置：写入表的表名
     * @author zhangxiao@dachuwang.com
     */
    public function set_table_name($t_name) {
        $this->t_name = $t_name;
    }

    /**
     * 设置：开始时间点
     * @author zhangxiao@dachuwang.com
     */
    public function set_stime($stime) {
        $this->stime = strtotime(date("Y-m-d", $stime));
    }

    /**
     * 设置：结束时间点
     * @author zhangxiao@dachuwang.com
     */
    public function set_etime($etime) {
        $this->etime = strtotime(date("Y-m-d", $etime)." 23:59:59");
    }

    /**
    *  访问外域服务的http封装
    *  @author zhangxiao@dachuwang.com
    */
    public function cli_query($uri_string, $data = array()) {
        $url = $this->_cli_service_url . '/' . $uri_string;
        $data['timeout'] = 1000;
        $return_data = $this->CI->http->query($url, $data);
        return json_decode($return_data, TRUE);
    }

}

/* End of file  staticize.php*/
/* Location: :./application/libraries/staticize.php/ */
