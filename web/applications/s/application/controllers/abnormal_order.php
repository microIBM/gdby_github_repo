<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 异常单基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-05-09
 */
class Abnormal_order extends MY_Controller {

    private $_otype_dict = [];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MAbnormal_order', 'MAbnormal_content', 'MCustomer', 'MOrder', 'MLocation', 'MUser', 'MLine', 'MSuborder'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);

        // 异常单类型对应关系
        $otype_config      = array_values(C('abnormal_order.otype'));
        $vals              = array_column($otype_config, 'val');
        $names             = array_column($otype_config, 'name');
        $this->_otype_dict = array_combine($vals, $names);
    }

    /**
     * 查看异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MAbnormal_order->get_one('*', array('id' => $this->input->post('id', TRUE)));
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 异常单列表
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status !='] = C('status.common.del');
        if(isset($_POST['status']) && $_POST['status'] != -1 && $_POST['status'] != '') {
            if(is_array($_POST['status'])) {
                $where['in']['status'] = $_POST['status'];
            } else {
                $where['status'] = $_POST['status'];
            }
        }
        if (!empty($_POST['searchValue'])) {
            if(preg_match("/^\d{5,11}$/", $_POST['searchValue'])){
                $where['like']['mobile'] = $_POST['searchValue'];
            } elseif(preg_match("/^\d{12,}$/", $_POST['searchValue'])){
                $where['like']['order_number'] = $_POST['searchValue'];
            } else {
                $where['like']['shop_name'] = $_POST['searchValue'];
            }
        }
        if(!empty($_POST['otype'])) {
            $where['otype'] = $_POST['otype'];
        }
        if(!empty($_POST['siteId'])) {
            $where['site_id'] = $_POST['siteId'];
        }
        if(!empty($_POST['cityId'])) {
            $where['city_id'] = $_POST['cityId'];
        }
        if(!empty($_POST['lineId'])) {
            $where['line_id'] = $_POST['lineId'];
        }
        if (!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if (!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if (!empty($_POST['ids'])) {
            $id_arr = explode(',', $_POST['ids']);
            $id_arr = array_filter($id_arr);
            $where['in'] = array('id' => $id_arr);
        }
        // 根据path排序，无需使用递归
        $order = array('created_time' => 'DESC');
        $list = $this->MAbnormal_order->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MAbnormal_order->count($where);
        $list = $this->_format_list($list);
        $arr = array(
            'status'     => C('status.req.success'),
            'list'       => $list,
            'total'      => $total,
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加异常单页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => 1);
        $order = array('path' => 'asc');
        $list = $this->MAbnormal_order->get_lists('*', $where, $order, array());
        $list = $this->_format_list($list);

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'list'   => $list,
            )
        );
    }

    /**
     * 添加异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('orderNumber', '订单编号', 'trim|required|numeric');
        $this->form_validation->set_rules('otype', '异常单类型', 'trim|required|numeric');
        $this->form_validation->set_rules('reason', '异常原因', 'trim|required');
        $this->form_validation->set_rules('solution', '处理方案', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $order = $this->MSuborder->get_one('*', ['order_number' => $_POST['orderNumber']]);
        if(empty($order)) {
            $this->_return(FALSE);
        }
        $customer = $this->MCustomer->get_one('*', ['id' => $order['user_id']]);
        $data['order_id']     = $order['id'];
        $data['order_number'] = $order['order_number'];
        $data['site_id']      = $order['site_src'];
        $data['city_id']      = $order['city_id'];
        $data['line_id']      = $order['line_id'];
        $data['name']         = $order['username'];
        $data['deliver_date'] = strtotime($order['deliver_date']);
        $data['deliver_time'] = $order['deliver_time'];
        $data['mobile']       = $customer['mobile'];
        $data['shop_name']    = $customer['shop_name'];
        $data['address']      = $customer['address'];
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        $data['creator']      = $_POST['creator'];
        $data['creator_id']   = $_POST['creator_id'];
        // 异常单添加，入库
        if ($insert_id = $this->MAbnormal_order->create($data)) {
            // 添加异常单内容
            $contents = $_POST['contents'];
            $content_arr = [];
            foreach ($contents as $content) {
                $data                 = [];
                $product              = $content['product'];
                $data['aid']          = $insert_id;
                $data['order_id']     = $order['id'];
                $data['product_id']   = $product['product_id'];
                $data['name']         = $product['name'];
                $data['single_price'] = $product['single_price'] * 100;
                $data['sum_price']    = $product['sum_price'] * 100;
                $data['quantity']     = $product['quantity'];
                $data['spec']         = json_encode($product['spec']);
                $data['created_time'] = $this->input->server("REQUEST_TIME");
                $data['updated_time'] = $this->input->server("REQUEST_TIME");
                $data['status']       = C('status.common.success');
                $content_arr[]        = $data;
            }
            $this->MAbnormal_content->create_batch($content_arr);

            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '异常单添加成功',
                )
            );
        } else {
            // 异常单添加入库失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '异常单添加失败'
                )
            );
        }
    }


    /**
     * 修改异常单页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MAbnormal_order->get_one('*', array('id' => $_POST['id']));
        $data = $this->_format_list([$data]);
        $data = $data[0];

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 修改异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('otype', '异常单类型', 'trim|required|numeric');
        $this->form_validation->set_rules('reason', '异常原因', 'trim|required');
        $this->form_validation->set_rules('solution', '处理方案', 'trim|required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        // 异常单修改，入库
        $result = $this->MAbnormal_order->update_by('id', $_POST['id'], $data);
        // 删除异常单内容
        $this->MAbnormal_content->false_delete(['aid' => $_POST['id']]);
        // 添加异常单内容
        $contents = $_POST['contents'];
        $content_arr = [];
        foreach ($contents as $content) {
            $data                 = [];
            $product              = $content['product'];
            $data['aid']          = $_POST['id'];
            $data['order_id']     = $product['order_id'];
            $data['product_id']   = $product['product_id'];
            $data['name']         = $product['name'];
            $data['single_price'] = $product['single_price'] * 100;
            $data['sum_price']    = $product['sum_price'] * 100;
            $data['quantity']     = $product['quantity'];
            $data['spec']         = json_encode($product['spec']);
            $data['created_time'] = $this->input->server("REQUEST_TIME");
            $data['updated_time'] = $this->input->server("REQUEST_TIME");
            $data['status']       = C('status.common.success');
            $content_arr[]        = $data;
        }
        $this->MAbnormal_content->create_batch($content_arr);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $_POST['id'];
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MAbnormal_order->false_delete($where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    private function _format_data() {
        $data                 = array();
        $data['reason']       = isset($_POST['reason']) ? $_POST['reason'] : '';
        $data['solution']     = isset($_POST['solution']) ? $_POST['solution'] : '';
        $data['otype']        = $_POST['otype'];
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data['status']       = $_POST['status'];
        $data['suggest']      = isset($_POST['suggest']) ? $_POST['suggest'] : '';
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    private function _format_list($list) {
        if(empty($list)) {
            return $list;
        }
        $line_ids = array_column($list, 'line_id');
        $line_list = $this->MLine->get_lists('id, name', array('in' => array('id' => $line_ids)));
        $line_dict = array_combine(array_column($line_list, 'id'), array_column($line_list, 'name'));

        $city_ids = array_column($list, 'city_id');
        $city_list = $this->MLocation->get_lists('id, name', array('in' => array('id' => $city_ids)));
        $city_dict = array_combine(array_column($city_list, 'id'), array_column($city_list, 'name'));

        $aids = array_column($list, 'id');
        $content_list = $this->MAbnormal_content->get_lists('*', ['in' => ['aid' => $aids], 'status' => C('status.common.success')]);
        $content_dict = [];
        foreach ($content_list as $content) {
            $content['single_price'] /= 100;
            $content['sum_price'] /= 100;
            $content_dict[$content['aid']][] = $content;
        }

        $result = array();
        foreach ($list as $k => $v) {
            $v['contents'] = isset($content_dict[$v['id']]) ? $content_dict[$v['id']] : [];
            $v['line_name'] = isset($line_dict[$v['line_id']]) ? $line_dict[$v['line_id']] : '';
            $v['city_name'] = isset($city_dict[$v['city_id']]) ? $city_dict[$v['city_id']] : '';
            $v['otype_name'] = isset($this->_otype_dict[$v['otype']]) ? $this->_otype_dict[$v['otype']] : '';
            $v['site_name'] = $v['site_id'] == C('site.code.dachu.id') ? C('site.code.dachu.name') : C('site.code.daguo.name');
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $result[] = $v;
        }

        return $result;
    }

}

/* End of file abnormal_order.php */
/* Location: :./application/controllers/abnormal_order.php */
