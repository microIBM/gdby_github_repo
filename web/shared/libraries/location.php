<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 地理位置查询
 * @modified by caiyilong@ymt360.com
 * @since 2014-10-08
 */
class Location {



    // 在配置里的是直辖市、特别行政区，在省以下就提示批发市场
    // 其他没在配置里的，只有到了市以下才提示批发市场
    static $special_location_array = array(
        1,  // 北京
        2,  // 天津
        3,  // 上海
        4,  // 重庆
        32, // 香港
        33, // 澳门
    );

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model(
            array(
                'MLocation'
            )
        );
    }

    /**
     * 查询省信息
     */
    public function index() {
        $res = $this->CI->MLocation->get_lists('*', array('upid' => 0, 'status' => 1));
        return $res;
    }

    /**
     * 查询省信息
     */
    public function get_name_by_id($id = 0) {
        $res = $this->CI->MLocation->get_one('*', array('id' => $id));
        return $res;
    }
    public function children($upid){
        $res = $this->CI->MLocation->get_lists('*',
            array(
                'in' => array('upid' => $upid),
                'status' => 1
            )
        );
        return $res;
    }

    /**
     *计算某个经纬度的周围某段距离的正方形的四个点
     *@param lng float 经度
     *@param lat float 纬度
     *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
     *@return array 正方形的四个点的经纬度坐标
     */
    function get_square_points($lng, $lat, $distance = 0.5){
        //地球半径，平均半径为6371km
        //define("earth_radius", 6371);
        $earth_radius = 6371;
        $dlng =  2 * asin(sin($distance / (2 * $earth_radius)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);

        $dlat = $distance / $earth_radius;
        $dlat = rad2deg($dlat);

        return array(
            'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
            'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
            'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
            'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
        );
    }
}
/* End of file location.php */
/* Location: ./libraries/location.php */
