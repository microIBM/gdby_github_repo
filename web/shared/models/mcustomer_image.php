<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户图片操作model
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-04-17
 */
class MCustomer_image extends MY_Model {
    use MemAuto;

    private $table = 't_customer_image';

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 批量创建
     * @author yugang@dachuwang.com
     * @since 2015-04-17
     */
    public function create_imgs($pic_urls, $owner_id, $owner_type) {
        $data = array();
        foreach ($pic_urls as $pic_url) {
            $data[] = array(
                'owner_type'   => $owner_type,
                'owner_id'     => $owner_id,
                'url'          => $pic_url,
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
                'status'       => C('status.common.success'),
            );
        }
        $this->create_batch($data);
    }

}

/* End of file mcustomer_log.php */
/* Location: :./application/models/mcustomer_log.php */
