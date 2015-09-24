<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * redis client
 */
class RedisClient {
    private $conn = NULL;
    private $_host = NULL;
    private $_port = NULL;

    private $pool = array();
    private $starup_size = 10;
    private $min_size = 10;
    private $max_size = 50;


    public function __construct(){
        $CI =& get_instance();
        $CI->config->load('redis', TRUE, TRUE);
        $conf = $CI->config->config['redis'];
        $this->_host = $conf['host'];
        $this->_port = $conf['port'];
    }

    private function create_connection(){
        $conn = new Redis();
        $conn->connect($this->_host, $this->_port, 0) or die ("Could not connect to redis");

        return $conn;
    }

    private function close_connection($conn){
        if($conn){
            $conn->close();
        }
    }


    private function get_resource(){
        $conn = array_pop($this->pool);
        if(!$conn){
            $conn = $this->create_connection(); 
        }

        return $conn;
    }


    private function put_resource($conn){
        if($conn){
            if(count($this->pool) < $this->max_size){
                array_push($this->pool,$conn);
            }else{
                $this->close_connection($conn);
            }
        }
    }

    public function lrange($key, $start,$end,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->lrange($key, $start, $end);
        $this->put_resource($conn);
        return $res;
    }

    public function llen($key,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->llen($key);
        $this->put_resource($conn);
        return $res;
    }

    public function lpush($key, $val,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        //支持直接插数组
        if(is_array($val)) {
            foreach($val as $item) {
                $res = $conn->lpush($key, $item);
            }
        } else {
            $res = $conn->lpush($key, $val);
        }

        $this->put_resource($conn);
        return $res;
    }

    public function rpush($key, $val,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->rpush($key, $val);
        $this->put_resource($conn);
        return $res;
    }

    public function brpop($key, $timeout=30,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->brpop($key, $timeout);
        $this->put_resource($conn);
        return $res;
    }

    public function rpop($key,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->rpop($key);
        $this->put_resource($conn);
        return $res;
    }

    public function lpop($key,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->lpop($key);
        $this->put_resource($conn);
        return $res;
    }

    public function hincr($key, $field,$increment = 1, $db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->hincrby($key, $field, $increment);
        $this->put_resource($conn);
        return $res;
    }

    public function hexists ($key, $field, $db = 0) {
        $conn = $this->get_resource();
        $conn->select($db);
        
        $res = $conn->hExists($key, $field);
        $this->put_resource($conn);
        return $res;
    }
    
    public function hreset($key, $field,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->hdel($key, $field);
        $this->put_resource($conn);
        return $res;
    }

    public function hset($key, $field, $value,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->hset($key, $field, $value);
        $this->put_resource($conn);
        return $res;
    }

    public function keys($pattern, $db=0) {
        $conn = $this->get_resource();
        $conn->setlect($db);

        $res = $conn->keys($pattern);
        $this->put_resource($conn);
        return $res;
    }

    public function hkeys($key,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->hkeys($key);
        $this->put_resource($conn);
        return $res;
    }

    public function hget($key, $field,$db=0) {
        $conn = $this->get_resource();
        $conn->select($db);
        if(is_array($field)){
            $res = $conn->hmget($key, $field);
        }else{
            $res = $conn->hget($key, $field);
        }
        $this->put_resource($conn);
        return $res;
    }

    public function sadd($key,$value,$db=0){
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->sadd($key, $value);
        $this->put_resource($conn);
        return $res;
    }


    public function set($key,$value,$db=0){
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->set($key, $value);
        $this->put_resource($conn);
        return $res;
    }

    public function mset($arr, $db=0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->mset($arr);
        $this->put_resource($conn);
        return $res;
    }

    public function get($key,$db = 0){
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->get($key);
        $this->put_resource($conn);
        return $res;
    }

    public function mget($key_array, $db = 0) {
        $conn = $this->get_resource();
        $conn->select($db);

        $res = $conn->mget($key_array);
        $this->put_resource($conn);
        return $res;
    }

    public function zrevrange($key,$start,$stop,$WITHSCORES=FALSE,$db = 0){
        $conn = $this->get_resource();
        $conn->select($db);

        if($WITHSCORES){
            $res = $conn->zrevrange($key,$start,$stop,'WITHSCORES');
        }else{
            $res = $conn->zrevrange($key,$start,$stop);
        }
        $this->put_resource($conn);
        return $res;
    }

    public function zrangebyscore($key,$min,$max,$WITHSCORES=FALSE,$limit=10,$offset=0,$db = 0){
        $conn = $this->get_resource();
        $conn->select($db);

        if($WITHSCORES){
            $res = $conn->zrangebyscore($key,$start,$stop,'WITHSCORES',$limit,$offset);
        }else{
            $res = $conn->zrangebyscore($key,$start,$stop);
        }
        $this->put_resource($conn);
        return $res;
    }

    // 支持事务transaction
    public function trans_set($key, $val) {
        $conn = $this->get_resource();
        $conn->multi()->set($key, $val)->exec();
    }

    public function multi_trans_set($queues) {
        $conn = $this->get_resource();
        $trans = $conn->multi();
        foreach($queues as $key => $val) {
            $trans->set($key, $val);
        }
        $trans->exec();
    }
    // 支持事务获取
    public function trans_get($key) {
        $conn = $this->get_resource();
        $res = $conn->multi()->get($key)->exec();
        return $res[0];
    }
}
