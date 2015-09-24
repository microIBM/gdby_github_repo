<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 投诉基础服务
 *
 * @author yugang@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-05-14
 */
class Complaint extends MY_Controller
{

    private $_ctype_dict = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'MComplaint',
            'MComplaint_content',
            'MLocation',
            'MImage',
            'MUser',
            'MLine',
            'MCustomer',
            'MOrder',
            'MSuborder'
        ));
        $this->load->library(array(
            'form_validation'
        ));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);

        // 投诉类型对应关系
        $ctype_config = array_values(C('complaint.ctype'));
        $codes = array_column($ctype_config, 'code');
        $msgs = array_column($ctype_config, 'msg');
        $this->_ctype_dict = array_combine($codes, $msgs);
    }

    /**
     * 查看投诉
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function view()
    {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MComplaint->get_one('*', array(
            'id' => $this->input->post('id', TRUE)
        ));
        // 返回结果
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'info' => $data
        ));
    }

    /**
     * 投诉列表
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function lists()
    {
        // 参数解析&数据查询
        $page = $this->get_page();
        $where = array();
        $where['status !='] = C('status.common.del');
        if (isset($_POST['status']) && $_POST['status'] != - 1 && $_POST['status'] != '') {
            if (is_array($_POST['status'])) {
                $where['in']['status'] = $_POST['status'];
            } else {
                $where['status'] = $_POST['status'];
            }
        }
        if (! empty($_POST['searchValue'])) {
            if (preg_match("/^\d{5,11}$/", $_POST['searchValue'])) {
                $where['like']['mobile'] = $_POST['searchValue'];
            } elseif (preg_match("/^\d{12,}$/", $_POST['searchValue'])) {
                $where['like']['order_number'] = $_POST['searchValue'];
            } else {
                $where['like']['shop_name'] = $_POST['searchValue'];
            }
        }
        if (! empty($_POST['userId'])) {
            $where['user_id'] = $_POST['userId'];
        }
        if (! empty($_POST['ctype'])) {
            $where['ctype'] = $_POST['ctype'];
        }
        if (! empty($_POST['siteId'])) {
            $where['site_id'] = $_POST['siteId'];
        }
        if (! empty($_POST['cityId'])) {
            $where['city_id'] = $_POST['cityId'];
        }
        if (! empty($_POST['lineId'])) {
            $where['line_id'] = $_POST['lineId'];
        }
        if (! empty($_POST['operator'])) {
            $where['creator_id'] = $_POST['operator'];
        }
        if (! empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if (! empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if (! empty($_POST['ids'])) {
            $id_arr = explode(',', $_POST['ids']);
            $id_arr = array_filter($id_arr);
            $where['in'] = array(
                'id' => $id_arr
            );
        }
        // 根据path排序，无需使用递归
        $order = array(
            'created_time' => 'DESC'
        );
        $list = $this->MComplaint->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MComplaint->count($where);
        $list = $this->_format_list($list);
        $arr = array(
            'status' => C('status.req.success'),
            'list' => $list,
            'total' => $total
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加投诉页面数据获取
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function create_input()
    {
        // 数据处理
        $where = array(
            'status' => 1
        );
        $order = array(
            'path' => 'asc'
        );
        $list = $this->MComplaint->get_lists('*', $where, $order, array());
        $list = $this->_format_list($list);

        // 返回结果
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'list' => $list
        ));
    }

    /**
     * 添加投诉
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function create()
    {
        // 表单校验
        $this->form_validation->set_rules('orderNumber', '订单编号', 'trim|required|numeric');
        $this->form_validation->set_rules('ctype', '投诉类型', 'trim|required|numeric');
        $this->form_validation->set_rules('description', '问题描述', 'trim|required');
        // $this->form_validation->set_rules('contents', '投诉内容', 'required');
        $this->validate_form();
        // 数据处理
        $data = $this->_format_data();
        $suborder = $this->MSuborder->get_one('*', [
            'order_number' => $_POST['orderNumber']
        ]);
        if (empty($suborder)) {
            $this->_return(FALSE);
        }
        $customer = $this->MCustomer->get_one('*', [
            'id' => $suborder['user_id']
        ]);
        $data['order_id'] = $suborder['id'];
        $data['order_number'] = $suborder['order_number'];
        $data['site_id'] = $suborder['site_src'];
        $data['city_id'] = $suborder['city_id'];
        $data['line_id'] = $suborder['line_id'];
        $data['user_id'] = $suborder['user_id'];
        $data['name'] = $suborder['username'];
        $data['deliver_date'] = $suborder['deliver_date'];
        $data['deliver_time'] = $suborder['deliver_time'];
        $data['invite_id'] = $suborder['sale_id'];
        $data['mobile'] = $customer['mobile'];
        $data['shop_name'] = $customer['shop_name'];
        $data['address'] = $customer['address'];
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        $data['creator_id'] = $_POST['creator_id'];
        $data['creator'] = $_POST['creator'];
        // 投诉添加，入库
        if ($insert_id = $this->MComplaint->create($data)) {
            // 添加图片
            if (! empty($_POST['imgUploads'])) {
                $imgUploads = $_POST['imgUploads'];
                $img_arr = [];
                foreach ($imgUploads as $img) {
                    $data = [];
                    $data['owner_type'] = C('complaint.owner_type');
                    $data['owner_id'] = $insert_id;
                    $data['url'] = $img['dataUrl'];
                    $data['mime_type'] = $img['type'];
                    $data['file_size'] = $img['size'];
                    $data['created_time'] = $this->input->server("REQUEST_TIME");
                    $data['updated_time'] = $this->input->server("REQUEST_TIME");
                    $data['status'] = C('status.common.success');
                    $img_arr[] = $data;
                }
                $this->MImage->create_batch($img_arr);
            }

            // 添加投诉内容
            if (isset($_POST['contents']) && ! empty($_POST['contents'])) {
                $contents = $_POST['contents'];
                $content_arr = [];
                foreach ($contents as $content) {
                    $data = [];
                    $product = $content['product'];
                    $data['cid'] = $insert_id;
                    $data['order_id'] = $suborder['id'];
                    $data['product_id'] = $product['product_id'];
                    $data['name'] = $product['name'];
                    $data['single_price'] = $product['single_price'] * 100;
                    $data['sum_price'] = sprintf("%0.2f", ($product['single_price'] * $content['quantity'])) * 100;
                    $data['quantity'] = $content['quantity'];
                    $data['spec'] = json_encode($product['spec']);
                    $data['created_time'] = $this->input->server("REQUEST_TIME");
                    $data['updated_time'] = $this->input->server("REQUEST_TIME");
                    $data['status'] = C('status.common.success');
                    $content_arr[] = $data;
                }
                $this->MComplaint_content->create_batch($content_arr);
            }
            $this->_return_json(array(
                'status' => C('status.req.success'),
                'msg' => '投诉添加成功'
            ));
        } else {
            // 投诉添加入库失败
            $this->_return_json(array(
                'status' => C('status.req.failded'),
                'msg' => '投诉添加失败'
            ));
        }
    }

    /**
     * 修改投诉页面数据获取
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function edit_input()
    {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据查询
        $data = $this->MComplaint->get_one('*', array(
            'id' => $_POST['id']
        ));
        $data = $this->_format_list([
            $data
        ]);
        $data = $data[0];

        // 返回结果
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'info' => $data
        ));
    }

    /**
     * 修改投诉
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function edit()
    {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('ctype', '投诉类型', 'trim|required|numeric');
        $this->form_validation->set_rules('description', '问题描述', 'trim|required');
        // $this->form_validation->set_rules('contents', '投诉内容', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_format_data();
        // 投诉修改，入库
        $result = $this->MComplaint->update_by('id', $_POST['id'], $data);
        // 删除图片
        $this->MImage->false_delete([
            'owner_type' => C('complaint.owner_type'),
            'owner_id' => $_POST['id']
        ]);
        // 添加图片
        if (! empty($_POST['imgUploads'])) {
            $imgUploads = $_POST['imgUploads'];
            $img_arr = [];
            foreach ($imgUploads as $img) {
                $data = [];
                $data['owner_type'] = C('complaint.owner_type');
                $data['owner_id'] = $_POST['id'];
                $data['url'] = $img['dataUrl'];
                $data['mime_type'] = $img['type'];
                $data['file_size'] = $img['size'];
                $data['created_time'] = $this->input->server("REQUEST_TIME");
                $data['updated_time'] = $this->input->server("REQUEST_TIME");
                $data['status'] = C('status.common.success');
                $img_arr[] = $data;
            }
            $this->MImage->create_batch($img_arr);
        }
        // 删除投诉内容
        $this->MComplaint_content->false_delete([
            'cid' => $_POST['id']
        ]);
        // 添加投诉内容
        if (isset($_POST['contents']) && ! empty($_POST['contents'])) {
            $contents = $_POST['contents'];
            $content_arr = [];
            foreach ($contents as $content) {
                $data = [];
                $product = $content['product'];
                $data['cid'] = $_POST['id'];
                $data['order_id'] = $product['order_id'];
                $data['product_id'] = $product['product_id'];
                $data['name'] = $product['name'];
                $data['single_price'] = $product['single_price'] * 100;
                $data['sum_price'] = sprintf("%0.2f", ($product['single_price'] * $content['quantity'])) * 100;
                $data['quantity'] = $content['quantity'];
                $data['spec'] = json_encode($product['spec']);
                $data['created_time'] = $this->input->server("REQUEST_TIME");
                $data['updated_time'] = $this->input->server("REQUEST_TIME");
                $data['status'] = C('status.common.success');
                $content_arr[] = $data;
            }
            $this->MComplaint_content->create_batch($content_arr);
        }
        // 返回结果
        $this->_return($result);
    }

    /**
     * 删除投诉
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function delete()
    {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $_POST['id'];
        $where = array(
            'id' => $del_id
        );
        // 假删除数据
        $result = $this->MComplaint->false_delete($where);
        // 删除投诉内容
        $this->MComplaint_content->false_delete([
            'cid' => $del_id
        ]);
        // 删除投诉对应图片
        $this->MImage->false_delete([
            'owner_id' => $del_id,
            'owner_type' => C('complaint.owner_type')
        ]);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 处理表单提交数据,做安全过滤
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    private function _format_data()
    {
        $data = array();
        $data['ctype'] = $_POST['ctype'];
        $data['source'] = isset($_POST['source']) ? $_POST['source'] : '';
        $data['feedback'] = isset($_POST['feedback']) ? $_POST['feedback'] : '';
        $data['total_price'] = isset($_POST['totalPrice']) ? $_POST['totalPrice'] * 100 : 0;
        $data['description'] = isset($_POST['description']) ? $_POST['description'] : '';
        $data['solution'] = isset($_POST['solution']) ? $_POST['solution'] : '';
        $data['sale_id'] = isset($_POST['saleId']) ? $_POST['saleId'] : 0;
        $data['logistics_id'] = isset($_POST['logisticsId']) ? $_POST['logisticsId'] : 0;
        $data['progress1'] = isset($_POST['progress1']) ? $_POST['progress1'] : '';
        $data['progress2'] = isset($_POST['progress2']) ? $_POST['progress2'] : '';
        $data['progress3'] = isset($_POST['progress3']) ? $_POST['progress3'] : '';
        $data['suggest'] = isset($_POST['suggest']) ? $_POST['suggest'] : '';
        $data['status'] = isset($_POST['status']) ? $_POST['status'] : C('status.common.success');
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data['result_param'] = isset($_POST['result_param']) ? $_POST['result_param'] : '';
        $data = array_filter($data);
        $data['deal_result'] = isset($_POST['deal_result']) ? $_POST['deal_result'] : '0';
        $data['relation_content'] = isset($_POST['relation_content']) ? $_POST['relation_content'] : 0;
        return $data;
    }

    /**
     * 处理列表数据
     *
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    private function _format_list($list)
    {
        if (empty($list)) {
            return $list;
        }
        $line_ids = array_column($list, 'line_id');
        $line_list = $this->MLine->get_lists('id, name', array(
            'in' => array(
                'id' => $line_ids
            )
        ));
        $line_dict = array_combine(array_column($line_list, 'id'), array_column($line_list, 'name'));

        $city_ids = array_column($list, 'city_id');
        $city_list = $this->MLocation->get_lists('id, name', array(
            'in' => array(
                'id' => $city_ids
            )
        ));
        $city_dict = array_combine(array_column($city_list, 'id'), array_column($city_list, 'name'));

        $invite_ids = array_column($list, 'invite_id');
        $sale_ids = array_column($list, 'sale_id');
        $logistics_ids = array_column($list, 'logistics_id');
        $user_ids = array_merge($invite_ids, $sale_ids, $logistics_ids);
        $user_ids = array_unique($user_ids);
        $user_ids = array_filter($user_ids);
        if (empty($user_ids)) {
            $user_dict = [];
        } else {
            $user_list = $this->MUser->get_lists('id, name', array(
                'in' => array(
                    'id' => $user_ids
                )
            ));
            $user_dict = array_combine(array_column($user_list, 'id'), array_column($user_list, 'name'));
        }

        $cids = array_column($list, 'id');
        $content_list = $this->MComplaint_content->get_lists('*', [
            'in' => [
                'cid' => $cids
            ],
            'status' => C('status.common.success')
        ]);
        $content_dict = [];
        foreach ($content_list as $content) {
            $content['single_price'] /= 100;
            $content['sum_price'] /= 100;
            $content_dict[$content['cid']][] = $content;
        }
        $image_list = $this->MImage->get_lists('*', [
            'owner_type' => C('complaint.owner_type'),
            'in' => [
                'owner_id' => $cids
            ],
            'status' => C('status.common.success')
        ]);
        $image_dict = [];
        foreach ($image_list as $image) {
            $image['dataUrl'] = $image['url'];
            $image['type'] = $image['mime_type'];
            $image['size'] = $image['file_size'];
            $image_dict[$image['owner_id']][] = $image;
        }

        $relation_content = array_values(C('complaint.relation_content'));
        $relation_content_dict = array_column($relation_content, 'msg', 'code');
        $deal_result = array_values(C('complaint.result'));
        $deal_result_dict = array_column($deal_result, 'msg', 'code');
        $source = array_values(C('complaint.source'));
        $source_dict = array_column($source, 'msg', 'code');

        $result = array();
        foreach ($list as $k => $v) {
            $v['total_price'] /= 100;
            $v['ctype_name'] = isset($this->_ctype_dict[$v['ctype']]) ? $this->_ctype_dict[$v['ctype']] : '';
            $v['contents'] = isset($content_dict[$v['id']]) ? $content_dict[$v['id']] : [];
            $v['images'] = isset($image_dict[$v['id']]) ? $image_dict[$v['id']] : [];
            $v['line_name'] = isset($line_dict[$v['line_id']]) ? $line_dict[$v['line_id']] : '';
            $v['city_name'] = isset($city_dict[$v['city_id']]) ? $city_dict[$v['city_id']] : '';
            $v['invite_name'] = isset($user_dict[$v['invite_id']]) ? $user_dict[$v['invite_id']] : '';
            $v['sale_name'] = isset($user_dict[$v['sale_id']]) ? $user_dict[$v['sale_id']] : '';
            $v['logistics_name'] = isset($user_dict[$v['logistics_id']]) ? $user_dict[$v['logistics_id']] : '';
            $v['ctype_name'] = isset($this->_ctype_dict[$v['ctype']]) ? $this->_ctype_dict[$v['ctype']] : '';
            $v['site_name'] = $v['site_id'] == C('site.code.dachu.id') ? C('site.code.dachu.name') : C('site.code.daguo.name');
            $v['status_name'] = $v['status'] == C('complaint.status.processing.code') ? C('complaint.status.processing.msg') : C('complaint.status.finish.msg');
            $v['feedback_name'] = $v['feedback'] == C('complaint.feedback.customer.code') ? C('complaint.feedback.customer.msg') : C('complaint.feedback.sale.msg');
            $v['source_cn'] = isset($source_dict[$v['source']]) ? $source_dict[$v['source']] : '';
            $v['deal_result_cn'] = isset($deal_result_dict[$v['deal_result']]) ? $deal_result_dict[$v['deal_result']] : '';
            if ($v['deal_result'] > 1) {
                $v['deal_result_cn'] = $v['deal_result_cn'] . '(' . $v['result_param'] . ')';
            }
            $v['relation_content_cn'] = isset($relation_content_dict[$v['relation_content']]) ? $relation_content_dict[$v['relation_content']] : '';
            $v['deliver_date'] = date('Y-m-d', $v['deliver_date']);
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $v['updated_time'] = date('Y-m-d H:i:s', $v['updated_time']);
            $result[] = $v;
        }

        return $result;
    }
}

/* End of file complaint.php */
/* Location: :./application/controllers/complaint.php */
