<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 生成bootstrap风格前端分页代码以及手动分页得到的对应数据
 * $base_url格式：/controller/method
 * @author zhangxiao@dachuwang.com
 */
if ( ! function_exists('pagination')) {

    function pagination($base_url = '', $url_params = array(), $total_rows = 0, $offset = 0, $num_links, $data=array(), $page=1) {
        $CI =& get_instance();
        $CI->load->library('pagination');
        $pagination['links'] = '';
        $pagination['data'] = array();

        //拼接带参数的url
        $base_url = trim($base_url);
        $base_url = $base_url.'?';
        foreach ($url_params as $key => $value){
            $base_url = sprintf($base_url.'%s=%s&', $key, $value);
        }
        $base_url = substr($base_url, 0, strlen($base_url)-1);

        $config['base_url'] = $base_url;
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $offset;
        $config['num_links'] = $num_links;
        $config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['first_link'] = "首页";
        $config['last_link'] = "末页";
        $config['full_tag_open'] = '<ul class="pagination">';
        $config['full_tag_close'] = '</ul>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a>';
        $config['cur_tag_close'] = '</li></a>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $CI->pagination->initialize($config);
        $links = $CI->pagination->create_links();

        $pagination_html = '<div class="control-pages clearfix"><nav class="pagination">'.$links.'</nav>';
        if ($total_rows >= 10) {
            $pagination_html .= '<div class="btn-group dropup page-size"><button type="button" class="btn btn-default">';
            if($offset == 10){
                $pagination_html .= '每页10条';
            }elseif($offset == 15 && $total_rows >=15){
                $pagination_html .= '每页15条';
            }elseif($offset == 20 && $total_rows >=20){
                $pagination_html .= '每页20条';
            }elseif($offset == 30 && $total_rows >=30){
                $pagination_html .= '每页30条';
            }elseif($offset == 50 && $total_rows >=50){
                $pagination_html .= '每页50条';
            }else{
                $pagination_html .= '选择每页条目数';
            }
            $pagination_html .= '</button>
                  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <ul class="dropdown-menu" role="menu">';

            $pattern = '/offset=(\d*)/';
            if(preg_match($pattern, $base_url)) {

                $base_url_offset10 = preg_replace($pattern, "offset=10", $base_url);
                $base_url_offset15 = preg_replace($pattern, "offset=15", $base_url);
                $base_url_offset20 = preg_replace($pattern, "offset=20", $base_url);
                $base_url_offset30 = preg_replace($pattern, "offset=30", $base_url);
                $base_url_offset50 = preg_replace($pattern, "offset=50", $base_url);
            } else {
                $base_url_offset10 = $base_url.'&offset=10';
                $base_url_offset15 = $base_url.'&offset=15';
                $base_url_offset20 = $base_url.'&offset=20';
                $base_url_offset30 = $base_url.'&offset=30';
                $base_url_offset50 = $base_url.'&offset=50';
            }
            $pagination_html .= '<li><a href="'.$base_url_offset10.'">每页10条</a></li>';
            if($total_rows > 10){
                $pagination_html .= '<li><a href="'.$base_url_offset15.'">每页15条</a></li>';
            }
            if($total_rows > 15){
                $pagination_html .= '<li><a href="'.$base_url_offset20.'">每页20条</a></li>';
            }
            if($total_rows > 20){
                $pagination_html .= '<li><a href="'.$base_url_offset30.'">每页30条</a></li>';
            }
            if($total_rows > 30){
                $pagination_html .= '<li><a href="'.$base_url_offset50.'">每页50条</a></li>';
            }
            $pagination_html .= '</ul></div>';
        }
        $pagination_html .= '<div class="label label-info total-records">共 '.$total_rows.' 条记录</div></div>';

        $pagesize_start = ($page - 1) * $offset;
        $pagesize_end = 0;
        if($total_rows > $pagesize_start + $offset) {
            $pagesize_end = $pagesize_start + $offset;
        } else {
            $pagesize_end = $total_rows;
        }

        $data_after_pagination = array();
        if(count($data) >= $pagesize_end) {
            for($i = $pagesize_start; $i < $pagesize_end; $i++) {
                array_push($data_after_pagination, $data[$i]);
            }
        }

        $pagination['links'] =  $pagination_html;
        $pagination['data'] = $data_after_pagination;

        return $pagination;
    }
}
