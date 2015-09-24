<?php
/*
 * jQuery File Upload Plugin PHP Class 7.1.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

//define('DENNIS_DEBUG', TRUE);

require_once('lib/class.image.inc');
class BinDataHandler
{

    protected $options;

    // PHP File Upload error message codes:
    // http://php.net/manual/en/features.file-upload.errors.php
    protected $error_messages = array(
        1 => '上传文件太大，超过服务器限制，请处理后再上传',
        2 => '上传文件太大，超过本页面限制，请处理后再上传',
        3 => '请注意：文件只上传了一部分',
        4 => '没有任何文件被上传，请检查',
        6 => '服务器没有临时文件夹，请联系我们解决这个问题',
        7 => '上传已成功，但保存失败。请联系我们解决这个问题',
        8 => '上传被终止',
        'post_max_size' => '上传文件太大，超过服务器限制，请处理后再上传',
        'max_file_size' => '上传文件太大，超过服务器限制，请处理后再上传',
        'min_file_size' => '上传文件太小，超过服务器限制，请处理后再上传',
        'accept_file_types' => '上传文件类型不允许',
        'max_number_of_files' => '超出上传文件数限制',
        'max_width' => '图片文件超出宽度限制',
        'min_width' => '图片文件低于最低宽度限制',
        'max_height' => '图片文件超出高度限制',
        'min_height' => '图片文件低于最低高度限制',
        'abort' => '图片上传异常',
        'image_resize' => '图片放缩失败'
    );

    protected $image_objects = array();

    function __construct($options = null, $initialize = true, $error_messages = null, $bucket = 'misc' ) {
        $this->options = array(
            'bucket' => $bucket,
            'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/files/'.$bucket.'/',
            'user_dirs' => false,
            'mkdir_mode' => 0755,
            'param_name' => 'files',
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type' => 'DELETE',
            'access_control_allow_origin' => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods' => array(
                'OPTIONS',
                'HEAD',
                'GET',
                'POST',
                'PUT',
                'PATCH',
                'DELETE'
            ),
            'access_control_allow_headers' => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition'
            ),
            // Enable to provide file downloads via GET requests to the PHP script:
            //     1. Set to 1 to download files via readfile method through PHP
            //     2. Set to 2 to send a X-Sendfile header for lighttpd/Apache
            //     3. Set to 3 to send a X-Accel-Redirect header for nginx
            // If set to 2 or 3, adjust the upload_url option to the base path of
            // the redirect parameter, e.g. '/files/'.
            'download_via_php' => false,
            // Read files in chunks to avoid memory limits when download_via_php
            // is enabled, set to 0 to disable chunked reading of files:
            'readfile_chunk_size' => 10 * 1024 * 1024, // 10 MiB
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types' => '/.+$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Defines which files are handled as image files:
            'image_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Image resolution restrictions:
            'max_width' => null,
            'max_height' => null,
            'min_width' => 1,
            'min_height' => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => true,
            // Set to 0 to use the GD library to scale and orient images,
            // set to 1 to use imagick (if installed, falls back to GD),
            // set to 2 to use the ImageMagick convert binary directly:
            'image_library' => 1,
            // Uncomment the following to define an array of resource limits
            // for imagick:
            /*
            'imagick_resource_limits' => array(
                imagick::RESOURCETYPE_MAP => 32,
                imagick::RESOURCETYPE_MEMORY => 32
            ),
            */
            // Command or path for to the ImageMagick convert binary:
            'convert_bin' => 'convert',
            // Uncomment the following to add parameters in front of each
            // ImageMagick convert call (the limit constraints seem only
            // to have an effect if put in front):
            /*
            'convert_params' => '-limit memory 32MiB -limit map 32MiB',
            */
            // Command or path for to the ImageMagick identify binary:
            'identify_bin' => 'identify',
            'image_versions' => array(
                // The empty image version key defines options for the original image:
                '' => array(
                    // Automatically rotate images based on EXIF meta data:
                    'auto_orient' => true
                ),
                // Uncomment the following to create medium sized images:
                /*
                'medium' => array(
                    'max_width' => 800,
                    'max_height' => 600
                ),
                */
                'thumbnail' => array(
                    // Uncomment the following to use a defined directory for the thumbnails
                    // instead of a subdirectory based on the version identifier.
                    // Make sure that this directory doesn't allow execution of files if you
                    // don't pose any restrictions on the type of uploaded files, e.g. by
                    // copying the .htaccess file from the files directory for Apache:
                    //'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
                    //'upload_url' => $this->get_full_url().'/thumb/',
                    // Uncomment the following to force the max
                    // dimensions and e.g. create square thumbnails:
                    //'crop' => true,
                    'max_width' => 80,
                    'max_height' => 80
                )
            )
        );
        if ($options) {
            $this->options = $options + $this->options;
        }
        if ($error_messages) {
            $this->error_messages = $error_messages + $this->error_messages;
        }
        if ($initialize) {
            $this->initialize($bucket);
        }
    }

    protected function initialize($bucket) {
        switch ($this->get_server_var('REQUEST_METHOD')) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get();
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post(true,$bucket);
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                $this->header('HTTP/1.1 405 Method Not Allowed');
        }
    }


    protected function get_distribute_path($filemd5) {
        $ext = ".jpg";
        $relative_path_dir = substr($filemd5, 0, 2) . '/'. substr($filemd5, 2, 2) . '/';
        $relative_path = $relative_path_dir . $filemd5 . $ext;
        $file_path = $this->options['upload_dir'] . $relative_path;
        return $file_path;
    }

    protected function get_error_message($error) {
        return array_key_exists($error, $this->error_messages) ?
            $this->error_messages[$error] : $error;
    }


    protected function get_server_var($id) {
        return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
    }



    protected function _return($data){
        echo json_encode($data);
        die();
    }


    public function post($print_response = true,$bucket) {

        $res = array("status"=> -1,"errmsg"=>"");
        $img_data = file_get_contents("php://input");
        if(!$img_data){
            $res["errmsg"] = "parse img data error";
            $this->_return($res);
        }

        $filemd5 = md5($img_data);

        $new_fullname = $this->get_distribute_path($filemd5);

        $bytes = file_put_contents($new_fullname,$img_data);
        if($bytes === FALSE || (int)$bytes <= 0){
            if(!AdImage::retrial($new_fullname)) {
                // 上传文件过一次GD库 by FuXu 20140830
                $res["errmsg"] = "save img data error";
                $this->_return($res);
            }
        }

        $res["status"] = 0;
        $res["picture"] = $bucket."/" . $filemd5.".jpg";
        $this->_return($res);

    }

}
