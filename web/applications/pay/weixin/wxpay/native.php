<?php
die('forbideen to access');
require_once "../../logics/header.php";
require_once "lib/WxPay.Api.php";
require_once "WxPay.NativePay.php";
require_once '../../logics/order.php';
require_once '../../logics/lib/log.php';
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" /> 
    <title>微信安全扫码支付</title>
</head>
<body>
    <div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">扫描支付模式一</div><br/>
    <img alt="模式一扫码支付" src="<?php echo Config::BASE_PAY_URL?>/qrcode.php?order_id=55263"/>
    <br/><br/><br/>
    <div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">扫描支付模式二</div><br/>
    <img alt="模式二扫码支付" src="<?php echo Config::BASE_PAY_URL?>/qrcode.php?order_id=55263" />
</body>
</html>