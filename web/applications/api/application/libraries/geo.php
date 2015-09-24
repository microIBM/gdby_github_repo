<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * @version 1.0.0
 */
class Geo {
    public function geohash($lat, $lng) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, C('geo.geohash.url').'?lat='.$lat.'&lng='.$lng);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        $json = json_decode($res, TRUE);
        $geo_hash = '';
        if($json['Status'] == 0) {
            if(isset($json['Req']['Geohash'])) {
                $geo_hash = $json['Req']['Geohash'];
            }
        }
        return $geo_hash;
    }
}
