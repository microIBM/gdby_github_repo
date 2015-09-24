<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 条形码接口
 * @author yuanxiaolin
 *
 */
class Barcode extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }
    public function get(){
    	$data['text'] = $this->input->get('text');
    	$data['thickness'] = $this->input->get('thickness');
    	$data['scale'] = $this->input->get('scale');
    	//$data['source'] = $this->input->get('source');
    	$query_string = http_build_query($data);
    	header('Content-Type: image/png');
    	echo file_get_contents($this->get_api_url($query_string));
    }
    private function get_api_url($query_string){
    	return sprintf('%s/barcode/get?%s',$this->_service_url,$query_string);
    }
}
/* End of file barcode.php */
/* Location: :./application/controllers/barcode.php */
