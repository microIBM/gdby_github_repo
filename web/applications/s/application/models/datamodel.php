<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 数据逻辑处理model
 *
 * @author yuanxiaolin@dachuwang.com
 */
class DataModel extends MY_Model {
    const PICK_ORDER = 'F';  //分拣订单标识
    const TRANCE_ORDER = 'P';//配送订单标识
    const DELIMITER ='-';//条码内容连接符
    public function __construct () {
        parent::__construct();
    }
    
    public function barcode_params(){
    	$data['text'] = $this->input->get('text');
    	$data['thickness'] = $this->input->get('thickness');
    	$data['scale'] = $this->input->get('scale');
     	
    	if(empty($data['text'])){
    		throw new Exception('params text required');
    	}
    	
    	if($data['thickness']  <20 || $data['thickness']  >90 || empty($data['thickness'])){
    		$data['thickness']  = 50;
    	}
    	if($data['scale'] < 1 || $data['scale'] > 4 || empty($data['scale'])){
    		$data['scale'] = 2;
    	}
    	return $data;
    }

}
/* End of file datamodel.php */
/* Location: :./application/models/datamodel.php */
