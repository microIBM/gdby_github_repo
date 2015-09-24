<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wave extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(array(
            'MLocation',
            'msuborder',
        ));
        $this->load->library(array('order_split'));
    }

    public function pick_list_page_options() {
        $site_options = array(
            C('app_sites.chu'),
            C('app_sites.guo')
        );
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => C("status.common.success")
            )
        );
        $site = C('site.code');
        $options = array(
        //    'sites'        => $site_options,
            'cities'       => $cities,
            'sites'        => array_values($site),
        );
        //$order_type = array_values(C('order.order_type'));
        $order_type = $this->order_split->get_config();
        $options['order_type'] = $order_type;
        $this->_return_json(
            $options
        );
    }

    public function wave_list_page_options() {
        $site_options = array(
            C('app_sites.chu'),
            C('app_sites.guo')
        );
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => C("status.common.success")
            )
        );

        $request_time = $this->input->server('REQUEST_TIME');
        $today = strtotime(date('Y-m-d', $request_time));
        $tomorrow = $today + 86400;

        $deliver_date_options = array(
            array(
                'name' => date('Y-m-d', $today),
                'val'  => $today
            ),
            array(
                'name' => date('Y-m-d', $tomorrow),
                'val'  => $tomorrow
            )
        );

        $deliver_time_options = array(
            array(
                'name' => '全天',
                'val' => 0
            ),
            array(
                'name' => '上午',
                'val' => 1
            ),
            array(
                'name' => '下午',
                'val' => 2
            )
        );

        $wave_type = array(
            array(
                'name' => '自动',
                'val'  => C('wave.wave_type.auto.code')
            ),
            array(
                'name' => '手动',
                'val'  => C('wave.wave_type.manual.code')
            )
        );

        $options = array(
            'sites'        => $site_options,
            'deliver_date' => $deliver_date_options,
            'deliver_time' => $deliver_time_options,
            'wave_type'    => $wave_type,
            'cities'       => $cities,
        );
        
        //$order_type = array_values(C('order.order_type'));
        $order_type = $this->order_split->get_config();
        $options['order_type'] = $order_type;

        $this->_return_json(
            $options
        );
    }

    private function _user_info_with_ip() {
        $cur = $this->userauth->current(FALSE);
        $ip_addr = $this->input->ip_address();
        $cur['ip'] = $ip_addr;
        return $cur;
    }

    public function create_wave() {
        $user_info = $this->_user_info_with_ip();
        $_POST['cur'] = $user_info;
        $return = $this->format_query('/wave/create_wave', $_POST);
        $this->_return_json($return);
    }

    public function create_pick_task() {
        $user_info = $this->_user_info_with_ip();
        $_POST['cur'] = $user_info;
        $return = $this->format_query('/wave/create_pick_task', $_POST);
        $this->_return_json($return);
    }

    public function wave_list() {
        $split_config = $this->order_split->get_config();
        $order_type_dict = array();
        foreach($split_config as $item){
            $order_type_dict[$item['code']] = $item['msg'];
        }
        $user_info = $this->_user_info_with_ip();
        $_POST['cur'] = $user_info;
        $return = $this->format_query('/wave/wave_list', $_POST);
        $list = $return['list'];
        foreach($return['list'] as &$item){
            $wave_id = $item['id'];
            $ordertypes = $this->msuborder->get_one('order_type',array('wave_id' => $wave_id));
            $order_type = empty($ordertypes) ? 1 : $ordertypes['order_type'];
            $item['order_type_name'] = $order_type_dict[$order_type];

        }
        $this->_return_json($return);
    }

    public function wave_info() {
        $return = $this->format_query('/wave/wave_info', $_POST);
        $this->_return_json($return);
    }

    public function pick_task_list() {
        $split_config = $this->order_split->get_config();
        $order_type_dict = array();
        foreach($split_config as $item){
            $order_type_dict[$item['code']] = $item['msg'];
        }
        $return = $this->format_query('/wave/pick_task_list', $_POST);
        $pick_list = $return['list'];
        if(!empty($pick_list)) {
            $pick_ids = array_column($pick_list, 'id');
            // 查询配送单下的订单列表
            $return_order = $this->format_query('/suborder/lists', array('pick_ids' => $pick_ids, 'itemsPerPage' => 'all'));
            $order_list = $return_order['orderlist'];
            $order_map = array();
            foreach ($order_list as $order) {
                $order_map[$order['pick_task_id']][] = $order;
            }
            // 设置配送单的订单列表
            foreach ($pick_list as &$pick) {
                $pick['barcode'] = $this->config->item('api_url') . 'wave/barcode?thickness=70&scale=1&text=' . $pick['pick_number'];
                $pick['orders'] = isset($order_map[$pick['id']]) ? $order_map[$pick['id']] : array();
                $pick['order_type'] = empty($pick['orders']) ? 1 : $pick['orders'][0]['order_type'];
                $pick['order_type_name'] = $order_type_dict[$pick['order_type']];
                $order_detail_dict = array();
                $sku_total_count = 0;
                foreach ($pick['orders'] as $order) {
                    foreach ($order['detail'] as $detail) {
                        if(isset($order_detail_dict[$detail['sku_number']])) {
                            // 累加sku数量并重新设置
                            $od = $order_detail_dict[$detail['sku_number']];
                            $od['quantity'] += $detail['quantity'];
                            $order_detail_dict[$detail['sku_number']] = $od;
                        } else {
                            // 设置sku数量
                            $order_detail_dict[$detail['sku_number']] = $detail;
                        }
                        $sku_total_count += $detail['quantity'];
                    }
                }
                $pick['sku_list'] = array_values($order_detail_dict);
                $pick['sku_total_count'] = $sku_total_count;
                // 分拣单的配送日期设置为第一个订单的配送日期
                $pick['deliver_date'] = !empty($pick['orders']) ? $pick['orders'][0]['deliver_date'] : '';
            }
            unset($pick);
            $return['list'] = $pick_list;
        }

        $this->_return_json($return);
    }

    public function pick_task_info() {
        $return = $this->format_query('/wave/pick_task_info', $_POST);
        $this->_return_json($return);
    }

    public function finish_task() {
        $user_info = $this->_user_info_with_ip();
        $_POST['cur'] = $user_info;
        $return = $this->format_query('/wave/finish_task', $_POST);
        $this->_return_json($return);
    }

    public function delete_wave() {
        $user_info = $this->_user_info_with_ip();
        $_POST['cur'] = $user_info;
        $return = $this->format_query('/wave/delete_wave', $_POST);
        $this->_return_json($return);
    }

    /**
     * 生成条形码
     * @author yugang@dachuwang.com
     * @since 2015-04-20
     */
    public function barcode() {
    	$data['text'] = $this->input->get('text');
    	$data['thickness'] = $this->input->get('thickness');
    	$data['scale'] = $this->input->get('scale');
    	$query_string = http_build_query($data);
    	header('Content-Type: image/png');
    	echo file_get_contents($this->get_api_url($query_string));
    }

    private function get_api_url($query_string){
    	return sprintf('%s/barcode/get?%s',$this->_service_url,$query_string);
    }
}

/* End of file wave.php */
/* Location: ./application/controllers/wave.php */
