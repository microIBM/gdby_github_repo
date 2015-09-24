<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 与支付账单相关的服务
 * @author lenjent
 */
class Payment extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * @inerface:微信支付开放城市接口
     * @method:get|post
     * @author yuanxiaolin@dachuwang.com
     */
    public function open_cities(){
        $cities = C('payment.open');
        $city_ids = array();
        if(!empty($cities)){
            foreach ($cities as $key => $value){
                $city_ids[$value['id']] = $value['white_users'];
               //array_push($city_ids, $value['id']);
            }
        }
        $this->_return_json(array('status'=>0,'data'=>$city_ids));
    }
    
}
