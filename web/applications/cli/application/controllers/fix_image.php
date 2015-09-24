<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_image extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MPotential_customer',
                'MCustomer_image',
            )
        );
    }

    /**
     * 修复历史客户图片异常问题
     * @author yugang@dachuwang.com
     * @since 2015-05-29
     */
    public function fix_customer_image() {
        $list = $this->MCustomer->get_lists(
            '*'
        );

        $count = 0;
        foreach($list as $item) {
            // 查询与该客户手机号相同的潜在客户
            $pc = $this->MPotential_customer->get_one('*', ['mobile' => $item['mobile']]);
            if(empty($pc)) {
                continue;
            }

            // 查询该潜在客户对应的图片列表
            $cimg_list = $this->MCustomer_image->get_lists('*', ['owner_id' => $item['id'], 'owner_type' => 2, 'status' => 1]);
            if(!empty($cimg_list)) {
                continue;
            }
            $img_list = $this->MCustomer_image->get_lists('*', ['owner_id' => $pc['id'], 'owner_type' => 1]);

            foreach ($img_list as $img) {
                $data = [];
                $data['owner_type'] = 2;
                $data['owner_id'] = $item['id'];
                $data['url'] = $img['url'];
                $data['created_time'] = $this->input->server("REQUEST_TIME");
                $data['updated_time'] = $this->input->server("REQUEST_TIME");
                $data['status'] = 1;
                // 插入客户对应的图片数据
                $insert_id = $this->MCustomer_image->create($data);
                echo $insert_id . ' ';
                $count++;
            }

        }
        echo $count . "user img added,done\n";
    }

}

/* End of file fix_image.php */
/* Location: ./application/controllers/fix_image.php */
