<?php
namespace bll\pool;

class Complany
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /*大客户收款*/
    public function getComplanyService($filter){
        $mobile = $filter['mobile'];
        $money = $filter['money'];
        $out_trade_number = $filter['out_trade_number'];

        if (empty($mobile) || empty($money) || empty($out_trade_number)) {
            return array('result'=> 0,'msg'=>'缺少传入参数');
        }

        if (!preg_match('/^1[34578]\d{9}$/', $mobile)) {
            return array('result'=> 0,'msg'=>'手机号码格式错误');
        } else {
            $this->ci->db->select('id');
            $this->ci->db->from('user');
            $this->ci->db->where('mobile',$mobile);
            $user = $this->ci->db->get()->result_array();
            if (count($user)>1) {
                return array('result'=> 0,'msg'=>'该手机号对应多个用户，请联系技术部处理songtao@fruitday.com');
            } else {
                $uid = $user[0]['id'];
                if (empty($uid) || !is_numeric($uid) || $uid<0) {
                    return array('result'=> 0,'msg'=>'此用户异常');
                }
                $time = date('Y-m-d H:i:s',time());
                $inset_data = array(
                    'uid'=>$uid,
                    'out_trade_number'=>$out_trade_number,
                    'payment'=>'网上支付',
                    'time'=>$time,
                    'money'=>$money,
                    'pay_status'=>0,
                    'sync_erp'=>0,
                );

                $this->ci->db->select('id');
                $this->ci->db->from('complany_service');
                $this->ci->db->where('out_trade_number',$out_trade_number);
                $list= $this->ci->db->get()->row_array();
                if ($list) {
                    return array('result'=> 0,'msg'=>'请不要重复推送');
                }

                $result = $this->ci->db->insert('complany_service',$inset_data);

                if($result){
                    return array('result'=> 1,'msg'=>'存储成功');
                }else{
                    return array('result'=> 0,'msg'=>'存储失败');
                }
                exit;
            }
        }
    }


    public function sendComplanyService(){
        $sql = "SELECT id,out_trade_number,payment,pay_status FROM ttgy_complany_service WHERE pay_status=1 and sync_erp=0 limit 50";
        $res = $this->ci->db->query($sql)->result_array();
        if (empty($res)) {
            return array();
        }
        // $payment = array('东方支付'=>1009,'支付宝' => 1003, '联华OK卡'=>1002, '网上银行' => 1001, '微信支付' => 1005,'天天果园充值卡'=>5002,'银联手机支付'=>1014,'银联支付'=>1013,'微信WAP支付'=>1005,'微信扫码支付'=>1005,'微信APP支付'=>1005,'微信公众号支付'=>1005,'微信平台支付'=>1005,'微信公众号支付'=>1005,'ApplePay'=>1017,'广发信用卡(银联)'=>1016);
        $payment = $this->ci->config->item("payment");
        $pay_status = array('未支付','已支付');

        foreach ($res as &$value) {
            $value['payment'] = $payment[$value['payment']];
            $value['pay_status'] = $pay_status[$value['pay_status']];
        }

        return $res;
    }

    /*更新状态已同步*/
    public function updateSynErp($data){
         $data_str = implode(',', $data['id']);

         $ftime = date('Y-m-d H:i:s',time());

         $fsql ="INSERT INTO ttgy_complany_log (complany_service_id,`time`) VALUES('".$data_str."','".$ftime."')";
         $fres = $this->ci->db->query($fsql);


        $sql = "UPDATE ttgy_complany_service SET sync_erp=1 WHERE id IN (".$data_str.")";
        $res = $this->ci->db->query($sql);
    }

}