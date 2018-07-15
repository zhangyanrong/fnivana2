<?php
namespace bll;

class Survey
{
    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->model('survey_model');
        $this->ci->load->helper('public');

    }


    /**
     * [ajaxDistSurvey 提交配送问卷调查]
     */
    public function commit($params)
    {
        $sid = $params['sid'];
        $cid = $params['cid'];
        $uques = (array)$params['ques'];
        $unique = $params['unique'];
        $pa_score   = $params['score'];

        $de_unique = $this->_initDistUser($unique);
        $uid = $de_unique['uid'];
        $ordername = $de_unique['ordername'];

        //验证是否为空
        if(empty($sid) || empty($uid) || empty($ordername)){
            $return = array('code'=>'300','msg'=>'参数异常');
            echo json_encode($return);
            exit;
        }

        //验证订单id和用户id是否匹配
        $ck_ures =  $this->ci->survey_model->ckUserOrder($ordername, $uid);
        if(!$ck_ures){
            $return = array('code'=>'300','msg'=>'非法请求');
            echo json_encode($return);
            exit;
        }

        //验证是否重复提交
        $ck_sres =  $this->ci->survey_model->ckDistSurvey($sid, $ordername);
        if(!$ck_sres){
            $return = array('code'=>'300','msg'=>'您已提交过哦');
            echo json_encode($return);
            exit;
        }

        //将数据插入数据库
        $curr_time = time();
        $score = 0;
        $whole_score = 0;
        foreach($uques as $k=>$v){
            if($k=="two-1"){
                $whole_ans_data[0] = array(
                    'ans_id'=>1,
                    'ans'=>$v,
                    'ctime'=>$curr_time,
                );
                $whole_score = $v;
            }else{
                $ans_data[] = array(
                    'ans_id'=>$k,
                    'ans'=>$v,
                    'ctime'=>$curr_time,
                );
                $score += $v;
            }
        }
        //特殊调查
//        $comp_data = array(
//            'sid' => $sid,
//            'uid' => $uid,
//            'remark' => $ordername,
//            'ctime' => $curr_time,
//            'score' => $score,
//        );
//        $comp_id =  $this->ci->survey_model->insComp($comp_data);
//        foreach($ans_data as &$val){
//            $val['comp_id'] = $comp_id;
//        }
//        $this->ci->survey_model->insAns($ans_data);
        $whole_score = $pa_score;
        //基本调查(服务态度评价)
        $whole_comp_data = array(
            'sid' => 2,
            'uid' => $uid,
            'remark' => $ordername,
            'ctime' => $curr_time,
            'score' => $whole_score,
        );
        $whole_comp_id =  $this->ci->survey_model->insComp($whole_comp_data);
        $whole_ans_data[0]['comp_id'] = $whole_comp_id;
        $this->ci->survey_model->insAns($whole_ans_data);
        $return = array('code'=>'200','msg'=>'succ');
        echo json_encode($return);
        exit;
    }


    /**
     * [_initUser 初始化用户信息]
     * @param  [type] $unique
     */
    private function _initDistUser($unique){
        $unique_arr = explode("#",base64_decode(urldecode($unique)));
        $uid = base_convert($unique_arr[0],36,10);
        $ordername = base_convert($unique_arr[1],36,10);
        return array(
            'uid'=>$uid,
            'ordername'=>$ordername,
        );
    }



}
