<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MLocation extends MY_Model {
    use MemAuto;

    private $table = "t_location";

    public function __construct(){
        parent::__construct($this->table);
    }

    /**
     * get_full_ids
     * @return 以所有的省份记录(object)为元素的数组
     * @author unknown
     * @commentd by Dennis( yuantaotao@gmail.com )
     **/
    function list_province() {
        $this->db->where(array('upid' => 0, 'status' => 1));
        $res = $this->db->get($this->table)->result();
        return $res;
    }

	// 获得自己和祖先
	// by zhoumi
	function get_ancestors($id) {
		// 获得 path
		$info = $this->get($id);
		$path = $info->path;
		$arr_path = explode(".", $path);

		// 从 path 获得 ancestors
		$res = [];
		foreach ($arr_path as $location_id) {
			$linfo = $this->get($location_id);
			if ($linfo) {
				$res[$location_id] = $linfo->name;
			}
		}
		return $res;
	}


    //获得下级地址
    function get_sons($upid) {
        $res = $this->get_lists("*", array('in' => array('upid' => $upid), 'status' => 1));
        if(!empty($res)) {
            return $res;
        }
        return array();
    }

    function get_by_name($name, $level) {
        $query = $this->db->get_where($this->table, array('name'=>$name, 'level' => $level));
        $res = $query->result();
        if(!empty($res)) {
            return $res[0];
        }
        return FALSE;
    }

    function get($id) {
        if($id == 0) {
            return FALSE;
        }
        $query = $this->db->get_where($this->table, array('id'=>$id));
        $res = $query->first_row();
        return $res;
    }

    //获取id数组  查询结果拼成串
    function get_arr($ids_arr, $short = TRUE) {
        $ret = "";
        foreach($ids_arr as $id) {
            if(! $id) {
                break;
            }
            $res = $this->get($id);
            if($short) {
                $ret .= $res->name;
            } else {
                $ret .= $res->full_name;
            }
        }
        return $ret;
    }

    //根据地址id获得省id
    function get_province($position) {
        if(! $position) {
            return FALSE;
        }

        $level = 0;
        while($level != 1) {
            $info = $this->get($position);
            if(! $info) {
                return FALSE;
            }
            $pid = $position;
            $position = $info->upid;
            $level = $info->level;
        }
        return $pid;
    }

    //根据地址id获得该地址以下的所有地址
    function get_all_sons($position) {
        $ret = array($position);
        $tmp = array($position);

        while($p = array_pop($tmp)) {
            $sons = $this->get_sons($p);
            if(! $sons) {
                continue;
            }
            foreach($sons as $son) {
                array_push($tmp, $son['id']);
                array_push($ret, $son['id']);
            }
        }

        return $ret;
    }

    /**
     * get sublocation of upids
     *
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    function get_by_upids($upids)
    {
        $this->db->where_in('upid', $upids);
        return $this->db->get($this->table)->result_array();
    }
    /**
     * get all the sub location of location_id recurisively
     *
     * @return array
     * @author Dennis( yuantaotao@gmail.com )
     **/
    function get_all_sub_location($location_id)
    {

        $ret = $location_ids = array($location_id);

        $this->db->where('id', $location_id);
        $location_info = $this->db->get($this->table)->result_array();
        if (
                count($location_info) == 0
                || $location_info[0]['level'] > 4
                || $location_info[0]['level'] < 1
           ) {
            return $ret;
        }

        $collumn_array = array('province_id', 'city_id', 'county_id', 'town_id', 'street_id');
        $collumn = $collumn_array[$location_info[0]['level'] - 1];
        $this->db->select('id');
        $this->db->where($collumn, $location_info[0][$collumn]);
        $l_data = $this->db->get($this->table)->result_array();

        foreach ($l_data as $one) {
            $ret[] = $one['id'];
        }

        array_unique($ret);


        /*
        $ret = $location_ids = array($location_id);
        $while_count = 0;

        while ($while_count < 10 && $l_data = $this->get_by_upids($location_ids)) {
            $location_ids = array();
            foreach ($l_data as $one) {
                $location_ids[] = $one['id'];
                $ret[] = $one['id'];
            }
            $while_count++;
        }
        */

        return $ret;
    }

    function search_by_name($name, $level) {
        $this->db->like('name', $name)->where('level', $level)->where('status', 1);
        $query = $this->db->get($this->table);
        $res = $query->result();
        if(!empty($res)) {
            return $res[0];
        }
        return FALSE;
    }

    /**
     * get_full_position
     * @return 对应于 location_id 的 从省到街道的地址字符串
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function get_full_position($id, $field = 'name') {
        $location = $this->get($id);
        $res = '';
        if($location) {
            $path = explode('.', trim($location->path));
            $this->db->where_in('id', $path);
            $tmp = $this->db->get($this->table)->result();
            $arr = array();
            foreach($tmp as $item) {
                $arr[$item->id] = $item->$field;
            }
            foreach($path as $id) {
                $res .= $arr[$id];
            }
        }
        return $res;
    }

    function get_list_position($arr) {
        $ret = "";
        foreach($arr as $a) {
            if ($a && ($location = $this->get($a))) {
                $location && ($ret .= $location->name);
            }
        }
        return $ret;
    }

    /**
     * get_full_ids
     * @return 包含 省/市/县/镇/村 共5级 ID 的 数组
     * @author unknown
     * @commentd by Dennis( yuantaotao@gmail.com )
     **/
    function get_full_ids($id) {
        $ret = array();
        $level = 0;
        while($level != 1) {
            $info = $this->get($id);
            if(! $info) {
                return $ret;
            }
            $id = $info->upid;
            $ret[] = $info->id;
            $level = $info->level;
        }
        return array_reverse($ret);
    }

    function get_geo_by_id($id){
        $this->db->select('latitude,longtitude,geohash');
        $this->db->where(array('id' => $id));
        $this->db->from($this->table);

        $query = $this->db->get();
        return $query->row();
    }
    
    function get_geo_arr_by_id($id){
        $this->db->select('latitude,longtitude,geohash');
        $this->db->where(array('id' => $id));
        $this->db->from($this->table);

        $query = $this->db->get();
        return $query->row_array();
    }

    function decode_geo($lat,$lng){
        $this->load->library('geohash');
        $geohash = $this->geohash->encode($lat,$lng);

        $this->db->like('geohash',substr($geohash,0,5),'after');
        $this->db->from($this->table);

        $query = $this->db->get();
        return $query->result();
    }

    function get_county_by_province($province_id) {
        $path = $province_id . '.';
        $this->db->like('path', $path ,'after');
        $this->db->where('level', 3);
        return $this->db->get($this->table)->result_array();
    }

    function get_location_list($ids = array()) {
        if(empty($ids)){
            return FALSE;
        }
        $this->db->where_in('id', $ids);
        return $this->db->get($this->table)->result_array();
    }

    function get_location_map($ids = array()) {
        if(empty($ids)) {
            return;
        }
        $province_list = $this->get_location_list($ids);
        $province_map  = array();
        foreach($province_list as $item) {
            $province_map[$item['id']] = $item['name'];
        }
        return $province_map;
    }

    /**
     * get_one_three_position
     * @return 对应于 location_id  仅取省和街道的地址字符串
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function get_one_three_position($id, $field = 'name') {
        $location = $this->get($id);
        $res = '';
        if($location) {
            $path = explode('.', trim($location->path));
            $this->db->where_in('id', $path);
            $tmp = $this->db->get($this->table)->result();
            $arr = array();
            foreach($tmp as $item) {
                $arr[$item->id] = $item->$field;
            }
            $count_path = count($path);
            if($count_path == 3) {
                foreach($path as $k=>$id) {
                    if($k == 2) {
                        continue;
                    }
                    $res .= $arr[$id];
                }
            } else {
                foreach($path as $id) {
                    $res .= $arr[$id];
                }
            }
        }
        return $res;
    }
}
