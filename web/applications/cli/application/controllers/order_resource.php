<?php

/**
 * 订单来源推荐
 *
 * @author wangzejun@dachuwang.com
 */
class Order_resource extends MY_Controller {
    const RESOURCE_IOS     = 1;  //订单来源ios
    const RESOURCE_ANDROID = 2;  //订单来源android
    const RESOURCE_CHU     = 3;  //订单来源chu
    const RESOURCE_MALL    = 4;  //订单来源mall

    private $cities = array();
    public function __construct() {
        parent::__construct();
        $this->cities = C("staticize_open_cities");
        $this->email_group = C('email_push_group');
    }
    
    /**
     * 
     * @author wangzejun@dachuwang.com
     */
    public function order_resource_count() {
        $data = $this->cli_query("order_bi/get_order_resource_count", array('timeout' => 1000));
        $all_count = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);     //初始化全国各个来源（iOS,android,chu,mall)订单量
        $city_order_count = array();
        if(!empty($data)) {
            foreach ($data as $key => &$value) {
                $all_count[$value['order_resource']] += $value['cnt'];
                if ($value['city_id'] == $this->cities['all']['code']) {
                    unset($data[$key]);
                    continue;
                }
                foreach ($this->cities as $index => $city) {
                    if ($city['code'] == $value['city_id']) {
                        $value['city_name'] = $city['name'];
                        continue 2;
                    }
                }
            }

            //将全国来源订单统计格式化
            foreach ($all_count as $k => $val) {
                array_push($data, 
                        array(
                            'city_id' => $this->cities['all']['code'],
                            'cnt' => $val,
                            'order_resource' => $k,
                            'city_name' => $this->cities['all']['name']
                        )
                );
            }
        }
        $city_order_count = $this->_format_data($data);
        $this->_send($city_order_count);
    }
    
    private function _format_data($data) {
        $format_data = array();
        foreach ($this->cities as $city) {
            $format_data[$city['code']] = array('city_name' => $city['name'], 'ios' => 0, 'android' => 0, 'chu' => 0, 'mall' => 0);
        }
        if (empty($data)) {
            return $format_data;
        }
        foreach ($data as $key => $val) {
            $format_data[$val['city_id']]['city_name'] = $val['city_name'];
            
            switch ($val['order_resource']) {
                case self::RESOURCE_IOS :
                    $format_data[$val['city_id']]['ios'] = $val['cnt'];
                    break;
                case self::RESOURCE_ANDROID :
                    $format_data[$val['city_id']]['android'] = $val['cnt'];
                    break;
                case self::RESOURCE_CHU :
                    $format_data[$val['city_id']]['chu'] = $val['cnt'];
                    break;
                case self::RESOURCE_MALL :
                    $format_data[$val['city_id']]['mall'] = $val['cnt'];
                    break;
            }
        }
        return $format_data;
    }
    
    private function _send($content) {
        $header = array(
            '城市',
            'ios',
            'android',
            'chu',
            'mall'
        );

        $result = $this->cli_query('email_report/send', array(
        	'to'      => $this->email_group['mall_email_group']['to'],
                'cc'      => $this->email_group['mall_email_group']['cc'],
                'name'    => $this->email_group['mall_email_group']['name'],
                'subject' => $this->email_group['mall_email_group']['subject'],
        	'title'   => $this->email_group['mall_email_group']['title'],
                'desc'    => $this->email_group['mall_email_group']['desc'],
        	'header'  => $header,
        	'content' => $content,
        ));
    }
}

/* End of file order_resource.php */
/* Location: ./application/controllers/order_resource.php */