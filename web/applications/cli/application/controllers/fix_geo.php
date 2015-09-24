<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_geo extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MPotential_customer',
            )
        );
    }

    /**
     * 将用户的geo信息分别存储
     * @author yugang@dachuwang.com
     * @since 2015-05-25
     */
    public function fix_customer_geo() {
        $list = $this->MCustomer->get_lists(
            '*'
        );

        $count = 0;
        foreach($list as $item) {
            $geo = $item['geo'];
            $lng = '';
            $lat = '';
            if (!empty($geo)) {
                $geo = json_decode($geo, TRUE);
                $lng = isset($geo['lng']) ? $geo['lng'] : '';
                $lat = isset($geo['lat']) ? $geo['lat'] : '';
            }

            $this->MCustomer->update_info(
                array(
                    'lng'  => $lng,
                    'lat' => $lat,
                ),
                array(
                    'id' => $item['id']
                )
            );
            $count++;
        }
        echo $count . "user edit,done\n";
    }

    /**
     * 将潜在用户的geo信息分别存储
     * @author yugang@dachuwang.com
     * @since 2015-05-25
     */
    public function fix_potential_customer_geo() {
        $list = $this->MPotential_customer->get_lists(
            '*'
        );

        $count = 0;
        foreach($list as $item) {
            $geo = $item['geo'];
            $lng = '';
            $lat = '';
            if (!empty($geo)) {
                $geo = json_decode($geo, TRUE);
                $lng = isset($geo['lng']) ? $geo['lng'] : '';
                $lat = isset($geo['lat']) ? $geo['lat'] : '';
            }

            $this->MPotential_customer->update_info(
                array(
                    'lng'  => $lng,
                    'lat' => $lat,
                ),
                array(
                    'id' => $item['id']
                )
            );
            $count++;
        }
        echo $count . "user edit,done\n";
    }

    /**
     * 将用户的方位和规模信息存储为普通字符串
     * @author yugang@dachuwang.com
     * @since 2015-05-29
     */
    public function fix_customer_json() {
        $list = $this->MCustomer->get_lists(
            '*'
        );

        $count = 0;
        foreach($list as $item) {
            $direction = $item['direction'];
            if (!empty($direction)) {
                $direction = json_decode($direction, TRUE);
                $direction = $direction['value'];
            }else{
                $direction = '';
            }

            $dimensions = $item['dimensions'];
            if (!empty($dimensions)) {
                $dimensions = json_decode($dimensions, TRUE);
                $dimensions = $dimensions['value'];
            }else{
                $dimensions = '';
            }

            $this->MCustomer->update_info(
                array(
                    'direction'  => $direction,
                    'dimensions' => $dimensions,
                ),
                array(
                    'id' => $item['id']
                )
            );
            $count++;
        }
        echo $count . "user edit,done\n";
    }

    /**
     * 将潜在用户的方位和规模信息存储为普通字符串
     * @author yugang@dachuwang.com
     * @since 2015-05-29
     */
    public function fix_potential_customer_json() {
        $list = $this->MPotential_customer->get_lists(
            '*'
        );

        $count = 0;
        foreach($list as $item) {
            $direction = $item['direction'];
            if (!empty($direction)) {
                $direction = json_decode($direction, TRUE);
                $direction = $direction['value'];
            }else{
                $direction = '';
            }

            $dimensions = $item['dimensions'];
            if (!empty($dimensions)) {
                $dimensions = json_decode($dimensions, TRUE);
                $dimensions = $dimensions['value'];
            }else{
                $dimensions = '';
            }

            $this->MPotential_customer->update_info(
                array(
                    'direction'  => $direction,
                    'dimensions' => $dimensions,
                ),
                array(
                    'id' => $item['id']
                )
            );
            $count++;
        }
        echo $count . "puser edit,done\n";
    }
}

/* End of file fix_geo.php */
/* Location: ./application/controllers/fix_geo.php */
