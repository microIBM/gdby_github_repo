<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dirtyrun extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
                'MProduct',
                'MOrder_detail',
                'MOrder',
                'MSuborder',
                'MLine',
                'MCustomer',
                'MPotential_customer',
                'MCustomer_transfer_log',
                'MAbnormal_order',
                'MAbnormal_content',
                'MComplaint',
                'MComplaint_content',
                'MUser',
                'MProduct_price'
            )
        );
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 修复t_order_detail表中未写入的product_id
     */
    public function fix_order_detail() {
        $where = ['product_id' => 0];
        $details = $this->MOrder_detail->get_lists(
            'id, name, spec',
            $where
        );
        foreach($details as $item) {
            $product = $this->MProduct->get_one(
                'id',
                array(
                    'title' => $item['name'],
                    'spec'  => $item['spec']
                )
            );
            if($product) {
                $res = $this->MOrder_detail->update_info(
                    array(
                        "product_id" => $product['id']
                    ),
                    array(
                        "id" => $item["id"]
                    )
                );
                if(!$res) {
                    echo "update detail " . $item['id'] . " failed\n";
                }
            } else {
                echo "detail " . $item['id'] . " has no product record in product table \n";
            }
        }
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 修复现在的t_order表里的site_src字段
     */
    public function fix_order_siteid() {
        //获取全部order
        $orders = $this->MOrder->get_lists(
            'id, user_id',
            array(
                'site_src' => 0
            )
        );
        if(empty($orders)) {
            echo "orders all have site_src, no need to fix";
            return;
        }

        //下单用户批量查询
        $user_ids = array_column($orders, 'user_id');
        $users = $this->MCustomer->get_lists(
            'id, site_id',
            array(
                'in' => array(
                    'id' => $user_ids
                )
            )
        );
        $user_ids = array_column($users, 'id');
        $user_map = array_combine($user_ids, $users);

        //update site_src in t_order
        foreach($orders as $order_item) {
            $user_id = $order_item['user_id'];
            $order_user = isset($user_map[$user_id]) ? $user_map[$user_id] : [];
            if($user_id == 0 || empty($order_user)) {
                echo "order {$order_item['id']} has no user data\n";
                continue;
            }
            $site_id = $order_user['site_id'];
            if($site_id == 0) {
                echo "the site_id of user {$order_user['id']} in order {$order_item['id']} \n";
                continue;
            }
            $order_id = $order_item['id'];
            $this->MOrder->update_info(
                array(//data
                    'site_src' => $site_id
                ),
                array(//where
                    'id' => $order_id
                )
            );
        }
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 修复因为t_order_detail表中的spec字段长度不够时
     * 导致从product表拷spec截断的问题
     */
    public function fix_order_detail_spec() {
        $order_details = $this->MOrder_detail->get_lists(
            'id, product_id',
            []
        );
        $product_ids = array_column($order_details, 'product_id');
        $products = $this->MProduct->get_lists(
            'id, spec',
            array(
                'in' => array(
                    'id' => $product_ids
                )
            )
        );
        $product_ids = array_column($products, 'id');
        $product_map = array_combine($product_ids, $products);

        foreach($order_details as $detail_item) {
            $product_id = $detail_item['product_id'];
            $detail_id = $detail_item['id'];
            if($product_id == 0) {
                echo "the product_id of detail $detail_id is 0\n";
                continue;
            }
            if(empty($product_map[$product_id])) {
                echo "detail {$detail_id} has no product info\n";
                continue;
            }
            $product = $product_map[$product_id];
            $spec = $product['spec'];
            $this->MOrder_detail->update_info(
                //data, where
                array(
                    'spec' => $spec
                ),
                array(
                    'id' => $detail_id
                )
            );
        }
    }

    /**
     * 处理没有商圈的数据
     * id从372至499
     * @author yugang@dachuwang.com
     * @since 2015-03-18
     */
    public function deal_data() {
        $file = fopen('/tmp/customer.csv', 'r');
        $count = 1;
        while(!feof($file)){
            $row = fgetcsv($file);
            if($row['1'] >= 372 && $row['1'] <=499){
                $count++;
                $data['county_id'] = $row['6'];
                $this->MCustomer->update_info($data, array('id' => $row['1']));
                echo '<br/>' . $count . ' : ' . $this->db->last_query(). '<br/>';
            }
        }
        fclose($file);
    }

    /**
     * 修复商圈线路数据
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-03-19
     */
    public function deal_line_data() {
        $file = fopen('/tmp/customer_lines_utf8.csv', 'r');
        $lines = $this->MLine->get_lists(
            array('id', 'name'),
            array('status' => 1)
        );
        $line_map = array_column($lines, 'id', 'name');
        while(!feof($file)) {
            $row = fgetcsv($file);
            $id = $row[0];
            $name = $row[1];
            if($id > 0) {
                $data = array(
                    'line_id' => !empty($line_map[$name]) ? $line_map[$name] : 0
                );
                echo "{$id}\t{$line_map[$name]}\t{$name}\r\n";
                //$this->MCustomer->update_info($data, array('id' => $id));
            }
        }
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 修复t_order表里的line_id
     */
    public function fix_order_line_id() {
        $deliver_date = strtotime(date('Y-m-d')) + 86400;
        $orders = $this->MOrder->get_lists(
            'id, user_id',
            array(
                'deliver_date >=' => $deliver_date,
                'status !=' => 0
            )
        );
        $user_ids  = array_column($orders, 'user_id');
        $users = $this->MCustomer->get_lists(
            'id, line_id',
            array(
                'in' => array(
                    'id' => $user_ids
                )
            )
        );
        $user_ids = array_column($users, 'id');
        $user_map = array_combine($user_ids, $users);
        foreach($orders as $item) {
            $user_id = $item['user_id'];
            $order_id = $item['id'];
            $line_id = isset($user_map[$user_id]) ? $user_map[$user_id]['line_id'] : 0;
            if(!$line_id) {
                echo "the line_id of order {$item['id']} is 0\n";
                continue;
            }
            $this->MOrder->update_info(
                array(
                    'line_id' => $line_id
                ),
                array(
                    'id' => $order_id
                )
            );
        }
        echo "done\n";
    }

    /**
     * 修复配送日期包含时分秒的订单数据
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function deal_deliver_date() {
        $order_list = $this->MOrder->get_lists('*');
        echo '修复订单异常配送日期sql语句列表：<br>';
        $count = 0;
        foreach ($order_list as $order) {
            $deliver_date = $order['deliver_date'];
            $right_deliver_date = strtotime(date('Y-m-d', $deliver_date));
            if($deliver_date != $right_deliver_date) {
                $data = array();
                $data['deliver_date'] = $right_deliver_date;
                $this->MOrder->update_info($data, array('id' => $order['id']));
                echo $this->db->last_query() . ' : 错误送货日期为'. date('Y-m-d H:i:s', $deliver_date) . ' ' . $deliver_date . '<br/>';
                $count++;
            }
        }
        echo '共修复订单配送日期异常数据' . $count . '条<br>';
    }

    //修正t_order里的status为0的order对应的t_order_detail里的status
    public function fix_order_detail_status() {
        $orders = $this->MOrder->get_lists(
            'id, status',
            array(
            )
        );
        $order_ids = array_column($orders, 'id');
        $order_status = array_column($orders, 'status');
        $status_map = array_combine($order_ids, $order_status);
        foreach($order_ids as $order_id) {
            $status = $status_map[$order_id];
            $this->MOrder_detail->update_info(
                array(
                    'status'   => $status
                ),
                array(
                    'order_id' => $order_id
                )
            );
        }
    }


    //修复大厨的商品
    public function fix_dachu_product_close_unit_and_single_price() {
        $dachu_category_sql = "select id from t_category where path like '.1.%' or path like '.6.%'";
        $category_ids = $this->db->query($dachu_category_sql)->result_array();
        $category_ids = array_column($category_ids, 'id');
        //var_dump($category_ids);
        $products = $this->MProduct->get_lists(
            'id, price, unit_id',
            array(
                'single_price' => 0,
                'in' => array(
                    'category_id' => $category_ids
                )
            )
        );
        //var_dump($products);
        //print_r($this->db->last_query());
        foreach($products as $item) {
            $this->MProduct->update_info(
                array(
                    'close_unit'   => $item['unit_id'],
                    'single_price' => $item['price']
                ),
                array(
                    'id' => $item['id']
                )
            );
            echo $this->db->last_query();
            echo "\n";
        }
    }

    //根据product表修复t_order_detail表中的category_id
    public function fix_category_id_for_order_detail() {
        $products = $this->MProduct->get_lists(
            'id, category_id',
            array()
        );

        $product_ids = array_column($products, 'id');
        $product_map = array_combine($product_ids, $products);

        foreach($product_map as $product_id => $product) {
            $category_id = $product['category_id'];
            $update_res = $this->MOrder_detail->update_info(
                array(
                    'category_id' => $category_id
                ),
                array(
                    'product_id' => $product_id
                )
            );
            echo $this->db->last_query();
            echo "\n";
            if(!$update_res) {
                echo "update product {$product_id} failed\n";
            }
        }
    }

    /**
     * 处理历史订单对应销售信息
     * @author yugang@dachuwang.com
     * @since 2015-05-05
     */
    public function fix_order_sale_data() {
        ini_set("memory_limit", "1024M");
        set_time_limit(0);

        // 获取所有的销售列表
        $sale_role = array('12', '13', '14', '15', '16');
        $sale_list = $this->MUser->get_lists('*', array('in' => array('role_id' => $sale_role)));
        $sale_ids = array_column($sale_list, 'id');
        $sale_dict = array_combine($sale_ids, $sale_list);

        $order_list = $this->MOrder->get_lists('id, user_id');
        $update_list = array();
        $count = 1;

        foreach ($order_list as $order) {
            $customer = $this->MCustomer->get_one('*', array('id' => $order['user_id']));
            $sale_role = isset($sale_dict[$customer['invite_id']]) ? $sale_dict[$customer['invite_id']]['role_id'] : 0;
            if($sale_role != 12 && $sale_role != 13){
                $sale_role = 12;
            }
            $data = array(
                'sale_id'   => isset($sale_dict[$customer['invite_id']]) ? $sale_dict[$customer['invite_id']]['id'] : 0,
                'sale_role' => $sale_role,
            );

            // 更新订单的所属销售
            $this->MOrder->update_info($data, array('id' => $order['id']));
            echo '<br/>' . $count++ . ' : ' . $this->db->last_query(). '<br/>\r\n';
        }
    }

    /**
     * 批量处理客户移交
     * @author yugang@dachuwang.com
     * @since 2015-05-05
     */
    public function fix_customer_transfer_data() {
        ini_set("memory_limit", "1024M");
        set_time_limit(0);

        $file = fopen('/tmp/t_customer_transfer.csv', 'r');
        $count = 1;
        $am_arr = array(14, 15, 16);
        $bd_arr = array(12, 13);
        $operator = $this->MUser->get_one('*', array('id' => 1));
        $operator['ip'] = '0.0.0.0';

        while(!feof($file)){
            $row = fgetcsv($file);
            $sale_mobile = $row['11'];
            $sale_name = $row['10'];
            $cid = $row['0'];
            if(empty($cid) || empty($sale_mobile)){
                continue;
            }
            // echo $count++ . ' -:' . $cid . ':' . $sale_mobile . ':<br>';
            // continue;

            $sale = $this->MUser->get_one('*', array('mobile' => $sale_mobile));
            // 如果根据手机号查询不到销售，则根据姓名查询
            if(empty($sale)){
                $sale = $this->MUser->get_one('*', array('name' => $sale_name));
            }
            // 查询不到销售，则不更新该客户
            if(empty($sale)){
                echo '##' . $cid . '##';
                continue;
            }

            if(in_array($sale['role_id'], $am_arr)){
                $data = array(
                    'am_id' => $sale['id'],
                    'status' => C('customer.status.allocated.code'),
                );
            } else {
                $data = array(
                    'invite_id' => $sale['id'],
                    'status' => C('customer.status.new.code'),
                );
            }
            $this->MCustomer->update_info($data, array('id' => $cid));
            echo '<br/>' . $count++ . ' : ' . $this->db->last_query(). '<br/>\r\n';
            // 记录移交日志
            $this->MCustomer_transfer_log->record($sale['id'], $cid, $operator);
        }

        fclose($file);
    }

    /**
     * 批量处理潜在客户移交
     * @author yugang@dachuwang.com
     * @since 2015-05-05
     */
    public function fix_potential_customer_transfer_data() {
        ini_set("memory_limit", "1024M");
        set_time_limit(0);

        $file = fopen('/tmp/t_potential_customer_transfer.csv', 'r');
        $count = 1;
        $am_arr = array(14, 15, 16);
        $bd_arr = array(12, 13);
        $operator = $this->MUser->get_one('*', array('id' => 1));
        $operator['ip'] = '0.0.0.0';

        while(!feof($file)){
            $row = fgetcsv($file);
            $sale_mobile = $row['11'];
            $sale_name = $row['10'];
            $cid = $row['0'];
            if(empty($cid) || empty($sale_mobile)){
                continue;
            }

            $sale = $this->MUser->get_one('*', array('mobile' => $sale_mobile));
            // 如果根据手机号查询不到销售，则根据姓名查询
            if(empty($sale)){
                $sale = $this->MUser->get_one('*', array('name' => $sale_name));
            }
            // 查询不到销售，则不更新该客户
            if(empty($sale)){
                echo '##' . $cid . '##';
                continue;
            }
            $data = array(
                'invite_id' => $sale['id'],
            );
            $this->MPotential_customer->update_info($data, array('id' => $cid));
            echo '<br/>' . $count++ . ' : ' . $this->db->last_query(). '<br/>\r\n';
            // 记录移交日志
            // $this->MCustomer_transfer_log->record($sale['id'], $cid, $operator);
        }

        fclose($file);
    }

    /**
     * 批量处理天津和上海最近5天内客户，所有所有天津的5天内下单客户分配给董常青，上海的5天内下单客户分配给李亮
     * @author yugang@dachuwang.com
     * @since 2015-05-05
     */
    public function fix_customer_cm_data() {
        ini_set("memory_limit", "1024M");
        set_time_limit(0);

        $order_list = $this->MOrder->get_lists('id, user_id', array('location_id' => 993, 'created_time >=' => 1430409600));
        $count = 1;

        // 上海的5天内下单客户分配给李亮-35
        foreach ($order_list as $order) {
            $customer = $this->MCustomer->get_one('*', array('id' => $order['user_id']));
            $data = array(
                'am_id' => 35,
                'status' => C('customer.status.allocated.code'),
            );

            // 更新订单的所属销售
            $this->MCustomer->update_info($data, array('id' => $customer['id']));
            echo '<br/>' . $count++ . ' : ' . $this->db->last_query(). '<br/>\r\n';
        }

        // 所有所有天津的5天内下单客户分配给董常青-18
        $order_list = $this->MOrder->get_lists('id, user_id', array('location_id' => 1206, 'created_time >=' => 1430409600));

        foreach ($order_list as $order) {
            $customer = $this->MCustomer->get_one('*', array('id' => $order['user_id']));
            $data = array(
                'am_id' => 18,
                'status' => C('customer.status.allocated.code'),
            );

            // 更新订单的所属销售
            $this->MCustomer->update_info($data, array('id' => $customer['id']));
            echo '<br/>' . $count++ . ' : ' . $this->db->last_query(). '<br/>\r\n';
        }
    }

    /**
     * 批量处理异常单数据
     * @author yugang@dachuwang.com
     * @since 2015-05-21
     */
    public function fix_abnormal_data() {
        $ao_list = $this->MAbnormal_order->get_lists('*', ['status' => 1]);
        foreach ($ao_list as $ao) {
            $data = [];
            $data['aid'] = $ao['id'];
            $data['order_id'] = $ao['order_id'];
            $data['product_id'] = $ao['product_id'];
            $data['name'] = $ao['product_name'];
            $data['created_time'] = $this->input->server("REQUEST_TIME");
            $data['updated_time'] = $this->input->server("REQUEST_TIME");
            $data['status'] = 1;
            $this->MAbnormal_content->create($data);
        }
    }

    /**
     * 批量处理投诉单客户id
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function fix_complaint_data() {
        $list = $this->MComplaint->get_lists('*');
        $count = 0;
        foreach ($list as $item) {
            $order = $this->MOrder->get_one('*', ['id' => $item['order_id']]);
            if(empty($order)){
                continue;
            }
            $data = [];
            $data['user_id'] = $order['user_id'];
            $this->MComplaint->update_info($data, ['id' => $item['id']]);
            echo $this->db->last_query() . '\r\n <br>';
            $count++;
        }
        echo $count . ' complaint changes done.';
    }

    /**
     * 批量处理CRM客户将所有潜在客户与注册客户放到公海
     * @author yugang@dachuwang.com
     * @since 2015-05-27
     */
    public function fix_customer_public_sea() {
        $count = 0;
        $cur = ['id' => '-1', 'name' => '系统', 'ip' => '127.0.0.1'];
        $where = ['status' => 1, 'province_id' => 804, 'invite_id !=' => 111];
        // 获取北京的已注册未下单客户
        $customer_list = $this->MCustomer->get_lists('*', $where);
        echo $this->db->last_query();
        foreach ($customer_list as $item) {
            // 记录日志
            $this->MCustomer_transfer_log->record(C('customer.public_sea_code'), $item['id'], $cur);
            // 将客户踢到公海
            $this->MCustomer->update_info(['invite_id' => -1], ['id' => $item['id']]);
            $count++;
        }

        echo ' \n ' . $count . ' customer changes done.';
        $count = 0;
        // 获取北京的潜在客户
        $potential_customer_list = $this->MPotential_customer->get_lists('*', $where);
        echo $this->db->last_query();
        foreach ($potential_customer_list as $item) {
            // 记录日志
            $src_user = $this->MUser->get_one('*', ['id' => $item['invite_id']]);
            $dest_user = ['id' => C('customer.public_sea_code'), 'role_id' => 0];
            $this->MCustomer_transfer_log->record_potential($src_user, $dest_user, $item['id'], $cur);
            // 将潜在客户踢到公海
            $this->MPotential_customer->update_info(['invite_id' => -1], ['id' => $item['id']]);
            $count++;
        }

        echo ' \n ' . $count . ' potential customer changes done.';
    }

    /**
     * 修复部分天津订单挂在北京的am上的异常数据
     * @author yugang@dachuwang.com
     * @since 2015-06-26
     */
    public function fix_wrong_order() {
        // 1.更新订单
        $orders = $this->MOrder->get_lists('*', ['sale_id' => 9, 'city_id' => 1206]);
        echo $this->db->last_query() . '<br>\r\n';
        echo count($orders);
        $count = 1;
        foreach ($orders as $order) {
            $customer = $this->MCustomer->get_one('*', ['id' => $order['user_id']]);
            $this->MOrder->update_info(['sale_id' => $customer['invite_id'], 'sale_role' => 12], ['id' => $order['id']]);
            echo $this->db->last_query() . '<br>\r\n';
            $this->MSuborder->update_info(['sale_id' => $customer['invite_id'], 'sale_role' => 12], ['order_id' => $order['id']]);
            echo $this->db->last_query() . '<br>\r\n';
            $count++;
        }

        echo '共修复了' . $count . '条数据';

        // 2.更新客户
        $this->MCustomer->update_info(['status' => 11, 'am_id' => 0], ['am_id' => 9, 'status' => 12, 'province_id' => 1206]);
        echo $this->db->last_query() . '<br>\r\n';
    }

    /**
     * 根据suborder修复order表的sale_id 和 sale_role
     * @author yugang@dachuwang.com
     * @since 2015-07-01
     */
    public function fix_order_sale() {
        $list = $this->MSuborder->get_lists('*', ['id <' => 60205]);
        echo count($list);
        echo '------';
        $count = 0;
        foreach ($list as $item) {
            $this->MOrder->update_by('id', $item['order_id'], ['sale_id' => $item['sale_id'], 'sale_role' => $item['sale_role']]);
            $count++;
        }

        echo '共更新了' . $count . '条数据';
    }

    /**
     * 根据备份数据修复order表和suborder表的sale_id 和 sale_role
     * @author yugang@dachuwang.com
     * @since 2015-07-06
     */
    public function fix_history_order_sale() {
        // 根据备份恢复20150704日之前的订单
        $list = $this->db->select('id, sale_id, sale_role')->from('t_order_bak')->get()->result_array();
        echo count($list);
        echo '------';
        $count = 0;
        foreach ($list as $item) {
            $this->MOrder->update_by('id', $item['id'], ['sale_id' => $item['sale_id'], 'sale_role' => $item['sale_role']]);
            $count++;
        }
        unset($item);
        echo '共更新了' . $count . '条数据\r\n';

        // 根据订单表的user_id恢复订单的order_id
        $list = $this->MOrder->get_lists('id, user_id', ['id >' => 61769]);
        echo count($list);
        echo '------';
        $count = 0;
        foreach ($list as $item) {
            $customer = $this->MCustomer->get_one('id,invite_id', ['id' => $item['user_id']]);
            if ($customer['invite_id'] > 0) {
                $user = $this->MUser->get_one('id,role_id', ['id' => $customer['invite_id']]);
                if (empty($user)) {
                    $user = ['id' => 0, 'role_id' => 0];
                }
                $this->MOrder->update_by('id', $item['id'], ['sale_id' => $user['id'], 'sale_role' => $user['role_id']]);
            }
            $count++;
        }
        unset($item);
        echo '共更新了' . $count . '条数据\r\n';

        // 根据t_order表恢复t_suborder表的sale_id和sale_role
        $list = $this->MOrder->get_lists('id, sale_id, sale_role');
        echo count($list);
        echo '------';
        $count = 0;
        foreach ($list as $item) {
            $this->MSuborder->update_by('order_id', $item['id'], ['sale_id' => $item['sale_id'], 'sale_role' => $item['sale_role']]);
            $count++;
        }

        echo '共更新了' . $count . '条数据';
    }

    /**
     * dm系统生成用户标签关联SQL语句
     * @author yugang@dachuwang.com
     * @since 2015-07-22
     */
    public function insert_user_tags() {
        $sql = "insert into dm_user_tags values ";
        $tag_id = 33795;
        $ids = "341,797,1178,1361,1683,1804,2025,2100,2357,2810,3457,3587,3768,3817,3901,4107,4444,4455,4797,4892,4983,5410,5436,5443,5592,5689,5697,5957,6207,6497,6735,6918,6929,6956,7050,7140,7323,7362,7428,7467,7497,7503,7538,7588,7617,7641,7643,7680,7701,7742,7793,7832,7875,8042,8224,8344,8409,8422,8480,8507,8537,8577,8736,8784,8882,8910,9122,9126,9192,9352,9423,9469,9535,9597,9605,9660,9754,9755,9758,9835,9851,9893,9921,9924,10032,10038,10170,10258,10303,10373,10406,10436,10462,10484,10546,10552,10585,10629,10704,10710,10718,10851,10905,11058,11065,11176,11235,11333,11362,11375,11419,11451,11464,11468,11499,11536,11598,11626,11698,11873,11946,12003,12007,12145,12215,12364,12378,12393,12425,12616,12618,12729,12779,12810,12926,13102,13264,13379,13420,13448,13547,13599,13711,13726,13794,13867,13991,3202,5798,7192,7526,10391,10454,10627,10657,10812,10867,11367,12811,12931,13005,13181,13214,13230,13260,13265,13286,13328,13367,13389,13401,13416,13418,13424,13426,13427,13463,13525,13541,13612,13646,13754,3550,3561,3985,4087,4189,4259,4402,4657,4790,5567,5868,6075,6278,6712,6733,8049,8575,8875,9521,9663,9765,10213,10268,10346,10421,10583,10935,10963,10975,10988,11001,11078,11175,11256,11309,11350,11388,11431,11520,11807,11835,11869,11940,12035,12193,12254,12282,12295,12428,12486,12671,12751,12753,12782,12797,13089";
        $id_list = explode(",", $ids);
        foreach ($id_list as $uid) {
            $sql .= " (NULL, {$uid}, {$tag_id}, 1, 1437545217, 1437545217), ";
        }

        echo $sql;
    }

    /*
     * 修复异常单和投诉单详情的product_id字段
     * @author yugang@dachuwang.com
     */
    public function fix_complaint_product_id() {
        $complaint_list = $this->MComplaint_content->get_lists('*');
        $complaint_count = 0;
        foreach ($complaint_list as $complaint) {
            $detail = $this->MOrder_detail->get_one('*', ['id' => $complaint['product_id']]);
            $this->MComplaint_content->update_info(['product_id' => $detail['product_id']], ['id' => $complaint['id']]);
            $complaint_count++;
        }
        echo "共修改了{$complaint_count}条记录。";

        $abnormal_count = 0;
        $abnormal_list = $this->MAbnormal_content->get_lists('*');
        foreach ($abnormal_list as $abnormal) {
            $detail = $this->MOrder_detail->get_one('*', ['id' => $abnormal['product_id']]);
            $this->MAbnormal_content->update_info(['product_id' => $detail['product_id']], ['id' => $abnormal['id']]);
            $abnormal_count++;
        }
        echo "共修改了{$abnormal_count}条记录。";
    }

    /**
     * 客户所属BD统一处理
     * @author yugang@dachuwang.com
     * @since 2015-08-08
     * @description
     * 把所属城市串了的客户移动进公海（如上海客户在北京BD这里）。
     * 检查一下禁用后的BD有没有持有客户，如果有的话释放到对应城市公海里。
     * 检查一下非BD角色有没有持有客户，如果有的话释放到对应城市公海里。
     * 把禁用和修改BD角色，需要判断BD是否仍然持有客户的限制加上去。
     */
    public function fix_customer_invite_id() {
        $all_bd_list = $this->MUser->get_lists('id', ['role_id' => 12]);
        $all_bd_list = array_column($all_bd_list, 'id');
        $disabled_bd_list = $this->MUser->get_lists('id', ['role_id' => 12, 'status <=' => 0]);
        $disabled_bd_list = array_column($disabled_bd_list, 'id');

        // 检查一下禁用后的BD有没有持有客户，如果有的话释放到对应城市公海里。
        $disabled_list = $this->MCustomer->get_lists('*', ['in' => ['invite_id' => $disabled_bd_list]]);
        $disabled_list = array_column($disabled_list, 'id');
        echo $this->db->last_query() . '\r\n';
        echo count($disabled_list) . '\r\n';
        $this->MCustomer->update_info(['invite_id' => -1], ['in' => ['id' => $disabled_list]]);

        // 检查一下非BD角色有没有持有客户，如果有的话释放到对应城市公海里。
        $not_bd_list = $this->MCustomer->get_lists('*', ['not_in' => ['invite_id' => $all_bd_list], 'invite_id !=' => -1]);
        $not_bd_list = array_column($not_bd_list, 'id');
        echo $this->db->last_query() . '\r\n';
        echo count($not_bd_list) . '\r\n';
        $this->MCustomer->update_info(['invite_id' => -1], ['in' => ['id' => $not_bd_list]]);

    }

    /**
     * 将上海的长时间未下单的客户踢到公海里
     * @author yugang@dachuwang.com
     * @since 2015-08-13
     */
    public function fix_customer_public_sea_0813() {
        $count = 0;
        $cur = ['id' => '-1', 'name' => '系统', 'ip' => '127.0.0.1'];
        // 上海未下单客户列表，来源于邮件
        $customer_ids = [1647,1648,1649,1650,1651,1731,1732,2098,2152,2153,2278,2291,2293,2300,2304,2325,2327,2354,2362,2393,2482,2484,2494,2504,2511,2519,2524,2618,2622,2631,2681,2684,2690,2712,2763,2853,2855,2866,2868,2872,2874,2878,2883,2909,2940,2945,2950,2987,3139,3192,3242,3246,3255,3302,3341,3373,3408,3409,3414,3417,3422,3427,3495,3498,3512,3630,3657,3665,3745,3765,3793,3895,3950,3958,4089,4151,4155,4284,4318,4396,4423,4456,4682,4718,4728,4743,4767,4780,4814,4846,4850,4901,4930,4980,5017,5070,5162,5170,5232,5246,5256,5285,5298,5373,5391,5423,5430,5498,5503,5518,5549,5551,5554,5591,5605,5623,5648,5673,5699,5741,5747,5748,5781,5791,5854,5859,5898,5901,5904,5921,5946,5970,5989,5992,5997,6002,6004,6011,6139,6143,6145,6148,6181,6184,6224,6226,6296,6369,6384,6395,6410,6422,6435,6444,6462,6463,6470,6490,6508,6521,6575,6612,6757,6771,6782,6786,6789,6809,6853,6941,6998,7027,7084,7103,7151,7201,7213,7330,7358,7387,7397,7463,7480,7481,7662,7696,7765,7812,7829,7838,7849,7862,7867,7872,7873,7925,7938,7942,7949,7961,7979,7986,7996,8026,8027,8034,8036,8052,8054,8058,8073,8080,8214,8238,8261,8286,8289,8322,8348,8359,8369,8373,8388,8396,8428,8433,8438,8462,8468,8523,8731,8739,8743,8757,8783,8789,8831,8849,8853,8855,8858,8870,8897,8914,8931,8943,8972,9088,9139,9146,9166,9187,9193,9202,9214,9230,9252,9270,9274,9298,9342,9345,9348,9385,9387,9401,9482,9483,9485,9498,9542,9562,9768,9785,9789,9802,9807,9818,9833,9839,9849,9865,9866,9876,9904,9906,10004,10006,10019,10021,10022,10048,10049,10051,10063,10075,10084,10085,10098,10099,10100,10109,10112,10125,10130,10137,10148,10183,10185,10205,10208,10212,10218,10224,10225,10226,10233,10234,10240,10262,10287,10289,10302,10306,10308,10313,10316,10321,10323,10332,10369,10399,10403,10409,10410,10412,10414,10423,10444,10463,10490,10491,10513,10525,10531,10539,10549,10574,10579,10625,10647,10656,10666,10669,10674,10681,10738,10754,10755,10784,10785,10806,10809,10813,10821,10825,10829,10865,10871,10872,10875,10894,10896,10933,10942,10955,10966,11002,11037,11043,11047,11053,11072,11077,11082,11084,11086,11088,11095,11101,11105,11108,11118,11120,11121,11128,11142,11145,11168,11178,11182,11188,11195,11225,11259,11266,11274,11281,11284,11286,11287,11288,11307,11321,11329,11340,11387,11395,11399,11402,11437,11462,11473,11478,11497,11505,11508,11512,11530,11563,11569,11579,11584,11587,11591,11593,11657,11662,11663,11666,11667,11670,11675,11680,11686,11688,11694,11710,11711,11727,11738,11741,11746,11747,11755,11758,11766,11773,11793,11797,11804,11806,11814,11825,11830,11846,11870,11876,11884,11894,11900,11906,11907,11913,11915,11942,11944,11949,11951,11960,11964,11966,11971,11972,11976,11983,11988,12002,12008,12027,12031,12040,12052,12055,12068,12070,12084,12092,12101,12112,12115,12124,12136,12188,12190,12235,12237,12285,12316,12352,12358,12373,12376,12379,12385,12392,12394,12400,12404,12422,12449,12476,12478,12496,12502,12506,12507,12512,12515,12520,12538,12550,12559,12560,12583,12592,12593,12600,12606,12619,12622,12628,12649,12652,12659,12661,12664,12679,12701,12704,12706,12709,12714,12721,12730,12741,12770,12780,12795,12799,12805,12809,12817,12826,12857,12859,12874,12879,12881,12882,12890,12892,12895,12906,12913,12915,12918,12924,12927,12929,12930,12939,12942,12944,12946,12952,12953,12958,12962,12963,12966,12968,12972,12974,12975,12977,12979,12981,12989,12996,13018,13025,13028,13029,13031,13032,13033,13037,13038,13040,13042,13046,13048,13049,13051,13053,13057,13058,13059,13064,13067,13072,13073,13079,13081,13084,13090,13101,13109,13115,13119,13122,13124,13134,13137,13154,13169,13174,13176,13177,13179,13182,13183,13185,13192,13215,13223,13225,13227,13228,13229,13231,13233,13237,13256,13258,13269,13275,13277,13287,13289,13292,13296,13310,13311,13314,13315,13320,13321,13322,13323,13336,13339,13347,13363,13371,13372,13374,13377,13382,13385,13387,13393,13394,13399,13407,13409,13417,13419,13423,13425,13428,13454,13456,13470,13471,13472,13475,13478,13479,13481,13484,13490,13492,13499,13501,13510,13522,13535,13556,13563,13566,13567,13570,13579,13581,13586,13594,13600,13601,13602,13603,13617,13618,13620,13625,13630,13632,13633,13636,13637,13640,13645,13654,13673,13675,13683,13684,13702,13714,13721,13758,13770,13775,13847,13864,13885,13903,13908,13916,13917,13935,13940,13946,13955,13958,13969,13981,13995,14013,14020,14021,14023,14029,14044,14051,14064,14072,14076,14078,14079,14081,14088,14091,14092,14095,14096,14103,14119,14125,14156,14180,14190,14204,14207,14211,14221,14225,14227,14232,14237,14330,14332,14333,14334,14336,14337,14338,14340,14342,14350,14351,14354,14355,14357,14361,14366,14367,14369,14372,14373,14383,14389,14392,14393,14394,14396,14397,14399,14423,14426,14428,14429,14430,14434,14435,14440,14444,14447,14458,14462,14465,14466,14473,14475,14476,14479,14480,14483,14489,14490,14491,14492,14493,14495,14499,14500,14503,14504,14506,14510,14513,14514,14515,14517,14520,14521,14522,14523,14524,14549,14556,14564,14567,14568,14571,14572,14576,14581,14582,14586,14588,14591,14592,14601,14605,14618,14619,14620,14621,14624,14634,14639,14642,14648,14651,14652,14654,14656,14661,14665,14666,14668,14670,14674,14676,14679,14684,14685,14689,14690,14692,14693,14697,14698,14727,14739,14740,14745,14746,14749,14754,14755,14756,14770,14775,14778,14779,14780,14782,14787,14789,14793,14797,14798,14800,14802,14811,14813,14820,14822,14829,14832,14836,14837,14839,14841,14842,14845,14846,14847,14848,14853,14854,14868,14873,14877,14878,14881,14894,14895,14896,14899,14905,14907,14909,14914,14917,14918,14919,14928,14929,14933,14935,14942,14943,14951,14953,14954,14957,14958,14959,14960,14968,14983,14985,14991,14993,14994,14995,14997,14998,15000,15003,15004,15005,15009,15011,15012,15015,15023,15029,15035,15040,15041,15044,15051,15057,15061,15062,15065,15067,15074,15076,15084,15087,15088,15089,15093,15095,15098,15099,15105,15107,15110,15113,15118,15119,15131,15142,15145,15155,15161,15171,15177,15187,15201,15202,15212,15219,15225,15231,15232,15239,15256,15266,15271,15273,15277,15281,15284,15286,15287,15288,15295,15303,15325,15326,15330,15336,15339,15342,15343,15344,15346,15347,15349,15364,15377,15387,15389,15390,15392,15399,15403,15406,15408,15409,15413,15415,15421,15436,15437,15438,15441,15442,15463,15494,15510,15512,15528,15540,15554,15559,15564,15567,15569,15571,15572,15575,15584,15607,15610,15619,15631,15632,15666,15667,15670,15679,15680,15682,15685,15694,15710,15718,15720,15722,15724,15726,15728,15735,15739,15743,15748,15752,15755,15787,15795,15797,15798,15801,15802,15807,15818,15819,15827,15837,15838,15839,15842,15845,15846,15847,15848,15865,15866,15867,15868,15870];
        foreach ($customer_ids as $cid) {
            $customer = $this->MCustomer->get_one('*', ['id' => $cid]);
            // 已经处于公海的客户不做处理
            if (empty($customer) || $customer['invite_id'] == -1){
                continue;
            }

            // 记录日志
            $this->MCustomer_transfer_log->record(C('customer.public_sea_code'), $cid, $cur, '上海地区批量释放长时间内未下单客户到公海');
            // 将客户踢到公海
            $this->MCustomer->update_info(['invite_id' => -1], ['id' => $cid]);
            $count++;
        }

        echo ' \n ' . $count . ' customer changes done.';
    }

    public function fix_normal_products($location_id) {
        // 找到只有
        $query_sql = 'select id from t_product where sku_number not in (select sku_number from t_product where customer_type = 2 and status = 1 and location_id = ' .$location_id. ') and customer_type = 1 and status = 1 and location_id = ' . $location_id . ';';
        $products = $this->db->query($query_sql)->result_array();
        $total = count($products);
        echo '共有' . $total;
        $product_ids = implode(',', array_column($products, 'id'));
        $update_product_sql = 'update t_product set customer_visiable=1 where id in(' . $product_ids .')';
        $this->db->query($update_product_sql);
    }

    /**
     * 修复定时调价表的product_id，设置为该产品的最新id
     * @author yugang@dachuwang.com
     * @since 2015-08-26
     */
    public function fix_price_product_id() {
        $price_list = $this->MProduct_price->get_lists('*');
        $error_count = $count = 0;
        foreach ($price_list as $price) {
            $count++;
            $product = $this->MProduct->get_one('*', ['sku_number' => $price['sku_number'], 'location_id' => $price['location_id']]);
            if (empty($product)) {
                $error_count++;
                continue;
            }
            $this->MProduct_price->update_info(['product_id' => $product['id']], ['id' => $price['id']]);
        }

        echo '共更新了' . $count . '条数据，其中' . $error_count . '条更新失败！';
    }
    // 修复咨询单类型
    public function fix_consult_ctype() {
        $sql = "update t_consult set ctype = 20 where ctype in (5,6,8,9,10)";
        $this->db->query($sql);
        $sql2 = "update t_consult set ctype = 5 where ctype = 7";
        $this->db->query($sql2);
    }
    
    public function fix_customer_latest_ordered_time(){
        $query_sql = 'update t_customer set latest_ordered_time = (select max(created_time) from t_order where t_customer.id = t_order.user_id)';
        $this->db->query($query_sql);
    }
}

/* End of file demo.php */
/* Location: ./application/controllers/demo.php */
