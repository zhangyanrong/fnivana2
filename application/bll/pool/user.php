<?php
namespace bll\pool;

class User
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 获取会员信息
     *
     * @return void
     * @author 
     **/
    public function getDetail($filter)
    {
        if (!$filter['buyerId'] && !$filter['buyerPhone'] && !$filter['email']) {
            return array('result'=> 0,'msg'=>'参数错误');
        }

        $this->ci->db->select('id,money,mobile,username,jf')
                             ->from('user');

        if($filter['buyerId']) $this->ci->db->where('id',$filter['buyerId']);
        if($filter['buyerPhone']) $this->ci->db->where('mobile',$filter['buyerPhone']);
        if($filter['email']) $this->ci->db->where('email',$filter['email']);

        $this->ci->db->limit(1);

        $user = $this->ci->db->get()->row_array();

        $data = array();
        if ($user) {
            $data['buyerId'] = (int) $user['id'];
            $data['buyerPhone'] = (string) $user['mobile'];
            $data['email'] = (string) $user['email'];
            $data['buyer'] = (string) $user['username'];
            $data['accAmount'] = (string) $user['money'];

            //$jf = $this->ci->db->select_sum('jf','total')->from('user_jf')->where('uid',$user['id'])->get()->row_array();

            $data['intAmount'] = (float) $user['jf'];

            $this->ci->load->model('user_model');
            $user_money = $this->ci->user_model->get_user_real_money($user['id']);

            $data['accAmount'] = (float) $user_money['amount'];   //实际金额
            $check_chengdu = $this->checkChengduUser($user['id']);
            if($check_chengdu === true){
                $data['withdrawMoney']  = (float) $user_money['amount'];
            }else{
                $withdraw = $this->ci->user_model->getWithdraw($user['id'],$user_money['amount']);
                $data['withdrawMoney']  = (float) $withdraw['withdraw'];  //可提现金额
            }
            
            //$data['otherMoney'] = (float) $withdraw['otherMoney'];  //须额外扣除赠送金额（按照最大可提现金额计算）
        }

        $this->rpc_log = array('rpc_desc' => '会员查询','obj_type'=>'user','obj_name'=>$user['id']);
        return array('result' => 1, 'data' => $data);
    }

    /*
    *会员帐户余额添加/使用
    *buyerId,money,type(outlay,income)
    */
    public function userMoney($filter){
        if (!$filter['buyerId']) {
            return array('result'=> 0,'msg'=>'buyerId 参数错误');
        }
        if (!$filter['money']) {
            return array('result'=> 0,'msg'=>'money 参数错误');
        }
        if (!$filter['type']) {
            return array('result'=> 0,'msg'=>'type 参数错误');
        }
        if (!$filter['refund_id']) {
            return array('result'=> 0,'msg'=>'refund_id 参数错误');
        }
        // if (!$filter['order_name']) {
        //     return array('result'=> 0,'msg'=>'order_name 参数错误');
        // }
        
        if ($filter['type'] != 'outlay'  && $filter['type'] != 'income') {
            return array('result'=> 0,'msg'=>'参数错误');
        }
        if (!is_numeric($filter['money']) || $filter['money']<=0) {
            return array('result'=> 0,'msg'=>'金额必须是数字');
        }
        $this->rpc_log = array('rpc_desc' => '会员余额扣款','obj_type'=>'userMoney','obj_name'=>$filter['refund_id']);
        $this->ci->db->select('id')->from('trade');
        $this->ci->db->where('refund_id',$filter['refund_id']);
        $this->ci->db->limit(1);
        $trade = $this->ci->db->get()->row_array();
        if($trade){
            return array('result'=> 1,'msg'=>'refund_id已存在');
        }

        $this->ci->db->select('id,money')->from('user');
        $this->ci->db->where('id',$filter['buyerId']);
        $this->ci->db->limit(1);
        $user = $this->ci->db->get()->row_array();

        if(empty($user)){
            return array('result'=> 0,'msg'=>'用户id错误');
        }
        $this->ci->load->model('user_model');
        $check = $this->ci->user_model->check_money_identical($filter['buyerId']);
        if($check === false){
            $this->ci->user_model->freeze_user($filter['buyerId']);
            return array('result'=> 0,'msg'=>'帐户余额异常，已冻结');
        }
        $money   =  preg_replace("/[+|-]/","",$filter['money']);
        if($filter['type']=='outlay'){
            if($user['money']<$money){
                return array('result'=> 0,'msg'=>'帐户余额不足');   
            }
            $money = -$money;
        }

        $mtime = explode('.', microtime(true));  
        $tradenumber=date("Ymdhis").$mtime[1];
        $time  =   date("Y-m-d H:i:s");
        $l_msg = ($filter['type'] == "outlay")?"扣款":"充值";
        $this->ci->db->trans_begin();
        $trade_data = array(
            'uid'=>$user['id'],
            'trade_number'=>$tradenumber,
            'payment'=>'账户余额',
            'money'=>$money,
            'type'=>$filter['type'],
            'time'=>$time,
            'has_deal'=>'1',
            'status'=>'OMS操作'.$l_msg,
            'refund_id'=>$filter['refund_id'],
            'order_name'=>$filter['order_name'],
        );
        $this->ci->db->insert('trade',$trade_data);
        $trade_id = $this->ci->db->insert_id();
        
        $trade_op_data = array(
            'trade_id'=>$trade_id,
            'operator'=>'OMS',
            'description'=>'OMS操作'.$l_msg.'，金额为'.$money,
            'msg'=>'OMS操作'.$l_msg,
            'time'=>$time
        );
        $this->ci->db->insert('trade_op',$trade_op_data);

        $acount = $user['money']+$money;   
        $update_data = array(
            'money' => $acount
        );
        $this->ci->db->where('id', $user['id']);
        $this->ci->db->update('user',$update_data);

        if(isset($filter['isTiXian']) && $filter['isTiXian'] == 1){
            $this->ci->user_model->setWithdrawUser($filter['buyerId']);
        }

        if ($this->ci->db->trans_status() === FALSE){
            $this->ci->db->trans_rollback();
            return array('result'=> 0,'msg'=>'操作失败');
        }else{
            $this->ci->db->trans_commit();
            return array('result'=> 1,'msg'=>$tradenumber);
        }
    }

    /*
    *会员积分添加/使用
    *buyerId,money,type(outlay,income)
    */
    public function userJf($filter){
        $this->rpc_log = array('rpc_desc' => '会员积分操作','obj_type'=>'userJf','obj_name'=>$filter['refund_id']);
        if (!$filter['buyerId'] || !$filter['money'] || !$filter['type']  || !$filter['refund_id'] || !$filter['order_name']) {
            return array('result'=> 0,'msg'=>'参数错误');
        }
        if ($filter['type'] != 'outlay'  && $filter['type'] != 'income') {
            return array('result'=> 0,'msg'=>'参数错误');
        }
        if (!is_numeric($filter['money']) || $filter['money']<=0) {
            return array('result'=> 0,'msg'=>'金额必须是正数');
        }

        // $this->ci->db->select_sum('jf');
        // $this->ci->db->from('user_jf');
        // $this->ci->db->where('uid',$filter['buyerId']);
        // $user = $this->ci->db->get()->row_array();
        $this->ci->load->model('user_model');
        $real_jf = $this->ci->user_model->checkUserJf($filter['buyerId']);
        $user_jf_money = number_format(floor($real_jf/100),0,'','');

        $money   =  preg_replace("/[+|-]/","",$filter['money']);
        if($filter['type']=='outlay'){
            if($user_jf_money<$money){
                return array('result'=> 0,'msg'=>'帐户积分不足');   
            }
            $money = -$money;
        }

        $time  =   date("Y-m-d H:i:s");
        $l_msg = ($filter['type'] == "outlay")?"扣款":"充值";
        $this->ci->db->trans_begin();
        $jf_data = array(
            'uid'=>$filter['buyerId'],
            'jf'=>$money*100,
            'time'=>$time,
            'reason'=>'OMS操作'.$l_msg,
            'refund_id'=>$filter['refund_id'],
            'order_name'=>$filter['order_name'],
            'type'=>'OMS操作',
        );
        $this->ci->db->insert('user_jf',$jf_data);
        $type = 1;
        if($filter['type']=='outlay'){
            $type = 2;//扣积分
        }
        $this->ci->user_model->updateJf($filter['buyerId'],$money*100,$type);
        if ($this->ci->db->trans_status() === FALSE){
            $this->ci->db->trans_rollback();
            return array('result'=> 0,'msg'=>'操作失败');
        }else{
            $this->ci->db->trans_commit();
            return array('result'=> 1,'msg'=>'操作成功');
        }
    }


    public function add_black_list($filter){
        if (!$filter['black_list']){
            return array('result'=> 0,'msg'=>'参数错误');
        }
        $this->ci->load->model('user_model');
        $mobiles = array();
        $credit_rank = array();
        foreach ($filter['black_list'] as $key => $value) {
            $mobiles[] = $value['mobile'];
            $credit_rank[$value['mobile']] = isset($value['credit_rank'])?intval($value['credit_rank']):1;
        }
        $where = array();
        $where['mobile'] = $mobiles;
        $user_list = $this->ci->user_model->getList('id,mobile',$where);
        $black_list = array();
        foreach ($user_list as $value) {
            $black_user['uid'] = $value['id'];
            $black_user['credit_rank'] = $credit_rank[$value['mobile']];
            $black_list[] = $black_user;
        }
        $result = $this->ci->user_model->addBlackList($black_list);
        $this->rpc_log = array('rpc_desc' => '会员黑名单添加','obj_type'=>'addBlackList');
        if($result) return  array('result'=> 1,'msg'=>'操作成功');
        else array('result'=> 0,'msg'=>'操作失败');
    }

    public function remove_black_list($filter){
        if (!$filter['black_list']){
            return array('result'=> 0,'msg'=>'参数错误');
        }
        $mobiles = array();
        foreach ($filter['black_list'] as $key => $value) {
            $mobiles[] = $value['mobile'];
        }
        $this->ci->load->model('user_model');
        $where = array();
        $where['mobile'] = $mobiles;
        $user_list = $this->ci->user_model->getList('id',$where);
        $uids = array();
        foreach ($user_list as $value) {
            $uids['id'] = $value['id'];
        }
        $result = $this->ci->user_model->removeBlackList($uids);
        $this->rpc_log = array('rpc_desc' => '会员黑名单移除','obj_type'=>'removeBlackList');
        if($result) return  array('result'=> 1,'msg'=>'操作成功');
        else array('result'=> 0,'msg'=>'操作失败');
    }

    function checkChengduUser($uid){
        $this->ci->load->model('user_model');
        $res = $this->ci->user_model->get_Withdraw_White_User($uid);
        if($res){
            return true;
        }
        return false;
    }
}