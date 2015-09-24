<?php 
ini_set('date.timezone', 'Asia/Shanghai');

//define('PAY_ENV', 'test');//开发测试环境
define('PAY_ENV', 'production');//线上生产环境

if (PAY_ENV == 'test') {
    error_reporting(E_ERROR);
}else{
    error_reporting(0);
}
