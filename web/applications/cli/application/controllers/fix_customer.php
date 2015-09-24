<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_customer extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCustomer',
                'MUser',
                'MPotential_customer',
                'MOrder',
                'MWorkflow_log'
            )
        );
    }

    /**
     * 将用户的geo信息分别存储
     * @author yugang@dachuwang.com
     * @since 2015-05-25
     */
    public function fix_customer_data() {
        // 将14天内未下单的北京非KA客户置为已删除
        $this->db->query("update t_customer set status = 0 where id not in (select distinct(user_id) from t_order where created_time > unix_timestamp('2015-06-17') and status != 0) and province_id = 804 and customer_type !=2");

        // 北京AM旗下所有客户还给注册BD，如果找不到这个BD的，移交给测试账号
        $list = $this->MCustomer->get_lists('*', ['status' => 12, 'province_id' => 804]);
        $count = 0;
        foreach ($list as $item) {
            $bd = $this->MUser->get_one('*', ['id' => $item['invite_bd'], 'status' => 1]);
            if (empty($bd)) {
                $this->MCustomer->update_info(['status' => 11, 'am_id' => 0, 'invite_id' => 340], ['id' => $item['id']]);
            } else {
                $this->MCustomer->update_info(['status' => 11, 'am_id' => 0, 'invite_id' => $item['invite_bd']], ['id' => $item['id']]);
            }
            $count++;
        }

        // 所有北京的AM变为BD
        $this->db->query("update t_user set role_id = 12 where role_id = 14 and province_id = 804");

        // 北京大果BD全部变成大厨BD
        $this->db->query("update t_user set site_id = 1 where site_id = 2 and role_id = 12 and province_id = 804");

        echo $count . "user edit,done\n";
    }

    /**
     * 阅兵期间因交通管制禁用非蒲黄榆线路、工体线路、知春路线路的普通客户
     * @author yugang@dachuwang.com
     * @since 2015-09-01
     */
    public function disable_customer() {
        // select * from t_customer where line_id not in (173, 138, 15) and status > 0 and is_active = 1 and customer_type = 1 and province_id = 804;
        $list = $this->MCustomer->get_lists(
            '*',
            [
                'status >'      => 0,
                'is_active'     => 1,
                'customer_type' => 1,
                'province_id'   => 804,
                'not_in'        => ['line_id' => [173, 138, 15]]
            ]
        );
        echo $this->db->last_query();

        $operator = ['name' => '系统'];
        foreach ($list as $customer) {
            // 禁用客户并记录日志
            $this->MCustomer->update_info(['status' => -1], ['id' => $customer['id']]);
            $this->MWorkflow_log->record_op_log($customer['id'], 2, $operator, '阅兵期间因交通管制禁用非蒲黄榆线路、工体线路、知春路线路的普通客户，阅兵结束后再重新启用', '禁用客户', 11);
        }
        echo '共禁用了' . count($list) . '个客户';
    }

    /**
     * 阅兵期间因交通管制禁用KA客户
     * @author yugang@dachuwang.com
     * @since 2015-09-02
     */
    public function disable_ka() {
        $list = $this->MCustomer->get_lists(
            '*',
            [
                'status >'      => 0,
                'is_active'     => 1,
                'customer_type' => 2,
                'province_id'   => 804,
                'not_in'        => ['id' => [15407, 15299, 15152, 14966]]
            ]
        );
        echo $this->db->last_query();

        $operator = ['name' => '系统'];
        foreach ($list as $customer) {
            // 禁用客户并记录日志
            $this->MCustomer->update_info(['status' => -1], ['id' => $customer['id']]);
            $this->MWorkflow_log->record_op_log($customer['id'], 2, $operator, '阅兵期间因交通管制禁用部分KA客户，阅兵结束后再重新启用', '禁用客户', 11);
        }
        echo '共禁用了' . count($list) . '个客户';
    }

    /**
     * 启用客户
     * @author yugang@dachuwang.com
     * @since 2015-09-02
     */
    public function enable_customer0902() {
        $list = $this->MCustomer->get_lists(
            '*',
            [
                'status'        => -1,
                'is_active'     => 1,
                'customer_type' => 1,
                'province_id'   => 804,
                'updated_time >=' => 1441119480,
                'in'        => ['line_id' => [317, 11]]
            ]
        );
        echo $this->db->last_query();

        $operator = ['name' => '系统'];
        foreach ($list as $customer) {
            // 禁用客户并记录日志
            $this->MCustomer->update_info(['status' => 1], ['id' => $customer['id']]);
            $this->MWorkflow_log->record_op_log($customer['id'], 1, $operator, '批量启用大望路线路、化工桥线路的普通客户', '启用客户', 11);
        }
        echo '共启用了' . count($list) . '个客户';
    }

    /**
     * 禁用天津和上海的客户
     * @author yugang@dachuwang.com
     * @since 2015-09-08
     */
    public function disable_0908() {
        $list = $this->MCustomer->get_lists(
            '*',
            [
                'status >'  => 0,
                'in'        => ['province_id' => [993, 1206]],
            ]
        );
        echo $this->db->last_query();

        $operator = ['name' => '系统'];
        foreach ($list as $customer) {
            // 禁用客户并记录日志
            $this->MCustomer->update_info(['status' => -1], ['id' => $customer['id']]);
            $this->MWorkflow_log->record_op_log($customer['id'], 2, $operator, '暂停天津和上海业务', '禁用客户', 11);
        }
        echo '共禁用了' . count($list) . '个客户';
    }
}

/* End of file fix_customer.php */
/* Location: ./application/controllers/fix_customer.php */
