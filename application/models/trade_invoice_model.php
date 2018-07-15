<?php
class Trade_invoice_model extends MY_Model {

    public function table_name(){
        return 'trade_invoice';
    }

    private function setTrade($uid, $name, $fp_id_no , $address, $mobile, $username)
    {

        $this->uid = $uid;
        $this->name = $name;
        $this->fp_id_no = $fp_id_no;
        $this->address = $address;
        $this->mobile = $mobile;
        $this->username = $username;
    }

	function save($uid, $invoice_info , $parm=null)
    {
        $setted = $this->setTrade($uid, $invoice_info['invoice']['name'], $invoice_info['invoice']['fp_id_no'], $invoice_info['invoice']['address'],  $invoice_info['invoice']['mobile'], $invoice_info['invoice']['username']);
        if( $setted )
            return $setted;

		try {
            $this->db->select('name');
            $this->db->from('area');
            $this->db->where_in('id',array($invoice_info['province'],$invoice_info['city'],$invoice_info['area']));
            if( strcmp($parm['version'], '3.4.0') == 0 && $parm['channel'] == 'AppStore'){
                 $area_info[0]['name'] = $invoice_info['province'];                       
                 $area_info[1]['name'] = $invoice_info['city'];                       
                 $area_info[2]['name'] = $invoice_info['area'];                       
            }else{
                $area_info = $this->db->get()->result_array();
            }
			if( $this->db->insert(
				"trade_invoice",
				array(
						"uid" => $this->uid,
						"name"=>$this->name,
                        "fp_id_no"=>$this->fp_id_no,
                        "money"=>$this->money,
						"address"=>$this->address,
						"mobile"=>$this->mobile,
                        "username"=>$this->username,
						"invoice_content"=>serialize( $this->trades ),
                        "time" => date("Y-m-d H:i:s"),
                        "province"=>$area_info[0]['name'],
                        "city"=>$area_info[1]['name'],
                        "area"=>$area_info[2]['name'],
                        "kp_type"=>$parm['kp_type']
					)
			) ) {
				return array("error"=>0,"msg"=>"添加成功","invoice"=>$this->db->insert_id());
            }
		} catch ( Exception $e) {
            log_message('error', $e->getMessage());
			return array("error"=>1,"msg"=>$e->getMessage());
		} 
    
    }

    function setTradeAndAmount($transactions)
    {
        if( empty( $transactions ) )
			return array("error"=>1,"msg"=>"无可开发票交易");

        $trades = array();
        $amount = 0;
        foreach($transactions as $t)
        {
            if( $t->has_deal && $t->money >0 )
            {
                $trades[] = $t->trade_number;
                $amount += $t->money;
            }
        }

        if( empty($trades) )
			return array("error"=>1,"msg"=>"无可开发票交易");

        $this->trades = $trades;

        //if( $amount < 100 )
		//	return array("error"=>1,"msg"=>"总金额小于100无法开票");

        $this->money = $amount;

    }

	public function getTransactionByTrade($trades, $condition=array())
    {
        if( empty($trades) )
            return;

        return $this->db->from("trade")->where_in("trade_number",$trades)
            ->get()->result();
    }

    public function updateTransaction($trades, $data=array())
    {
        if( empty($data) || empty($trades) )
            return;

        $this->db->where_in("trade_number",$trades);
        $this->db->update("trade", $data);
    }

	public function checkTotalMoney($trade_number)
    {
        if( empty($trade_number) )
            return;

        $result = $this->db->select('sum(money) as total')->from("trade")->where_in("trade_number",$trade_number)
            ->get()->result_array();

		return $result[0]['total'];
    }

    public function upInvoiceTrack($invoice_id,$tracking_number,$express,$express_id,$invoice_no,&$msg){
        $type=0;
        $invoice = explode('_', $invoice_id);
        if($invoice[1] != 'T'){
            $msg = '此单不是充值发票';
            return false;
        }
        $invoice_id = $invoice[2];
        $invoice_info = $this->dump(array('id'=>$invoice_id));
        if(empty($invoice_info)){
            $msg = '无此充值发票';
            return false;
        }
        if(empty($tracking_number)){
            $msg = '缺少运单号';
            return false;
        }
        if(empty($express_id)){
            $msg = '缺少物流公司ID';
            return false;
        }
        if(empty($express)){
            $msg = '缺少物流公司名称';
            return false;
        }
        if(empty($invoice_no)){
            $msg = '缺少发票号';
            return false;
        }
        $res = $this->db->select('id')->from('invoice_track')->where(array('invoice_id'=>$invoice_id,'type'=>$type))->get()->row_array();
        $data = array();
        $data['tracking_number'] = $tracking_number;
        $data['express_id'] = $express_id;
        $data['express'] = $express;
        $data['invoice_no'] = $invoice_no;
        if($res){
            $this->db->where(array('id'=>$res['id']));
            $result = $this->db->update('invoice_track',$data);
        }else{
            $data['invoice_id'] = $invoice_id;
            $data['type'] = $type;
            $result = $this->db->insert('invoice_track',$data);
        }
        if(! $result){
            $msg = '物流休息录入失败';
            return false;
        }
        return true;
    }

    public function getInvoiceHistory($uid,$limit,$offset){
        $sql = "select i.uid,i.username,i.mobile,i.name,i.fp_id_no,i.address,i.money,i.invoice_content,i.time,i.province,i.city,i.area,i.kp_type,t.express,t.tracking_number,t.invoice_no from ttgy_trade_invoice i left join ttgy_invoice_track t on i.id=t.invoice_id  and t.type=0 where i.uid=".$uid." order by i.id desc limit ".$limit." offset ".$offset;
        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            $result[$key]['trade_list'] = unserialize($value['invoice_content']);
            unset($result[$key]['invoice_content']);
        }
        return $result;
    }

    public function getFpIdNoHistory($uid,$fp){
        $this->db->distinct();
        $this->db->select('fp_id_no');
        $this->db->from('trade_invoice');
        $this->db->where(array('uid' => $uid, 'name' => $fp, 'fp_id_no is not null'=>null));
        $result = $this->db->get()->result_array();
        return $result;
    }
}
