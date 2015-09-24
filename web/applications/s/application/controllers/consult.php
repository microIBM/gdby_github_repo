<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 咨询基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-05-22
 */
class Consult extends MY_Controller {

    private $_ctype_dict = [];
    private $_source_dict = [];
    private $_channel_dict = [];
    private $_status_dict = [];

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MConsult',
                'MUser',
            )
        );
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);

        // 咨询类型对应关系
        $ctype_config        = array_values(C('consult.ctype'));
        $codes               = array_column($ctype_config, 'code');
        $msgs                = array_column($ctype_config, 'msg');
        $this->_ctype_dict   = array_combine($codes, $msgs);

        $source_config       = array_values(C('consult.source'));
        $codes               = array_column($source_config, 'code');
        $msgs                = array_column($source_config, 'msg');
        $this->_source_dict  = array_combine($codes, $msgs);

        $channel_config      = array_values(C('consult.channel'));
        $codes               = array_column($channel_config, 'code');
        $msgs                = array_column($channel_config, 'msg');
        $this->_channel_dict = array_combine($codes, $msgs);

        $status_config       = array_values(C('consult.status'));
        $codes               = array_column($status_config, 'code');
        $msgs                = array_column($status_config, 'msg');
        $this->_status_dict  = array_combine($codes, $msgs);
    }

    /**
     * 查看咨询
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MConsult->get_one('*', array('id' => $this->input->post('id', TRUE)));
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }

    /**
     * 咨询列表
     * @author yugang@dachuwang.com
     * @since 2015-05-22
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
            } else {
                $where['like']['name'] = $_POST['searchValue'];
            }
        }
        if(!empty($_POST['operator'])) {
            $where['creator_id'] = $_POST['operator'];
        }
        if(!empty($_POST['ctype'])) {
            $where['ctype'] = $_POST['ctype'];
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
        $list = $this->MConsult->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MConsult->count($where);
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
     * 添加咨询页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function create_input() {
        // 返回结果
        $this->_return_json(
            array(
                'status'    => C('status.req.success'),
                'ctypes'    => array_values(C('consult.ctype')),
                'statues'   => array_values(C('consult.status')),
                'channnels' => array_values(C('consult.channel')),
                'sources'   => array_values(C('consult.source')),
            )
        );
    }

    /**
     * 添加咨询
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('ctype', '咨询类型', 'trim|required|numeric');
        $this->form_validation->set_rules('source', '问题来源', 'trim|required');
        $this->form_validation->set_rules('name', '姓名', 'required');
        $this->form_validation->set_rules('content', '咨询内容', 'required');
        $this->form_validation->set_rules('status', '状态', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        $data['creator_id']   = $_POST['creator_id'];
        $data['creator']      = $_POST['creator'];
        // 咨询添加，入库
        if ($insert_id = $this->MConsult->create($data)) {
            $this->_return_json(
                array(
                    'status' => C('status.req.success'),
                    'msg'    => '咨询添加成功',
                )
            );
        } else {
            // 咨询添加入库失败
            $this->_return_json(
                array(
                    'status' => C('status.req.failded'),
                    'msg'    => '咨询添加失败'
                )
            );
        }
    }


    /**
     * 修改咨询页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MConsult->get_one('*', array('id' => $_POST['id']));
        $data = $this->_format_list([$data]);
        $data = $data[0];

        // 返回结果
        $this->_return_json(
            array(
                'status'    => C('status.req.success'),
                'info'      => $data,
                'ctypes'    => array_values(C('consult.ctype')),
                'statues'   => array_values(C('consult.status')),
                'channnels' => array_values(C('consult.channel')),
                'sources'   => array_values(C('consult.source')),
            )
        );
    }

    /**
     * 修改咨询
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('ctype', '咨询类型', 'trim|required|numeric');
        $this->form_validation->set_rules('source', '问题来源', 'trim|required');
        $this->form_validation->set_rules('name', '姓名', 'required');
        $this->form_validation->set_rules('content', '咨询内容', 'required');
        $this->form_validation->set_rules('status', '状态', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        // 咨询修改，入库
        $result = $this->MConsult->update_by('id', $_POST['id'], $data);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除咨询
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $_POST['id'];
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MConsult->false_delete($where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 处理表单提交数据,做安全过滤
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    private function _format_data() {
        $data                    = array();
        $data['ctype']           = isset($_POST['ctype']) ? $_POST['ctype'] : 0;
        $data['source']          = isset($_POST['source']) ? $_POST['source'] : 0;
        $data['name']            = isset($_POST['name']) ? $_POST['name'] : '';
        $data['mobile']          = isset($_POST['mobile']) ? $_POST['mobile'] : '';
        $data['qq']              = isset($_POST['qq']) ? $_POST['qq'] : '';
        $data['wechat']          = isset($_POST['wechat']) ? $_POST['wechat'] : '';
        $data['channel']         = isset($_POST['channel']) ? $_POST['channel'] : 0;
        $data['company_name']    = isset($_POST['companyName']) ? $_POST['companyName'] : '';
        $data['company_area']    = isset($_POST['companyArea']) ? $_POST['companyArea'] : '';
        $data['company_address'] = isset($_POST['companyAddress']) ? $_POST['companyAddress'] : '';
        $data['content']         = isset($_POST['content']) ? $_POST['content'] : '';
        $data['solution']        = isset($_POST['solution']) ? $_POST['solution'] : '';
        $data['status']          = isset($_POST['status']) ? $_POST['status'] : C('consult.status.processing.code');
        $data['updated_time']    = $this->input->server("REQUEST_TIME");
        // $data                    = array_filter($data);
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    private function _format_list($list) {
        if(empty($list)) {
            return $list;
        }

        $result = array();
        foreach ($list as $k => $v) {
            $v['ctype_name'] = isset($this->_ctype_dict[$v['ctype']]) ? $this->_ctype_dict[$v['ctype']] : '';
            $v['source_name'] = isset($this->_source_dict[$v['source']]) ? $this->_source_dict[$v['source']] : '';
            $v['channel_name'] = isset($this->_channel_dict[$v['channel']]) ? $this->_channel_dict[$v['channel']] : '';
            $v['status_name'] = isset($this->_status_dict[$v['status']]) ? $this->_status_dict[$v['status']] : '';
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $result[] = $v;
        }

        return $result;
    }

}

/* End of file consult.php */
/* Location: :./application/controllers/consult.php */
