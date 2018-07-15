<?php
/**
 * SESSION 记缓存
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   libraries
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: MY_Session.php 1 2014-12-25 14:48:51 pax $
 * @link      http://www.fruitday.com
 **/
class MY_Session extends CI_Session {

	public $sess_id = null;

	public function __construct($params=array())
	{
		if (isset($params['session_id'])) {
			$this->sess_id = $params['session_id'];

			unset($params['session_id']);
		}

		parent::__construct($params);
	}

	/**
	 * 写缓存
	 *
	 * @return void
	 * @author
	 **/
	public function _set_memcache()
	{
		$expire = ($this->sess_expire_on_close === TRUE) ? 0 : $this->sess_expiration;

 		$this->CI->load->library('memcached');
 		if(empty($this->userdata['user_data'])){
			$memcached_userdata = $this->userdata;
			$session_userdata = array();
			foreach (array('session_id','ip_address','user_agent','last_activity') as $val)
			{
				unset($memcached_userdata[$val]);
				$session_userdata[$val] = $this->userdata[$val];
			}
			$session_userdata['user_data'] = serialize($memcached_userdata);
		}else{
			$memcached_userdata = $this->userdata;
			$userdata_tmp = unserialize($memcached_userdata['user_data']);
			unset($memcached_userdata['user_data']);
			$session_userdata = array();
			foreach (array('session_id','ip_address','user_agent','last_activity') as $val)
			{
				unset($memcached_userdata[$val]);
				$session_userdata[$val] = $this->userdata[$val];
			}
			foreach ($memcached_userdata as $key => $value) {
				$userdata_tmp[$key] = $memcached_userdata[$key];
			}
			$session_userdata['user_data'] = serialize($userdata_tmp);

		}
		$this->CI->memcached->set($this->userdata['session_id'],serialize($session_userdata),$expire);
	}

	/**
	 * 删缓存
	 *
	 * @return void
	 * @author
	 **/
	public function _del_memcache()
	{
		$this->CI->load->library('memcached');

		$this->CI->memcached->delete($this->userdata['session_id']);
	}

	/**
	 * 得缓存
	 *
	 * @return void
	 * @author
	 **/
	public function _get_memcache($session_id)
	{
		$this->CI->load->library('memcached');

		$data = $this->CI->memcached->get($session_id);

		return unserialize($data);
	}

	/**
	 * 是否写缓存
	 *
	 * @return void
	 * @author
	 **/
	public function _is_memcache()
	{
		return (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && $this->sess_use_memcache == true) ? true : false;
	}

	/**
	 * 读SESSION
	 *
	 * @return void
	 * @author
	 **/
	public function sess_read()
	{
		if ($this->_is_memcache()) {

			if ($this->sess_id) {
				$session_id = $this->sess_id;
			} else {
				$session = $this->CI->input->cookie($this->sess_cookie_name);


				// No cookie?  Goodbye cruel world!...
				if ($session === FALSE)
				{
					log_message('debug', 'A session cookie was not found.');
					return FALSE;
				}

				// Decrypt the cookie data
				if ($this->sess_encrypt_cookie == TRUE)
				{
					$session = $this->CI->encrypt->decode($session);
				}
				else
				{
					// encryption was not used, so we need to check the md5 hash
					$hash	 = substr($session, strlen($session)-32); // get last 32 chars
					$session = substr($session, 0, strlen($session)-32);

					// Does the md5 hash match?  This is to prevent manipulation of session data in userspace
					if ($hash !==  md5($session.$this->encryption_key))
					{
						log_message('error', 'The session cookie data did not match what was expected. This could be a possible hacking attempt.');
						$this->sess_destroy();
						return FALSE;
					}
				}

				// Unserialize the session array
				$session = $this->_unserialize($session);

				// Is the session data we unserialized an array with the correct format?
				if ( ! is_array($session) OR ! isset($session['session_id']) OR ! isset($session['ip_address']) OR ! isset($session['user_agent']) OR ! isset($session['last_activity']))
				{
					$this->sess_destroy();
					return FALSE;
				}

				// Is the session current?
				if (($session['last_activity'] + $this->sess_expiration) < $this->now)
				{
					$this->sess_destroy();
					return FALSE;
				}

				// Does the IP Match?
				if ($this->sess_match_ip == TRUE AND $session['ip_address'] != $this->CI->input->ip_address())
				{
					$this->sess_destroy();
					return FALSE;
				}

				// Does the User Agent Match?
				if ($this->sess_match_useragent == TRUE AND trim($session['user_agent']) != trim(substr($this->CI->input->user_agent(), 0, 120)))
				{
					$this->sess_destroy();
					return FALSE;
				}

				$session_id = $session['session_id'];
			}

			$userdata = $this->_get_memcache($session_id);
			if (!$userdata){
				$this->CI->db->where('session_id', $this->sess_id);
				$query = $this->CI->db->get($this->sess_table_name);
				$userdata = $query->row_array();
				if(empty($userdata)){
					return FALSE;
				}
			}

			$this->userdata = $userdata;
			// $this->userdata['session_id'] = $this->sess_id;
			return TRUE;
		}elseif ($this->sess_use_database === TRUE) {
			if(empty($this->sess_id)){
				return FALSE;
			}
			$this->CI->db->where('session_id', $this->sess_id);
			$query = $this->CI->db->get($this->sess_table_name);
			$userdata = $query->row_array();
			if(empty($userdata)){
				return FALSE;
			}
			$this->userdata = $userdata;
        	return TRUE;
		}

		return parent::sess_read();
	}

	/**
	 * 写SESSION
	 *
	 * @return void
	 * @author
	 **/
	public function sess_write()
	{ 	if ($this->_is_memcache()) {

			$this->_set_memcache();

			// $cookie_userdata = array();

			// // Before continuing, we need to determine if there is any custom data to deal with.
			// // Let's determine this by removing the default indexes to see if there's anything left in the array
			// // and set the session data while we're at it
			// foreach (array('session_id','ip_address','user_agent','last_activity') as $val)
			// {
			// 	$cookie_userdata[$val] = $this->userdata[$val];
			// }

			// // 记COOKIE
			// $this->_set_cookie($cookie_userdata);

			// return TRUE;
		}
		return parent::sess_write();
	}

	/**
	 * 建SESSION
	 *
	 * @return void
	 * @author
	 **/
	public function sess_create()
	{

		$sessid = '';
		while (strlen($sessid) < 32)
		{
			$sessid .= mt_rand(0, mt_getrandmax());
		}

		$sessid .= $this->CI->input->ip_address();

		$this->userdata = array(
			'session_id'	=> $this->sess_id ? $this->sess_id : md5('nirvana'.uniqid($sessid, TRUE)),
			'ip_address'	=> $this->CI->input->ip_address(),
			'user_agent'	=> substr($this->CI->input->user_agent(), 0, 120),
			'last_activity'	=> $this->now,
			'user_data'		=> ''
		);

		if ($this->_is_memcache()) {
			$this->_set_memcache();
		}

		// Save the data to the DB if needed
		if ($this->sess_use_database === TRUE)
		{
			$this->CI->db->query($this->CI->db->insert_string($this->sess_table_name, $this->userdata));
		}

		// Write the cookie
		$this->_set_cookie();
	}

	/**
	 * 更新SESSION
	 *
	 * @return void
	 * @author
	 **/
	public function sess_update()
	{
		if ($this->_is_memcache()) {
			$this->userdata['last_activity'] = $this->now;

			$this->_set_memcache();
			// return TRUE;
		}
		return parent::sess_update();
	}

	/**
	 * 销毁SESSION
	 *
	 * @return void
	 * @author
	 **/
	public function sess_destroy()
	{
		if ($this->_is_memcache()) {

			$this->_del_memcache();

			// return TRUE;
		}
		return parent::sess_destroy();
	}

	/**
	 * 清理
	 *
	 * @return void
	 * @author
	 **/
	public function _sess_gc()
	{

	}

}
