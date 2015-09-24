<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 线路基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-23
 */
class Line extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MLine', 'MLocation', 'MCustomer'));
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MLine->get_one('*', array('id' => $this->input->post('id', TRUE)));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function lists() {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('status.common.success');
        if(isset($_POST['status']) && 'all' != $_POST['status']) {
            $where['status'] = $_POST['status'];
        }
        if(!empty($_POST['searchValue'])) {
            $where['like'] = array('name' => $_POST['searchValue']);
        }
        if(!empty($_POST['line_ids'])) {
            $where['in'] = array('id' => $_POST['line_ids']);
        }
        if(!empty($_POST['cityId'])) {
            $where['location_id'] = $_POST['cityId'];
        }
        /*
        if(!empty($_POST['siteId'])) {
            $where['site_src'] = $_POST['siteId'];
        }
        */
        if(!empty($_POST['wh_id'])) {
            $where['warehouse_id'] = $_POST['wh_id'];
        }
        $order_by = array('updated_time' => 'DESC');
        $list = $this->MLine->get_lists('*', $where, $order_by, array(), $page['offset'], $page['page_size']);
        $total = $this->MLine->count($where);

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
     * 添加线路页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function create_input() {
        // 数据处理
        $list = $this->MLocation->get_lists('*', array('upid' => 0));
        // 系统类别
        //$sites = array(C('app_sites.chu'), C('app_sites.guo'));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'list'   => $list,
                //'sites'  => $sites,
            )
        );
    }

    /**
     * 添加线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('name', '线路名称', 'trim|required');
        $this->form_validation->set_rules('locationId', '所属城市', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        if ($insert_id = $this->MLine->create($data)) {
            // 线路添加成功
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '线路添加成功',
                )
            );
        } else {
            // 线路添加失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '线路添加失败'
                )
            );
        }
    }

    /**
     * 修改线路页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MLine->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $list = $this->MLocation->get_lists('*', array('upid' => 0));
        // 系统类别
        //$sites = array(C('app_sites.chu'), C('app_sites.guo'));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
                'list'   => $list,
                //'sites'  => $sites,
            )
        );
    }

    /**
     * 修改线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', '线路名称', 'trim|required');
        $this->form_validation->set_rules('locationId', '所属城市', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $id = $this->input->post('id', TRUE);
        // 线路修改，入库
        $result = $this->MLine->update_by('id', $id, $data);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MLine->false_delete($where);

        // 返回结果
        $this->_return($result);
    }


    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    private function _format_data() {
        $data = array();
        $data['name'] = $this->input->post('name', TRUE);
        $data['full_name'] = $this->input->post('fullName', TRUE);
        $data['description'] = $this->input->post('description', TRUE);
        $data['location_id'] = $this->input->post('locationId', TRUE);
        $data['warehouse_id'] = $this->input->post('warehouseId', TRUE);
        $data['warehouse_name'] = $this->input->post('warehouseName', TRUE);
        //$data['site_src'] = $this->input->post('siteSrc', TRUE);
        $data['site_src'] = C("site.dachu");
        $data['status'] = 1;
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data = array_filter($data);
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    private function _format_list($list) {
        $result = array();
        $line_count_list = $this->MCustomer->count_by_line(array_column($list, 'id'));
        foreach ($list as $k => $v) {
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['line_count'] = 0;
            foreach ($line_count_list as $line) {
                if($v['id'] == $line['line_id']) {
                    $v['line_count'] = $line['count'];
                }
            }
            $result[] = $v;
        }

        return $result;
    }

    public function get_all() {
        $data = $this->MLine->get_lists('id, location_id, site_src, name, full_name', array('status' => C('status.common.success'), 'id >' => 0));
        $this->_return_json(array('status' => C('tips.code.op_success'), 'list' => $data));
    }

    public function get_warehouses() {
        $where = $this->_set_get_warehouses_where();
        $warehouses = $this->MLine->get_lists('warehouse_id', $where);
        $this->_return_json(array('status' => C('tips.code.op_success'), 'list' => $warehouses));
    }

    private function _set_get_warehouses_where() {
        $where = [];
        if(isset($_POST['locaiton_id'])) {
            $where['location_id'] = $_POST['location_id'];
        }
        if(!empty($_POST['line_id'])) {
            $where['id'] = $_POST['line_id'];
        }
        return $where;
    }
}

/* End of file line.php */
/* Location: :./application/controllers/line.php */
