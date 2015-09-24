<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

$config['memcached_thncr']['servers'] = array(
        'default' => array(
            'host'          => '127.0.0.1',
            'port'          => '11211',
            'weight'        => '1',
            )
        );

$config['memcached_thncr']['config'] = array(
        'engine'            => 'Memcache',// Acceptable values: Memcached or Memcache
        'expiration'        => 3600,       // Default content expiration value (in seconds)
        );

/* End of file memcached.php */
/* Location: ./system/application/config/memcached_thncr.php */
