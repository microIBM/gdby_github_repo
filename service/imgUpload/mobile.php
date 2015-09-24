<?php


require ("ymtmcrypt.php");
require ("cfg.php");

$tool = new  YmtMcrypt();
if(isset($_GET['phone'])){

    $data = $_GET["phone"];
    $phone = $tool->decrypt($_GET["phone"]);
    
    if( ! empty($phone)){

        $target_url = sprintf("%s?mobile=%s&height=%s&color=%s",
            $_C['PHONE_BASE64_SERVER'],
            $phone,
            $_C['PHONE_BASE64_HEIGHT_1'],
            $_C['PHONE_BASE64_COLOR']
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,$_C['TIMEOUT']);
        $result =false;
        while(($result === false) && (--$_C['RETRY']> 0)){
            $result=curl_exec($ch);
        }
 
        curl_close ($ch);
        if(false == $result){
            header('HTTP/1.1 404 Not Found');
            header("status: 404 Not Found");
        }
        $raw_data = base64_decode($result);
        header('Content-type: image/png');
        echo $raw_data;
    }else{
        header('HTTP/1.1 404 Not Found');
        header("status: 404 Not Found");
    }
}else{
    header('HTTP/1.1 404 Not Found');
    header("status: 404 Not Found");
}
