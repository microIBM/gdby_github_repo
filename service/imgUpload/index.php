<?php
/**
 * The image store of ymt360
 *      Based jQuery File Upload Plugin
 *      https://github.com/blueimp/jQuery-File-Upload
 *
 * @author Dennis( yuantaotao@gmail.com )
 * @version 3
 * @copyright Dennis( yuantaotao@gmail.com ),
 * @package ymt360
 **/


/*{
 *---------------------------------------------------------------
 * the log trace
 *---------------------------------------------------------------
 */
//todo: add log trace with sentry
/*end of the log trace }*/


error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');
require('BinDataHandler.php');
require('bucket.php');


/*{
 *---------------------------------------------------------------
 * set the bucket for images
 *---------------------------------------------------------------
 */

if (
        !isset($_GET['bucket'])
        || !in_array($_GET['bucket'], $buckets)
   ) {
    $bucket = 'misc';
} else {
    $bucket = $_GET['bucket'];
}
/*end of set the bucket for images }*/

$options = array();
switch ($bucket) {
    case 'sells':
        $options = array(
                'access_control_allow_origin' => '*',
                'access_control_allow_methods' => array(
                    'OPTIONS',
                    'HEAD',
                    'GET',
                    'POST',
                    'PUT',
                    ),
                );
        break;
    default:
        break;
}
if(isset($_GET['type'])&& 2==$_GET['type']){
    $upload_handler = new BinDataHandler($options, TRUE, NULL, $bucket);
}else{
    $upload_handler = new UploadHandler($options, TRUE, NULL, $bucket);
}
