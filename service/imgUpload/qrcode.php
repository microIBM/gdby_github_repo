<?php


require ("phpqrcode/qrlib.php");
require ("ymtmcrypt.php");
require ("cfg.php");

$tool = new  YmtMcrypt();
if(isset($_GET['s'])){
    /*
        $tool->encrypt("http://www.ymt360.com" . chr(0) . "100");
        d6HDjbnL6AZKRyTVLfmy22JWhg9irf785FfV-nRJffMc5ZtyXOSjqP0Kphzk5vPs
    */
    $s = $tool->decrypt($_GET["s"]);
    list($data, $size) = explode(chr(0),$s);

    if(empty($size)){
        $size = $_C['QRCODE_SIZE'];
    }
    
    if( ! empty($data)){
        //png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
        /* $data 数据
         * $filename 保存的图片名称
         * $errorCorrectionLevel 错误处理级别
         * $matrixPointSize 每个黑点的像素
         * $margin 图片外围的白色边框像素
         */
        ob_start();
        QRcode::png($data, FALSE, QR_ECLEVEL_L, $size/25, $_C['QRCODE_MARGIN']);
        $bin_qrcode = ob_get_contents();
        ob_end_clean();

        $img = new Imagick();
        $img->readImageBlob($bin_qrcode);
        $img->resizeImage($size, $size, Imagick::FILTER_LANCZOS, TRUE);
        echo $img;
        $img->destroy();
    }else{
        header('HTTP/1.1 404 Not Found');
        header("status: 404 Not Found");
    }
}else{
    header('HTTP/1.1 404 Not Found');
    header("status: 404 Not Found");
}
