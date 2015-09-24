<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Demo extends MY_Controller {

    public function __construct () {
        parent::__construct();
        //构造函数调用model或者library
        $this->load->model(
            array('MUser')
        );
    }

    public function test() {
        //在MY_Controller里对流式json和普通json请求进行了合并，可以直接用$_POST取到所有的post数据
        $post = $_POST;
        $user_id = isset($post['user_id']) ? $post['user_id'] : 0;
        $where = array(
            'id' => $user_id,
            "in" => array(
                'status' => array(
                    0, 1
                )
            )
        );
        //get_one是MY_Model里的函数，可以实现选择字段的select
        //直接返回一个结果
        $user = $this->MUser->get_one(
            'id, name',
            $where
        );
        //get_list是MY_Model里的函数，同样可以实现指定字段的select
        //会返回一个list
        $user_list = $this->MUser->get_list(
            'id, name',
            array(
                'in' => array(
                    'status' => [0, 1]
                )
            )
        );

        //可以调用MY_Controller里的return_json
        $this->_return_json($user_list);
    }
}

/* End of file demo.php */
/* Location: ./application/controllers/demo.php */
