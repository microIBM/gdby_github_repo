<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author caochunhui@ymt360.com
 * @description 格式化price
 */

if(!function_exists('format_kb')) {
    function format_kb($sizeb) {
        $sizekb = $sizeb / 1024;
        $sizemb = $sizekb / 1024;
        $sizegb = $sizemb / 1024;
        $sizetb = $sizegb / 1024;
        $sizepb = $sizetb / 1024;
        if ($sizeb > 1) {$size = round($sizeb,2) . "b";}
        if ($sizekb > 1) {$size = round($sizekb,2) . "kb";}
        if ($sizemb > 1) {$size = round($sizemb,2) . "mb";}
        if ($sizegb > 1) {$size = round($sizegb,2) . "gb";}
        if ($sizetb > 1) {$size = round($sizetb,2) . "tb";}
        if ($sizepb > 1) {$size = round($sizepb,2) . "pb";}
        return $size;
    }
}
