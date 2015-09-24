<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 配送单基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-04-08
 */
class Distribution extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MDistribution',
                'MOrder',
                'MSuborder',
                'MOrder_detail',
                'MWorkflow_log',
                'MUser',
                'MLine',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'skip32',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function view() {
        if(!empty($_POST['dist_number'])) {
            $map['dist_number'] = $_POST['dist_number'];
        }
       else{
            $this->form_validation->set_rules('id', 'ID', 'required|numeric');
            $this->validate_form();
            $map = array('id' => $this->input->post('id', TRUE));
        }
        // 数据查询
        $data = $this->MDistribution->get_one('*', $map);
        $data['dist_number'] = C('barcode.prefix.dispatch') . $data['dist_number'];
        $data['created_time'] = date('Y-m-d H:i:s', $data['created_time']);
        $data['total_price'] /= 100;
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 配送单列表
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        if (!empty($_POST['cityId'])) {
            $where['city_id'] = $_POST['cityId'];
        }
        if (!empty($_POST['orderType'])) {
            $where['order_type'] = $_POST['orderType'];
        }
        if (!empty($_POST['siteId'])) {
            $where['site_src'] = $_POST['siteId'];
        }
        if (!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if (!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if (!empty($_POST['searchValue'])) {
            $where['like'] = array('name' => $_POST['searchValue']);
        }
        if(isset($_POST['status']) && $_POST['status'] != -1 && $_POST['status'] != '') {
            if(is_array($_POST['status'])) {
                $where['in']['status'] = $_POST['status'];
            } else {
                $where['status'] = $_POST['status'];
            }
        }
        if (!empty($_POST['dist_ids'])) {
            $dist_ids = $_POST['dist_ids'];
            if(!is_array($dist_ids)) {
                $dist_ids = explode(',', $dist_ids);
                $dist_ids = array_filter($dist_ids);
            }
            $where['in'] = array('id' => $dist_ids);
        }
        if (!empty($_POST['dist_numbers'])) {
            $dist_number_arr = explode(',', $this->input->post('dist_numbers', TRUE));
            $dist_number_arr = array_filter($dist_number_arr);
            foreach ($dist_number_arr as &$dist_number) {
                $dist_number = ltrim($dist_number, C('barcode.prefix.dispatch'));
            }
            $where['in'] = array('dist_number' => $dist_number_arr);
        }
        $list = $this->MDistribution->get_lists('*', $where, array('created_time' => 'desc'), array(), $page['offset'], $page['page_size']);
        $total = $this->MDistribution->count($where);
        $list = $this->_format_list($list);

        $code_with_cn = array_values(C('distribution.status'));
        $codes        = array_column($code_with_cn, 'code');
        $codes[] = -1;// 全部
        foreach($codes as $v) {
            if($v != -1) {
                $where['status'] = $v;
            }else{
                unset($where['status']);
            }
            $totals[$v] = $this->MDistribution->count($where);
        }
        $arr = array(
            'status'     => C('status.req.success'),
            'list'       => $list,
            'total'      => $total,
            'totals'      => $totals,
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加配送单:
     *  1)生成配送单
     *  2)修改配送单对应订单状态
     *  3)添加配送单对应状态日志
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function create() {
        // 解析订单ID
        $distributions = $_POST['distributions'];
        $order_ids_all = array_column($distributions, 'order_ids');
        $order_ids_all = implode(',', $order_ids_all);
        $order_ids_all = explode(',', $order_ids_all);
        $order_ids_all = array_filter($order_ids_all);

        $dist_number_arr = array();
        // 验证order_id是否合法
        // $this-_validate_order_ids($order_ids_all);
        //批量取出订单详情
        $detail_map = $this->_get_detail_map($order_ids_all);
        $cur = $_POST['cur'];
        // 循环处理每一个配送单
        foreach ($distributions as $dist) {
            $order_ids = explode(',', $dist['order_ids']);
            $order_list = $this->MSuborder->get_lists('*', array('in' => array('id' => $order_ids)));
            $order_dict = array_combine(array_column($order_list, 'id'), $order_list);
            $total_price = 0;
            $deal_price  = 0;
            $line_count  = 0;
            $sku_count   = 0;
            $order_count = count($order_list);
            // 计算总单数、行数、件数、价格等
            foreach ($order_list as $order) {
                $total_price += $order['total_price'];
                $deal_price += $order['deal_price'];
                $order_detail_list = $detail_map[$order['id']];
                $line_count += count($order_detail_list);
                foreach ($order_detail_list as $order_detail) {
                    $sku_count += $order_detail['quantity'];
                }
            }


            // 一张配送单上的线路和所属系统是一致的
            $site_src = $order_list[0]['site_src'];
            $line_id = $order_list[0]['line_id'];
            $city_id = $order_list[0]['location_id'];
            $order_type = $order_list[0]['order_type'];
            $data = array(
                'remarks'        => isset($_POST['remarks']) ? $_POST['remarks'] : '',
                'deliver_time'   => !empty($_POST['deliver_time']) ? $_POST['deliver_time'] : '1',
                'total_price'    => $total_price,
                'deal_price'     => $deal_price,
                'site_src'       => $site_src,
                'line_id'        => $line_id,
                'order_count'    => $order_count,
                'order_type'     => $order_type,
                'line_count'     => $line_count,
                'sku_count'      => $sku_count,
                'total_distance' => isset($_POST['total_distance']) ? $_POST['total_distance'] : '0',
                'creator'        => $cur['name'],
                'creator_id'     => $cur['id'],
                'created_time'   => $this->input->server('REQUEST_TIME'),
                'updated_time'   => $this->input->server('REQUEST_TIME'),
                'status'         => C('status.common.success'),
                'city_id'        => $city_id,
            );
            $data = array_filter($data);

            // 1.配送单添加，入库
            $dist_id = $this->MDistribution->create($data);
            // 生成配送单号并修改
            $dist_number = $this->_gen_dist_number($dist_id);
            $dist_number_arr[] = C('barcode.prefix.dispatch') . $dist_number;
            $this->MDistribution->update_info(array('dist_number' => $dist_number), array('id' => $dist_id));

            // 2.修改配送单对应的订单的dist_id,dist_order和状态
            $dist_order = 1;
            foreach ($order_ids as $order_id) {
                $order = $order_dict[$order_id];
                $data = array(
                    'dist_id'    => $dist_id,
                    'dist_order' => $dist_order++,
                    'status'  => C('order.status.allocated.code'),
                );
                $where = array(
                    'id' => $order_id,
                    'status !=' => C('order.status.closed.code'),
                );
                $this->MSuborder->update_info($data, $where);
                $this->MOrder_detail->update_info(array('status' => C('order.status.allocated.code')), array('suborder_id' => $order_id));
            }

            // 3.订单工作流日志记录
            foreach ($order_ids as $order_id) {
                $this->MWorkflow_log->record_order($order_id, C('order.status.allocated.code'), $cur);
            }
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '添加配送单成功',
                'dist_numbers' => implode(',', $dist_number_arr),
            )
        );
    }


    /**
     * 修改配送单页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MDistribution->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $data['dist_number'] = C('barcode.prefix.dispatch') . $data['dist_number'];

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 修改配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', '配送单名称', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $id = $this->input->post('id', TRUE);
        // 配送单修改，入库
        $result = $this->MDistribution->update_by('id', $id, $data);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 打印配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-11
     */
    public function prints(){
        // 表单校验
        $this->form_validation->set_rules('dist_ids', 'ID', 'required');
        $this->validate_form();

        // 数据处理
        $dist_ids = $_POST['dist_ids'];
        $data = array('is_printed' => 1);
        $where = array('in' => array('id' => $dist_ids));
        // 配送单修改，入库
        $result = $this->MDistribution->update_info($data, $where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除配送单
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MDistribution->false_delete($where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    private function _format_data() {
        $data = array();
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data = array_filter($data);
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    private function _format_list($list) {
        $result = array();
        $line_list = $this->MLine->get_lists('*', array('status' => C('status.common.success')));
        $line_ids = array_column($line_list, 'id');
        $line_dict = array_combine($line_ids, $line_list);
        foreach ($list as $k => $v) {
            $v['dist_number'] = C('barcode.prefix.dispatch') . $v['dist_number'];
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $v['total_price'] /= 100;
            $v['deal_price'] /= 100;
            if(isset($line_dict[$v['line_id']])){
                $v['warehouse_name'] = $line_dict[$v['line_id']]['warehouse_name'];
                $v['line_name'] = $line_dict[$v['line_id']]['name'];
            }
            $result[] = $v;
        }

        return $result;
    }

    /**
     * 获取订单详情Map
     * @author yugang@dachuwang.com
     * @since 2015-04-08
     */
    private function _get_detail_map($order_ids) {
        $where = [
            'in' => [ 'suborder_id' => $order_ids ]
        ];
        $order_details = $this->MOrder_detail->get_lists(
            '*',
            $where
        );
        $detail_map = [];
        foreach($order_details as &$item) {
            $order_id = $item['suborder_id'];
            $item['price']     /= 100;
            $item['sum_price'] /= 100;
            $item['actual_price'] /= 100;
            $item['actual_sum_price'] /= 100;
            $item['created_time'] = date('Y/m/d H:i', $item['created_time']);
            $item['updated_time'] = date('Y/m/d H:i', $item['updated_time']);
            $spec = json_decode($item['spec'], TRUE);
            if(!empty($spec)) {
                foreach($spec as $idx => $spec_arr) {
                    if(empty($spec_arr['name']) || empty($spec_arr['val'])) {
                        unset($spec[$idx]);
                    }
                }
                $item['spec'] = $spec;
            } else {
                $item['spec'] = '';
            }
            if(isset($detail_map[$order_id])) {
                $detail_map[$order_id][] = $item;
            } else {
                $detail_map[$order_id] = [
                    $item
                ];
            }
        }

        return $detail_map;
    }

    /**
     * 验证order_id是否合法
     * @author yugang@dachuwang.com
     * @since 2015-04-09
     */
    private function _validate_order_ids(){
        return TRUE;
    }

    /**
     * 生成唯一的配送单号
     * @author yugang@dachuwang.com
     * @since 2015-04-09
     */
    private function _gen_dist_number($counter){
        $serial_no = $this->skip32->get_serial_no($counter);
        return $serial_no;
    }
}

/* End of file distribution.php */
/* Location: :./application/controllers/distribution.php */
