<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Excel_export {

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library(
            array(
                'PHPExcel',
            )
        );
    }

    /**
     * 导出Excel
     * @author yugang@dachuwang.com
     * @since 2015-05-12
     */
    public function export($xls_data, $title_arr, $name = 'default.xlsx') {
        ini_set("memory_limit", "1024M");

        $path = '/tmp/export/temp.xlsx';
        $this->_convert_array_to_excel($xls_data, $title_arr, $path);

        $data = file_get_contents($path); // 读文件内容
        $this->CI->load->helper('download');

        force_download($name, $data);
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 用数组和地址直接生成excel文件
     * 每一个数组占一个sheet
     */
    private function _convert_array_to_excel($arr = array(), $sheet_titles = array(), $out_name = '', $barcode_arr = array()) {

        //下面的代码是抄的。
        //set cache
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        //open excel file
        $write_objPHPExcel = new PHPExcel();
        $write_objPHPExcel->getDefaultStyle()->getFont()
            ->setName('simsun')
            ->setSize(10);

        //下面要循环了

        $sheet_cnt = 0;
        foreach($arr as $item) {
            //用订单id.csv来命名每一个sheet
            $out_sheet = new PHPExcel_Worksheet($write_objPHPExcel, $sheet_titles[$sheet_cnt]);
            //$out_sheet->setTitle($item);

            //row index start from 1
            $row_index = 0;
            foreach ($item as $row) {
                $row_index++;
                //$cellIterator = $row->getCellIterator();
                //$cellIterator->setIterateOnlyExistingCells(false);

                //column index start from 0
                $column_index = -1;
                foreach ($row as $cell) {
                    $column_index++;
                    //var_dump($cell);
                    $out_sheet->setCellValueByColumnAndRow($column_index, $row_index, $cell, PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }
            //如果条码数组不为空，那么说明需要在sheet里插入条码
            if(!empty($barcode_arr) && isset($barcode_arr[$sheet_cnt])) {
                $barcode_download_res = $this->_download_barcode($barcode_arr[$sheet_cnt]);
                if($barcode_download_res['code'] == 200) {
                    //no pic you say a jb
                    $pic_path = $barcode_download_res['file'];
                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                    $objDrawing->setName('barcode');
                    $objDrawing->setDescription('');
                    $objDrawing->setPath($pic_path);
                    $objDrawing->setHeight(50);
                    $objDrawing->setCoordinates('D26');
                    //$objDrawing->setOffsetX(10);
                    //$objDrawing->getShadow()->setVisible(true);
                    //$objDrawing->getShadow()->setDirection(36);
                    $objDrawing->setWorksheet($out_sheet);
                    //no pic you say a jb
                }
            }

            $write_objPHPExcel->addSheet($out_sheet);
            $sheet_cnt++;
        }
        $write_objPHPExcel->removeSheetByIndex(0);
        //删除第一个空sheet
        //上面要循环了
        //上面的代码是抄的

        //write excel file
        $objWriter = new PHPExcel_Writer_Excel2007($write_objPHPExcel);

        $dir_name = dirname($out_name);
        if(!is_dir($dir_name)) {
            $res = mkdir($dir_name, 0777, TRUE);
        }
        $objWriter->save($out_name);
    }
}

/* End of file  cate_logic.php*/
/* Location: :./application/libraries/cate_logic.php/ */
