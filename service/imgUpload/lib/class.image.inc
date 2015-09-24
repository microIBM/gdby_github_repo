<?php
/**
 * Image Util
 * @package adsmart.dangdang.com
 * @author  FuXu <fuxu@dangdang.com>¡¡
 * @version 0.1
 * @since¡¡ 2010-02-05
 */
class AdImage {
    /**
     * AdImage::retrial
     * @param string file
     * @return boolean
     */
    public static function retrial($file) {
        $im = new Imagick();
        try{
            $im->readImage($file);
            if($im->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
                $im->clear();
                $im->destroy();
                return FALSE;
            }
            $im->writeImages($file, TRUE);
            $im->clear();
            $im->destroy();
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }
    
}
