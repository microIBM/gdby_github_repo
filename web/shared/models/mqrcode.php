<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * qrmodel
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class MQrcode extends MY_Model {
    use MemAuto;
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取提货qr码
     */
    public function qrcode($filename) {
        if($filename) {
            $info = array(
                'status'    => TRUE,
                'thqrcode'  => 'http://img.ymt360.com/qrcode/' . $filename
            );
        } else {
            $info =  array(
                'status'  => FALSE,
                'message' => 'invalid'
            );
        }
        $this->_return_json($info);
    }
}

/* End of file mqrcode.php */
/* Location: :./application/models/mqrcode.php */
