<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import_guolele extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MAnti_products'
            )
        );
    }

    /**
     * @description 导入果乐乐数据到t_anti_products
     */
    public function import($file_name = 'result.txt') {
        $contents = file_get_contents($file_name);
        $lines = explode("\r\n", $contents);
        foreach($lines as $line) {
            $values = explode('&&', $line);
            print_r($values);
            if(count($values) > 1) {
                $name = $values[0];
                $prod_id = $values[1];
                $price = doubleval($values[2]) * 100;
                $cates = $values[3];
                $prop = $values[4];
                if(preg_match('/\d+/', $values[5], $matches)) {
                    $values[5] = $matches[0];
                }
                $total_price = $values[5] * 100;
                $url = $values[6];
            }

            $anti_product = array(
                'site_id'      => 22,
                'prod_id'      => $prod_id,
                'price'        => $price,
                'cate'         => $cates,
                'name'         => $name,
                'prop'         => $prop,
                'total_price'  => $total_price,
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            );

            $history = $this->MAnti_products->get_one(
                '*',
                array(
                    'site_id' => 22,
                    'prod_id' => $prod_id
                )
            );

            if(empty($history)) {
                $this->MAnti_products->create(
                    $anti_product
                );
            } else {
                $this->MAnti_products->update_info(
                    array(
                        'price'       => $price,
                        'total_price' => $total_price,
                    ),
                    array(
                        'site_id' => 22,
                        'prod_id' => $prod_id
                    )
                );
            }
        }
    }
}

/* End of file import_guolele.php */
/* Location: ./application/controllers/import_guolele.php */
