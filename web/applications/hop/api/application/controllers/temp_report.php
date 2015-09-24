<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Temp_report extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MUser',
                'MCustomer',
                'MOrder',
            )
        );
    }

    public function buyonce() {
        $orders = $this->db->query("select created_time, user_id, count(1) cnt from t_order where status!=0 group by user_id having cnt=1")->result_array();
        $user_ids = array_column($orders, 'user_id');
        $users = $this->MCustomer->get_lists(
            '*',
            array(
                'in' => array(
                    'id' => $user_ids
                )
            )
        );
        $uid_order_map =array_combine(
            array_column($orders, 'user_id') , array_column($orders, 'created_time')
        );
        foreach($users as &$item) {
            $user_id = $item['id'];
            $item['order_time'] = $uid_order_map[$user_id];
        }
        unset($item);
        $users = $this->_format_user($users);
        $csv_data = [];
        foreach($users as $item) {
            $csv_data[] = array(
                'name'            => $item['name'],
                'mobile'          => $item['mobile'],
                'reg_time'        => date('Y-m-d H:i', $item['created_time']),
                'first_deal_time' => date('Y-m-d H:i', $item['order_time']),
                'shop_name'       => $item['shop_name'],
                'address'         => $item['address'],
                'bd_name'         => $item['bd_name'],
                'bd_mobile'       => $item['bd_mobile']

            );
        }
        $title_arr = array(
            'name'            => '用户姓名',
            'mobile'          => '用户手机',
            'reg_time'        => '注册时间',
            'first_deal_time' => '首次下单时间',
            'shop_name'       => '店名',
            'address'         => '地址',
            'bd_name'         => 'bd姓名',
            'bd_mobile'       => 'bd手机号'
        );
        $this->export_csv($title_arr, $csv_data);

    }

    private function _format_user($users = array()) {
        if(empty($users)) {
            return $users;
        }
        $invite_ids = array_column($users, 'invite_id');
        $bds = $this->MUser->get_lists(
            '*',
            array(
                'in' => array(
                    'id' => $invite_ids
                )
            )
        );
        $invite_ids = array_column($bds, 'id');
        $bd_map = array_combine($invite_ids, $bds);
        foreach($users as &$item) {
            $bd_id = $item['invite_id'];
            $item['bd_name'] = isset($bd_map[$bd_id]) ? $bd_map[$bd_id]['name'] : '';
            $item['bd_mobile'] = isset($bd_map[$bd_id]) ? $bd_map[$bd_id]['mobile'] : '';
        }
        return $users;
    }
}

/* End of file temp_report.php */
/* Location: ./application/controllers/temp_report.php */
