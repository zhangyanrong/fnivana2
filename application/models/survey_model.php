<?php
class Survey_model extends MY_Model {

    CONST OPEN_SEND_JF = 0;

    function __construct() {
        parent::__construct();
    }

    public function insComp($data){
        if(empty($data['sid']) || empty($data['uid'])){
            return 0;
        }
        $this->db->insert('survey_usercomp', $data);
        $comp_id = $this->db->insert_id();
        if(self::OPEN_SEND_JF == 1){
            if(!empty($comp_id)){
                $jf = 20;
                $jf_data = array(
                    'uid'=>$data['uid'],
                    'time'=>$data['ctime'],
                    'reason'=>'问卷调查获得'.$jf.'积分',
                    'jf'=>$jf,
                    'type'=>'问卷调查',
                );
                $this->db->insert('user_jf', $jf_data);
                $this->load->model('user_model');
                $this->user_model->updateJf($data['uid'],$jf,1);
            }
        }
        return $comp_id;
    }


    public function insAns($data){
        $this->db->insert('survey_userans', $data[0]);
    }


    /**
     * [ckDistSurvey 验证订单问卷调查]
     * @param  [int] $sid       [问卷主题id]
     * @param  [int] $ordername [订单号]
     * @return [boolen]            [true:无记录false:已经存在]
     */
    public function ckDistSurvey($sid, $ordername){
        if(empty($sid) || empty($ordername)){
            return false;
        }
        $sql = "select * from ttgy_survey_usercomp where  sid='{$sid}' and remark='{$ordername}'";
        $num = $this->db->query($sql)->result_array();

        if(empty($num)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * [ckUserOrder 验证订单id和用户id是否匹配]
     * @param  [int] $ordername [description]
     * @param  [int] $uid       [description]
     * @return [boolen]            [description]
     */
    public function ckUserOrder($ordername, $uid){
        if(empty($ordername) || empty($uid)){
            return false;
        }
        if(!is_numeric($ordername) || !is_numeric($uid)){
            return false;
        }
        $sql = "select uid from ttgy_order where operation_id = 9 and order_region =1 and order_name='{$ordername}'";
        $res = $this->db->query($sql)->result_array();

        if(empty($res)){
            return false;
        }
        if($res[0]['uid']==$uid){
            return true;
        }else{
            return false;
        }
    }

}