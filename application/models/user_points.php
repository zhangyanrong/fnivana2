<?php
class User_points extends CI_model {
	 private $userId;
	 private $totalPoints=0;
	 private $userPoints;

	 function __construct()
	 {
	 	parent::__construct();
	 }

	 public  function getUserPoints($uid="", $limit=1, $offset="") {

	 	$condition = array();

	 	if( $uid )
	 		$condition['uid'] = $uid;

        $result = $this->db->from("user_jf")->where($condition)
            ->limit($limit,$offset)
            ->order_by('time','desc')
            ->get()->result();

        if( !empty($result) )
            $this->userPoints = $result;

        return $result;
        unset($result);
    }

    public function getTotalPoints()
    {
    	return $this->totalPoints;
    }

    public function getPointsCount($uid)
    {
    	if( $uid > 0 )
            $condition = array("uid"=>$uid);
        else
            $condition = array();


        $this->db->select("count(*) as count, sum(jf) as total");
        $result = $this->db->from("user_jf")->where($condition)
        	->get()->result();

        $this->totalPoints = $result[0]->total;
        
        return $result[0]->count;
    }

    function getUserScore($uid) {
        $this->db->select("sum(jf) as jf");
        $this->db->from("user_jf");
        $this->db->where("uid",$uid);
        $query = $this->db->get();
        $jf = $query->row_array();
        return $jf;
    }

    public function addPoints(Users $user, $type ,$point="", $reason="")
    {
        if( ! $user->getUserId() )
            return array("error"=>1, "msg"=>"请求的用户ID错误");

        $check = $this->checkAddPointType($type);
        if( $check )
            return $check;

        $points = $this->getPoint($type);

        if( $user->beVerified($type) && !$point )
            return array("error"=>1, "msg"=>"已经验证用户");

        $point = $point ? $point : $points['point'];

        if( $this->db->insert("user_jf",
            array(
                "uid" => $user->getUserId(),
                "jf" => $point ? $point : $points['point'],
                "reason" => $reason? $reason : $points['reason'],
                "time" => date("Y-m-d H:i:s")
            )
        ) )
            return array("error"=>0, "msg"=>"成功获取".$point."积分");
            
    }

	/**
	 * 注册送赠品
	 */
	public function giveGift(User $user) {
		if (!$user->getUserId())
			return array("error" => 1, "msg" => "请求的用户ID错误");
		if (date('Y-m-d', strtotime("2015-08-10")) > date('Y-m-d')) {
			return;
		}
		$active_id = 919;
		$active_tag = 'registerSend';
        $gift_send = $this->db->select('*')->from('gift_send')->where('id', $active_id)->get()->row_array();
        if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
            $gift_start_time = date('Y-m-d');
            $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
        }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
            $gift_start_time = $gift_send['gift_start_time'];
            $gift_end_time = $gift_send['gift_end_time'];
        }else{
            $gift_start_time = $gift_send['start'];
            $gift_end_time = $gift_send['end'];
        }
		$user_gift_data = array(
			'uid' => $user->getUserId(),
			'active_id' => $active_id,
			'active_type' => '2',
			'has_rec' => '0',
            'start_time'=>$gift_start_time,
            'end_time'=>$gift_end_time,
		);
		$this->db->insert('user_gifts', $user_gift_data);
	}

	private function checkAddPointType($type)
    {
        $pointsType = array("verify_mobile", "verify_email","cancel_order","verify_birthday");

        if( ! in_array( $type, $pointsType ) )
            return array("error"=>1, "msg"=>"获取积分类型错误，不能添加积分");
    
    }

    public function getPoint($type)
    {
        $points = array();

        switch ($type) {
            case 'verify_mobile':
                $points['point'] = 500;
                $points['reason'] = "验证手机获取".$points['point']."积分";
                break;
            case 'verify_email':
                $points['point'] = 100;
                $points['reason'] = "验证邮箱获取".$points['point']."积分";
                break;
            case 'verify_birthday':
                $points['point'] = 500;
                $points['reason'] = "完善会员生日信息获取".$points['point']."积分";
                break;
        }

        return $points;
    }

    // 8<------------------------------------------------------------------------------
    /**
     * 积分发放
     *
     * @return void
     * @author 
     **/
    public function jf_grant($user,$score,$reason,$type='')
    {
        // 会员等级多倍积分
        $this->load->model("user_rank");
        $score = $this->user_rank->cal_rank_score($score,$user['user_rank'],$msg);

        $data = array(
            'jf'     => $score, 
            'reason' => sprintf($reason,$msg.$score), 
            'time'   => date("Y-m-d H:i:s"), 
            'uid'    => $user['uid'],
        );

        $rs = $this->db->insert('user_jf', $data);
        $this->load->model('user_model');
        $this->user_model->updateJf($user['uid'],$score,1);
        return $rs ? array('id'=>$this->db->insert_id(),'score'=>$score) : false;
    }
}
?>
