<?php
class Strategy_model extends CI_model {

	var $key = 'api:strategy:';
	var $channel; 	// 渠道
	var $platform; 	// 平台
	var $province;	// 省市
	var $member; 	// 会员等级

	var $channel_arr = [
		'1'=>'pc',
		'2'=>'app',
		'3'=>'wap',
	];

    function __construct() {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

	// 获取一个策略
	function get($id) {

		if(!$id) {
			return false;
		}

		$id                  = str_replace($this->key, '', $id);
		$result              = $this->redis->hGetAll($this->key.$id);
		$result['product']   = json_decode($result['product']);
		$result['condition'] = json_decode($result['condition']);
		$result['solution']  = json_decode($result['solution']);
		if($result['product']->black){
			if(!trim($result['product']->black[0])){
				unset($result['product']->black);
			}
		}
		if($result['product']->white){
			if(!trim($result['product']->white[0])){
				unset($result['product']->white);
			}
		}
		return $result;
	}

	// 获取所有策略
	function getAll($channel, $platform, $province, $member) {

		if(!$this->redis)
			return false;

		if (is_numeric($channel))
			$this->channel  = $this->channel_arr[$channel];
		else
			$this->channel = $channel;

		$this->platform = $platform;
		$this->province = $province;
		$this->member   = $member;

		$strategies = [];
		// $keys = $this->redis->sMembers($this->key.'index');
		$keys = $this->redis->sInter(
			$this->key.$platform, 
			$this->key.'active',
			$this->key.$channel
		);
		$not_end = $this->redis->zRangeByScore( $this->key.'end', time(), "+inf");
		$keys    = array_intersect($keys, $not_end);

		foreach ($keys as $key) {
			if ($this->validate($key) === false)
				continue;
			$strategy = $this->get($key);

			array_push($strategies, $strategy);
		}

		return $strategies;
	}

	// 验证策略是不是有效
	function validate($key) {

		$key = str_replace($this->key, '', $key);
			
		// $end = $this->redis->hGet($this->key.$key, 'end');
		// if (time() > $end)
		// 	return false;

		// $active = $this->redis->hGet($this->key.$key, 'active');
		// if ($active == 'false')
		// 	return false;

		$start = $this->redis->hGet($this->key.$key, 'start');
		if (time() < $start)
			return false;

		// $platform_list = $this->redis->hGet($this->key.$key, 'platform');
		// $platform_list = json_decode($platform_list);
		// if( !in_array($this->platform, $platform_list) )
		// 	return false;		

		// $channel_list = $this->redis->hGet($this->key.$key, 'channel');
		// $channel_list = json_decode($channel_list);
		// if( !in_array($this->channel, $channel_list) )
		// 	return false;		

		$province = $this->redis->hGet($this->key.$key, 'province');
		$province = json_decode($province);
		if( $province && $province[0] && !in_array($this->province, $province) )
			return false;


		$member = $this->redis->hGet($this->key.$key, 'member');
		$member = json_decode($member);
		if( $member && $member[0] != " " && !in_array($this->member['user_rank'], $member) )
			return false;

		return true;
	}
}
