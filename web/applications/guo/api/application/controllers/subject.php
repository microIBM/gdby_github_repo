<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 专题信息
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Subject extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function info() {
        $cur = $this->userauth->current(TRUE);
        if(!empty($_POST['id'])) {
            $data = $this->format_query('/subject/info', array('id' => $_POST['id'], 'cur' => $cur));
            $this->_return_json($data);
        }
    }
}

/* End of file subject.php */
/* Location: ./application/controllers/subject.php */
