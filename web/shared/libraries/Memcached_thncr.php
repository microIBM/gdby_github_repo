<?php
/**
 * The Memcache Library For Thncr
 *
 * @author Dennis( yuantaotao@gmail.com )
 * @version 3
 * @copyright Dennis( yuantaotao@gmail.com ), 
 * @package ymt360
 **/

/**
 * Class Memcached_thncr 
 * @author Dennis( yuantaotao@gmail.com )
 */
class Memcached_thncr
{
    private $config;
    private $m;
    private $client_type;
    private $CI;
    private $mem_db;
    protected $errors = array();

    public function __construct()
    {
        $this->CI =& get_instance();

        // Load the memcached library config
        $this->CI->load->config('memcached_thncr');
        $this->config = $this->CI->config->item('memcached_thncr');

        // Lets try to load Memcache or Memcached Class
        $this->client_type = class_exists($this->config['config']['engine']) ? $this->config['config']['engine'] : FALSE;
        switch($this->client_type)
        {
            case 'Memcached':
                $this->m = new Memcached();
                break;
            case 'Memcache':
                $this->m = new Memcache();
                break;
        }

        $this->auto_connect();
    }

    /**
     * auto_connect
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    private function auto_connect()
    {
        foreach($this->config['servers'] as $key=>$server)
        {
            $this->add_server($server);
        }
    }

    /**
     * add_server
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function add_server($server)
    {
        extract($server);
        return $this->m->addServer($host, $port, NULL, $weight);
    }

    public function set($key = NULL, $value = NULL, $expiration = NULL)
    {
        $expiration = $expiration ? : $this->config['config']['expiration'];

        switch($this->client_type)
        {
            case 'Memcache':
                $add_status = $this->m->set($key, $value, MEMCACHE_COMPRESSED, $expiration);
                break;

            default:
            case 'Memcached':
                $add_status = $this->m->set($key, $value, $expiration);
                break;
        }

        return $add_status;
    }

    public function get($key = NULL)
    {
        if($this->m)
        {
            if(is_null($key))
            {
                return FALSE;
            }

            return $this->m->get($key);
        }
        return FALSE;
    }


    public function delete($key, $expiration = NULL)
    {
        if(is_null($key))
        {
            $this->errors[] = 'The key value cannot be NULL';
            return FALSE;
        }

        if(is_null($expiration))
        {
            $expiration = 0;
        }

        return $this->m->delete($key, $expiration);
    }

    public function increment($key = null, $by = 1)
    {
        return $this->m->increment($key, $by);
    }


    public function decrement($key = null, $by = 1)
    {
        return $this->m->decrement($key, $by);
    }
}

?>
