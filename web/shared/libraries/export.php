<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Export {

    public function __construct () {
    }

    //导出csv文件
    public function csv($input = array()) {
        if(empty($input) || empty($input['data']) || empty($input['file_path'])) {
            echo '输入数据和csv文件名不能为空';
            return;
        }

        $file_path = $input['file_path'];
        $data = $input['data'];

        $file = fopen($file_path, 'w');
        foreach($data as $item) {
            fputcsv($file, $item);
        }
        fclose($file);
        $str = file_get_contents($file_path);
        $str = iconv('UTF-8', 'GBK', $str);
        unlink($file_path);
        file_put_contents($file_path, $str);

    }
}

/* End of file export.php */
/* Location: ./application/controllers/export.php */
