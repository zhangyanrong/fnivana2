<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Memcached settings
| -------------------------------------------------------------------------
| Your Memcached servers can be specified below.
|
|	See: https://codeigniter.com/user_guide/libraries/caching.html#memcached
|
*/
// $config = array(
// 	'default' => array(
// 		'hostname' => '127.0.0.1',
// 		'port'     => '11211',
// 		'weight'   => '1',
// 	),
// );

$config = array(
		'nirvana2' => array(
				'hostname' => 'fdaymemcached.my2ymd.0001.cnn1.cache.amazonaws.com.cn',
				'port'     => '11211',
				'weight'   => '1',
		),
);