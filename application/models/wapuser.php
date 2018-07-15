<?php
class Wapuser extends CI_Model {
	var $session_expiretime = 1209600;
	function Wapuser() {
		parent::__construct();
		$this->load->library("session");
	}

	public function App_oAuthSignin($params){
    	if(abs(time()-$params['time'])>20*60){
    		return array('code'=>'500','msg'=>'time out');
    	}
    	if(!isset($params['mobile']) || empty($params['mobile'])){
			return array('code'=>'500','msg'=>'mobile is empty');
		}
		if(!isset($params['from']) || empty($params['from'])){
			return array('code'=>'500','msg'=>'from is empty');
		}
		return $this->do_oauth_signin($params['mobile'],$params['from']);
    }

    private function do_oauth_signin($mobile,$from) {
		$this->db->from("user");
		$this->db->where(array('mobile'=>$mobile));
		if ($this->db->count_all_results() > 0){
			$this->update_user(
				array( "mobile"=>$mobile ),
				array( "last_login_time"=>date("Y-m-d H:i:s"))
			);
			$user = $this->getUser("",array( "mobile"=>$mobile ));
			if(isset($user['code'])){
				return $user;
			}
			$this->session->sess_expiration = $this->session_expiretime;
			$user['oauth_from'] = $from;
			$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
			$session_id = $this->session->set_userdata($user);
			session_id($session_id);
			session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
			$_SESSION['user_detail'] = $user;
			session_write_close();
		}else{
			$userfield  = array('email'=>$mobile.'@fruitday.com','username'=>$mobile,'mobile'=>$mobile, 'password'=>md5(rand(10000000,99999999)),'reg_time'=>date("Y-m-d H:i:s"),'last_time'=>date("Y-m-d H:i:s"));

			$this->db->insert("user",$userfield);            
			$uid  =  $this->db->insert_id();
			$user = $this->getUser("",array('id'=>$uid));
			if(isset($user['code'])){
				return $user;
			}
			$this->session->sess_expiration = $this->session_expiretime;
			$user['oauth_from'] = $from;
			$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
			$session_id = $this->session->set_userdata($user);
			session_id($session_id);
			session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
			$_SESSION['user_detail'] = $user;
			session_write_close();
		}
		// error_log(date('Y-m-d H:i:s')."    ".$from."    ".$mobile."\n",3,"/mnt/www/oauthlog/".date("Y-m-d").".log");
		return array('code'=>'200','connect_id'=>$session_id);
	}

	private function getUser($uid="",$condition="") {
		$this->db->select("id,email,username,money,mobile,mobile_status,reg_time,last_login_time,badge");
		$this->db->from("user");

		if($condition)
			$this->db->where($condition);
		else
			$this->db->where("id",$uid);

		$query = $this->db->get();
		$user = $query->row_array();

		$badge = unserialize($user['badge']);
        $user_badge = 0;
        if(is_array($badge)){
            $user_badge = $badge[0];
        }
		$response = array();

		if(empty($user)){
			$return_result = array('code'=>'300','msg'=>'用户名或密码错误');    
			return $return_result;
		}
		if($condition)
			$jf = $this->getUserScore($user['id']);
		else
			$jf = $this->getUserScore($uid);

		$coupon_num = $this->getCouponNum($user['id'],0);
		$response = array_merge($user,$jf);
		$response['coupon_num'] = $coupon_num;
		$response['user_badge'] = $user_badge;
		return $response;
	}

	private function getUserScore($uid) {
		$this->db->select("sum(jf) as jf");
		$this->db->from("user_jf");
		$this->db->where("uid",$uid);
		$query = $this->db->get();
		$jf = $query->row_array();
		if(empty($jf['jf'])){
			$jf['jf'] = 0;
		}
		$jf['jf'] = floor($jf['jf']);
		return $jf;
	}

	private function getCouponNum($uid,$used){
		$condition['uid'] = $uid;
		$condition['to_date >='] = date("Y-m-d");
		$condition['is_used'] = $used;
		$condition['is_sent'] = 1;
		return $this->db->from("card")->where($condition)->count_all_results();
	}

	private function update_user($condition = array(), $data = array()){
		$this->db->where($condition);
		$this->db->limit(1);
		$this->db->update("user", $data);
	}
}