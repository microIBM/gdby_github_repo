<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * xhprof相关的函数
 * @author: caiyilong@ymt360.com
 * @version: 1.0.0
 * @since: 2015-04-21
 */

if( !function_exists('start_xhprof') ) {
    function start_xhprof() {
        try {
            $XHPROF_ROOT = "/data/www/xhprof";
            include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
            include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
            // start profiling
            xhprof_enable();
        } catch(Exception $e) {
            return FALSE;
        }
        return TRUE;
    }
}

if( !function_exists('end_xhprof') ) {
    function end_xhprof($domain = "dachuwang") {
        try {
            //end profiling
            $xhprof_data = xhprof_disable();
            $xhprof_runs = new XHProfRuns_Default();
            $run_id = $xhprof_runs->save_run($xhprof_data, $domain);
        } catch(Exception $e) {
            return FALSE;
        }
        return C("xhprof.url") . "/xhprof_html/index.php?run={$run_id}&source={$domain}";
    }
}
