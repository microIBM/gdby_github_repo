<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * decription 条码服务类
 * @author yuanxiaolin@dachuwang.com
 */
// Including all required classes
define('BARCODE_DIR', APPPATH.'libraries/barcode');
require_once(BARCODE_DIR.'/class/BCGFontFile.php');
require_once(BARCODE_DIR.'/class/BCGColor.php');
require_once(BARCODE_DIR.'/class/BCGDrawing.php');

require_once(BARCODE_DIR.'/class/BCGcode128.barcode.php');

class Barcode extends MY_Controller {
    
    public function __construct () {
        parent::__construct();
    }
    /**
     * 获取条形码服务
     * 用法：http://s.dachuwang.com/barcode/get?text=2015081923456&thickness=70&scale=1&source=F
     * @param string text 条码内容，必须
     * @param string source 条码类型，用于区分不同场景的扫码，必须
     * @param number thickness 条码高度（20-90）,默认为50
     * @param number scale 条码大小（1-4）, 默认为2
     * @author yuanxiaolin@dachuwang.com
     */
    public function get(){
    	
    	try {
    		$this->load->model('DataModel','datamodel');
    		$params = $this->datamodel->barcode_params();
    		$colorFront = new BCGColor(0, 0, 0);
    		$colorBack = new BCGColor(255, 255, 255);
    		//$font = new BCGFontFile(BARCODE_DIR.'/font/Arial.ttf', 14);
    		$code = new BCGcode128(); 
    		$code->setScale($params['scale']); // 条码显示大小：1-4，默认为2
    		$code->setThickness($params['thickness']); // 条码显示高度：20-90，默认50
    		$code->setForegroundColor($colorFront); // 条码颜色为黑色
    		$code->setBackgroundColor($colorBack); // 条码背景为白色
    		//$code->setFont($font);
    		$code->parse($params['text']); // 条码内容
    		
    		$drawing = new BCGDrawing('', $colorBack);
    		$drawing->setBarcode($code);
    		$drawing->draw();
    		//header('Content-Type: image/png');
    		$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    	} catch (Exception $e) {
    		echo $e->getMessage();
    	}
    }
}
