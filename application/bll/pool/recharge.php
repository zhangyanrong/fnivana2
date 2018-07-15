<?php
namespace bll\pool;

class Recharge
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }


    /**
     * 获取
     *
     * @return void
     * @author 
     **/
    public function get_push_data($trade_filter = array())
    {
        $filter = array(
            't.has_deal' => '1',
            //'t.status' => array('已充值','OMS操作充值'),
            't.status' => '已充值',
            't.sync_erp' => '0',
            );
        if ($trade_filter) {
            $new_filter = array();
            foreach ($trade_filter as $key => $value) {
                $new_filter["t.".$key] = $value;
            }
            
            $filter = array_merge($filter,$new_filter);
        }else{
            $filter['t.time >='] = date("Y-m-d",strtotime("-7 day"));
        }

        $this->ci->load->model('trade_model');
        
        $this->ci->db->select("t.trade_number,t.money,t.uid,u.username,u.mobile,t.payment,t.card_number,t.trade_no,t.time,t.update_pay_time,t.post_at")->from('ttgy_trade as t')->join('ttgy_user as u','t.uid=u.id','left');
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $this->ci->db->where_in($key,$value);
            } else {
                $this->ci->db->where($key,$value);
            }
        }
        $trades = $this->ci->db->limit(50)->get()->result_array();
        if (!$trades) return array();

        //$user = $this->ci->db->select('*')->from('user')->where('id',$trade['uid'])->get()->row_array();


        // $paym = array('东方支付'=>1, '微信支付'=>1, '支付宝'=>1,'联华OK卡'=>1, '网上银行'=>1,'天天果园充值卡'=>5,'账户余额充值'=>9,'银联手机支付'=>1,'银联支付'=>1,'微信WAP支付'=>1,'微信扫码支付'=>1,'微信APP支付'=>1,'微信公众号支付'=>1,'微信平台支付'=>1,'微信公众号支付'=>1,'ApplePay'=>1,'广发信用卡(银联)'=>1);
        // $payment = array('东方支付'=>1009,'支付宝' => 1003, '联华OK卡'=>1002, '网上银行' => 1001, '微信支付' => 1005,'天天果园充值卡'=>5002,'银联手机支付'=>1014,'银联支付'=>1013,'微信WAP支付'=>1005,'微信扫码支付'=>1005,'微信APP支付'=>1005,'微信公众号支付'=>1005,'微信平台支付'=>1005,'微信公众号支付'=>1005,'ApplePay'=>1017,'广发信用卡(银联)'=>1016);
        $paym = $this->ci->config->item("paym");
        $payment = $this->ci->config->item("payment");
        $data = array();
        foreach ($trades as $trade) {
            $data[] = array(
                'trancode'    => $trade['trade_number'],
                'amount'      => (float) $trade['money'],
                'buyerId'     => (int) $trade['uid'],
                'name'        => $trade['username'],
                'phone'       => $trade['mobile'],
                'paym'        => (int) $paym[$trade['payment']],
                'payplatform' => (int) $payment[$trade['payment']],
                'tckCode'     => (string) $trade['card_number'],
                'tckCount'    => $trade['card_number'] ? 1 : 0,
                'chrgno'      => (string) $trade['trade_no'],
                'createtime'  => $trade['time'],
                'paytime'     => $trade['update_pay_time'] ? $trade['update_pay_time'] : $trade['post_at'],

            );
        }
        

        return $data;
    }

    public function pushoms($params)
    {
        if (!$params['trade_number']) return array('code'=>300,'msg'=>'空充值单号');

        $this->ci->load->bll('rpc/request');
        $this->ci->load->model('trade_model');
        foreach ($params['trade_number'] as $trade_number) {
            $trade = $this->get_push_data(array('trade_number'=>$trade_number));
            $trade = $trade[0];
            if (!$trade) continue;

            $log = array(
                'rpc_desc' => '手动推送充值记录',
                'obj_type' => 'trade',
                'obj_name' => $trade_number,
            );
            $this->ci->bll_rpc_request->set_rpc_log($log);

            $rs = $this->ci->bll_rpc_request->realtime_call(POOL_RECHARGE_URL,$trade);

            if ($rs == false) continue ;

            $this->ci->trade_model->update(array('sync_erp'=>'1'),array('trade_number'=>$trade_number));
        }
        
        return array('code'=>200,'msg'=>'同步成功');
    }

    public function pushone($filter)
    {
        $this->ci->load->bll('rpc/request');
        $this->ci->load->model('trade_model');

        $trade = $this->get_push_data($filter);
        $trade = $trade[0];
        if (!$trade) return false;

        $log = array(
            'rpc_desc' => '手动推送充值记录',
            'obj_type' => 'trade',
            'obj_name' => $trade['trancode'],
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_RECHARGE_URL,$trade);

        if ($rs == false) return false;

        $this->ci->trade_model->update(array('sync_erp'=>'1'),array('trade_number'=>$trade['trancode']));
    
        return true;
    }

    public function get_trade_new($trade_numbers){
        $where = '';
        if ($trade_numbers) {
            $trade_numbers = implode("','", $trade_numbers);
            $where = " and t.trade_number in('".$trade_numbers."')";
        }
        $limit = 100;
        $s_time = date('Y-m-d H:i:s',(time()-3600*24*3));
        $e_time = date('Y-m-d H:i:s',(time()));
        $where or $where = " and t.time between '".$s_time."' and '".$e_time."' ";

        $sql = "select u.username,t.trade_number,t.trade_no,t.payment,t.money,t.uid,t.time,t.update_pay_time,t.card_number from ttgy_trade t left join ttgy_user u on t.uid=u.id where t.has_deal=1 and t.sync_erp=1 and t.status='已充值' ".$where." order by t.time limit ".$limit;
        $result = $this->ci->db->query($sql)->result_array();
        return $result;
    }
}