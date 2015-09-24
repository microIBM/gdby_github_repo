<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 订单数据逻辑处理逻辑
 *
 * @author yuanxiaolin@dachuwang.com
 */
class DataModel extends MY_Model {
	
    public function __construct () {
        parent::__construct();
    }
    
    /**
     * 获取分拣扫码，配送扫码请求参数
     * @throws Exception
     * @return Ambigous <string, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_params(){
    	
    	$code_number = strtoupper($this->input->post('code_number'));
    	$operator_uid = $this->input->post('operator_uid');
    	
    	if(!empty($code_number)){
    		$prefix = C('barcode.prefix');
    		if(!empty($prefix['picking']) && substr_count($code_number,$prefix['picking'])>0){
    			$code_number = str_replace($prefix['picking'], '', $code_number);
    			$data['prefix'] = $prefix['picking'];
    		}elseif(!empty($prefix['dispatch']) && substr_count($code_number, $prefix['dispatch']) > 0){
    			$code_number = str_replace($prefix['dispatch'], '', $code_number);
    			$data['prefix'] = $prefix['dispatch'];
    		}
    		
    		$data['code_number'] = $code_number;
    	}else{
    		throw new Exception('code_number required');
    	}
    	
    	if(!empty($operator_uid)){
    		$data['operator_uid'] = $operator_uid;
    	}else{
    		throw new Exception('operator_uid required');
    	}
    	
    	return $data;
    }
    

}
/* End of file datamodel.php */
/* Location: :./application/models/datamodel.php */
