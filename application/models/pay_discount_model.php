<?php
class Pay_discount_model extends CI_model {
	private $table = 'pay_discount';
	
	public function Pay_discount_model(){
		parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
	}
	
	public function get_pay_discount($pay_parent_id,$pay_id,$money,$pro_id,$order_id,$channel='',$province,$uid,$order_type=1,$p_order_id=0){
        //普通订单，享受支付折扣
        if($order_type != 1)
        {
            return 0;
        }
        if(!$this->checkProIds($pro_id)){
            return 0;
        }
        $this->db->from("pay_discount");
        $this->db->where(array('pay_parent_id'=>$pay_parent_id,'pay_id'=>$pay_id));
        $now_date = time();
        $this->db->where(array("s_time <="=>$now_date,"e_time >="=>$now_date));
        $this->db->order_by('priority');
        $query = $this->db->get();
        $result = $query->result_array();
        $total_pay_discount = 0;
        $finish = false;
        $channel = $this->check_source($channel);
        $now_time = date('H:i:s');
        foreach ($result as $key => $value) {
            if($value['day_s_time'] && $value['day_s_time']!='00:00:00'){
                if(strtotime($now_time) < strtotime($value['day_s_time'])){
                    continue;
                }
            }
            if($value['day_e_time'] && $value['day_e_time']!='00:00:00'){
                if(strtotime($now_time) > strtotime($value['day_e_time'])){
                    continue;
                }
            }
            $send_region_arr = unserialize($value['send_region']);
            if(!$province || !in_array($province, $send_region_arr)){
                continue;
            }
            if($value['order_limit']>0){
                $total_num = $this->checkDiscountOrderNum($value['id']);
                if($total_num>=$value['order_limit']){
                    continue;
                }
            }
            if(!empty($value['channel'])){
                $channel_array = unserialize($value['channel']);
                if(!in_array($channel, $channel_array)){
                    continue;
                }
            }
            $detail_rules = unserialize($value['details_rules']);
            if(!$this->checkDetailRules($detail_rules,$uid,$value['id'],$order_id,$p_order_id)){
                continue;
            }
        	$rule = unserialize($value['discount_rule']);
            $pay_discount = 0;
        	switch ($rule['type']) {
        		case '1':
        			if($money>=$rule['pro_money']){
                        if($money>=$rule['cut_money']){
                            $pay_discount = $rule['cut_money'];
                        }else{
                            $pay_discount = $money;
                        }
                        if($value['priority']>0){
                            $finish = true;
                        }
        			}
        			break;
        		case '2':
        			if($money>=$rule['pro_money']){
        				$mobilecard_id = $rule['mobilecard_id'];
                        if($value['priority']>0){
                            $finish = true;
                        }
        			}
        			break;
        		case '3':
        		    if(!is_array($pro_id)){
        		    	$pro_id = explode(',', $pro_id);
        		    }
        			$rule_p_id = explode(',', $rule['product_id']);
        			if($rule_p_id && $pro_id){
        				if(array_intersect($pro_id,$rule_p_id)){
                            if($money>=$rule['pro_money']){
                                if($money>=$rule['cut_money']){
                                    $pay_discount = $rule['cut_money'];
                                }else{
                                    $pay_discount = $money;
                                }
                                if($value['priority']>0){
                                    $finish = true;
                                }
		        			}
        				}
        			}
        			break;
        		case '4':
                    if($money>=$rule['pro_money']){
                        $rule_p_id = explode(',', $rule['product_id']);
                        $res = $this->checkSaleProduct($order_id,$p_order_id,$rule_p_id,$rule['sale_money']);
                        if($res === true){
                            if($money>=$rule['cut_money']){
                                $pay_discount = $rule['cut_money'];
                            }else{
                                $pay_discount = $money;
                            }
                            if($value['priority']>0){
                                $finish = true;
                            }
                        }
                    }
                    break;
        		default:
        			return;
        			break;
        	}
            $total_pay_discount += $pay_discount;
            if($finish === true){
                break;
            }
        }
        if($total_pay_discount>$money){
            $total_pay_discount = $money;
        }
        return $total_pay_discount;
	}

    public function set_order_pay_discount($pay_parent_id,$pay_id,$money,$pro_id,$order_id=0,$channel='',$province,$uid,$order_type=1,$p_order_id=0){
        if(empty($order_id) && empty($p_order_id)){
            return 0;
        }

        //普通订单，享受支付折扣
        if($order_type != 1)
        {
            $this->initPayDiscount($order_id,$p_order_id);
            return 0;
        }

        if(!$this->checkProIds($pro_id)){
            $this->initPayDiscount($order_id,$p_order_id);
            return 0;
        }
        $this->db->from("pay_discount");
        $this->db->where(array('pay_parent_id'=>$pay_parent_id,'pay_id'=>$pay_id));
        $now_date = time();
        $this->db->where(array("s_time <="=>$now_date,"e_time >="=>$now_date));
        $this->db->order_by('priority');
        $query = $this->db->get();
        $result = $query->result_array();
        if(empty($result)){
            $this->initPayDiscount($order_id,$p_order_id);
            return 0;
        }
        $total_pay_discount = 0;
        $finish = false;
        $channel = $this->check_source($channel);
        $now_time = date('H:i:s');
        foreach ($result as $key => $value) {
            if($value['day_s_time'] && $value['day_s_time']!='00:00:00'){
                if(strtotime($now_time) < strtotime($value['day_s_time'])){
                    continue;
                }
            }
            if($value['day_e_time'] && $value['day_e_time']!='00:00:00'){
                if(strtotime($now_time) > strtotime($value['day_e_time'])){
                    continue;
                }
            }
            $send_region_arr = unserialize($value['send_region']);
            if(!$province || !in_array($province, $send_region_arr)){
                continue;
            }
            if($value['order_limit']>0){
                $total_num = $this->checkDiscountOrderNum($value['id']);
                if($total_num>=$value['order_limit']){
                    continue;
                }
            }
            if(!empty($value['channel'])){
                $channel_array = unserialize($value['channel']);
                if(!in_array($channel, $channel_array)){
                    continue;
                }
            }
            $detail_rules = unserialize($value['details_rules']);
            if(!$this->checkDetailRules($detail_rules,$uid,$value['id'],$order_id,$p_order_id)){
                continue;
            }
            $pay_discount = 0;
            $mobilecard_id = 0;
            $data = array();
            $rule = unserialize($value['discount_rule']);
            switch ($rule['type']) {
                case '1':
                    if($money>=$rule['pro_money']){
                        if($money>=$rule['cut_money']){
                            $pay_discount = $rule['cut_money'];
                        }else{
                            $pay_discount = $money;
                        }
                        if($value['priority']>0){
                            $finish = true;
                        }
                    }
                    break;
                case '2':
                    if($money>=$rule['pro_money']){
                        $mobilecard_id = $rule['mobilecard_id'];
                        if($value['priority']>0){
                            $finish = true;
                        }
                    }
                    break;
                case '3':
                    if(!is_array($pro_id)){
                        $pro_id = explode(',', $pro_id);
                    }
                    $rule_p_id = explode(',', $rule['product_id']);
                    if($rule_p_id && $pro_id){
                        if(array_intersect($pro_id,$rule_p_id)){
                            if($money>=$rule['pro_money']){
                                if($money>=$rule['cut_money']){
                                    $pay_discount = $rule['cut_money'];
                                }else{
                                    $pay_discount = $money;
                                }
                                if($value['priority']>0){
                                    $finish = true;
                                }
                            }
                        }
                    }
                    break;
                case '4':
                    if($money>=$rule['pro_money']){
                        $rule_p_id = explode(',', $rule['product_id']);
                        $res = $this->checkSaleProduct($order_id,$p_order_id,$rule_p_id,$rule['sale_money']);
                        if($res === true){
                            if($money>=$rule['cut_money']){
                                $pay_discount = $rule['cut_money'];
                            }else{
                                $pay_discount = $money;
                            }
                            if($value['priority']>0){
                                $finish = true;
                            }
                        }
                    }
                    break;
                default:
                    return;
                    break;
            }
            $data['discount_id'] = $value['id'];
            $data['order_id'] = $order_id;
            $data['p_order_id'] = $p_order_id;
            $data['cut_money'] = $pay_discount?$pay_discount:0;
            $data['mobilecard_id'] = $mobilecard_id?$mobilecard_id:0;
            $data['card_send'] = 0;
            $data['discount_type'] = $rule['type'];
            $data['time'] = date('Y-m-d H:i:s');
            $this->db->from("order_pay_discount");
            if($order_id){
                $this->db->where(array('order_id'=>$order_id,'discount_id'=>$value['id']));
            }elseif($p_order_id){
                $this->db->where(array('p_order_id'=>$p_order_id,'discount_id'=>$value['id']));
            }
            
            if($this->db->count_all_results() > 0){
                if($pay_discount>0 || $mobilecard_id>0){
                    if($order_id){
                        $this->db->update('order_pay_discount',$data,array('order_id'=>$order_id,'discount_id'=>$value['id']));
                    }else{
                        $this->db->update('order_pay_discount',$data,array('p_order_id'=>$p_order_id,'discount_id'=>$value['id']));
                    }
                }else{
                    if($order_id){
                        $this->db->where(array('order_id'=>$order_id,'discount_id'=>$value['id']));
                        $this->db->delete('order_pay_discount');
                    }else{
                        $this->db->where(array('p_order_id'=>$p_order_id,'discount_id'=>$value['id']));
                        $this->db->delete('order_pay_discount');
                    }
                }
            }else{
                if($pay_discount>0 || $mobilecard_id>0){
                    $this->db->insert('order_pay_discount',$data);
                }
            }
            $total_pay_discount += $pay_discount;
            if($finish===true){
                break;
            }
        }
        if($total_pay_discount>$money){
            $total_pay_discount = $money;
        }
        if($order_id){
            $this->db->update('order',array('new_pay_discount'=>$total_pay_discount),array('id'=>$order_id));
        }else{
            $this->db->update('b2o_parent_order',array('new_pay_discount'=>$total_pay_discount),array('id'=>$p_order_id));
        }
        return $total_pay_discount;
    }

    function pay_discout_send_card($order_id,$uid){
        $this->load->model('card');
        $Pdiscount = $this->db->select('*')
                              ->from('order_pay_discount')
                              ->where('order_id',$order_id)
                              ->where('discount_type','2')
                              ->where('card_send','0')
                              ->get()
                              ->result_array();
        foreach ($Pdiscount as $key => $value) {
            $card = array();
            $cardTmpl = $this->db->select('*')
                             ->from('mobile_card')
                             ->where('card_type','11')
                             ->where('id',$value['mobilecard_id'])
                             ->where('time <=',date('Y-m-d'))
                             ->where('to_date >=',date('Y-m-d'))
                             ->get()
                             ->result_array();
            if($cardTmpl){
                $tmpl = $cardTmpl[0];
                $card = array(
                   'sendtime'             => date("Y-m-d"),
                   'card_money'           => $tmpl['card_money'],
                   'product_id'           => $tmpl['product_id'],
                   'maketing'             => '0',
                   'is_sent'              => '1',
                   'restr_good'           => $tmpl['restr_good'],
                   'remarks'              => $tmpl['remarks'],
                   'time'                 => date('Y-m-d H:i:s'),
                   'to_date'              => date('Y-m-d H:i:s',strtotime("+{$tmpl['validity']} day")),
                   'can_use_onemore_time' => 'false',
                   'can_sales_more_times' => $tmpl['can_sales_more_times'],
                   'card_discount'        => 1,
                   'order_money_limit'    => $tmpl['order_money_limit'],
                   'uid'                  => $uid,
                   'direction'            => $tmpl['direction'],
                );
                $cards = $this->card->gen_card($card);
                $cards and $this->db->update('order_pay_discount',array('card_send'=>'1'),array('id'=>$value['id']));
            }
        }
    }

    function getPayDiscountView($pay_parent_id,$pay_id=0,$channel=''){
        $this->db->from("pay_discount");
        $now_date = time();
        $this->db->where(array("s_time <="=>$now_date,"e_time >="=>$now_date));
        $this->db->where(array("pay_parent_id ="=>$pay_parent_id));
        $pay_id and $this->db->where(array("pay_id ="=>$pay_id));
        $query = $this->db->get();
        $result = $query->result_array();
        $rules = array();
        $pay_array  =  $this->config->item("pay_array");
        $channel = $this->check_source($channel);
        foreach ($result as $key => $value) {
            if($value['order_limit']>0){
                $total_num = $this->checkDiscountOrderNum($value['id']);
                if($total_num>=$value['order_limit']){
                    continue;
                }
            }
            if(!empty($value['channel'])){
                $channel_array = unserialize($value['channel']);
                if(!in_array($channel, $channel_array)){
                    continue;
                }
            }
            $details_rules = unserialize($value['details_rules']);
            $msg = '';
            if($details_rules['day_limit']){
                $where = " and o.time >= '".date('Y-m-d',time())."'";
                $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$value['id'].$where;
                $query = $this->db->query($sql);
                $result1 = $query->row_array();

                $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$value['id'].$where;
                $query = $this->db->query($sql);
                $result2 = $query->row_array();
                $total_num = $result1['total_num'] + $result2['total_num'];
                if($total_num>=$details_rules['day_limit']){
                    $msg = ' 今日名额已满';
                }
            }
            if($details_rules['week_limit']){
                $where = " and o.time >= '".date('Y-m-d',(time()-((date('w')==0?7:date('w'))-1)*86400))."'";
                $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$value['id'].$where;
                $query = $this->db->query($sql);
                $result1 = $query->row_array();

                $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$value['id'].$where;
                $query = $this->db->query($sql);
                $result2 = $query->row_array();
                $total_num = $result1['total_num'] + $result2['total_num'];
                
                if($total_num>=$details_rules['week_limit']){
                    $msg = ' 本周名额已满';
                }
            }
            $value['remarks'] and $rules[] = $value['remarks'].$msg;
        }
        return $rules;
    }

    function checkProIds($pro_id){
        if(!is_array($pro_id)){
            $pro_id = explode(',', $pro_id);
        }
        if(empty($pro_id)){
            return false;
        }
        $this->db->from("product");
        $this->db->where(array("pay_discount_limit"=>1));
        $this->db->where_in("id",$pro_id);
        if($this->db->count_all_results() > 0){
            return false;
        }
        return true;
    }

    function checkDiscountOrderNum($discount_id){
        $discount_key = "pay_discount_".$discount_id;
        $total_num = $this->redis->get($discount_key);
        if($total_num){

        }else{
            $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $total_num1 = $result['total_num'];

            $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $total_num2 = $result['total_num'];
            $total_num = $total_num1 + $total_num2;
            // $this->redis->set($discount_key,$total_num);
            // $this->redis->expire($discount_key,3600*24);
        }
        return $total_num;
    }

    function initPayDiscount($order_id=0,$p_order_id=0){
        if($order_id){
            $this->db->where(array('order_id'=>$order_id));
            $this->db->delete('order_pay_discount');
            $this->db->update('order',array('new_pay_discount'=>0),array('id'=>$order_id));
        }
        if($p_order_id){
            $this->db->where(array('p_order_id'=>$p_order_id));
            $this->db->delete('order_pay_discount');
            $this->db->update('b2o_parent_order',array('new_pay_discount'=>0),array('id'=>$p_order_id));
        }
    }

    private function check_source($source){
        $channel = 0;
        switch ($source) {
            case 'app':
                $channel = 2;
                break;
            case 'wap':
                $channel = 3;
                break;
            case 'pc':
                $channel = 1;
                break;
            default:
                # code...
                break;
        }
        return $channel;
    }

    function checkDetailRules($detail_rules,$uid,$discount_id,$order_id=0,$p_order_id=0){
        if($detail_rules['week_day']){
            if(date("w") != $detail_rules['week_day']){
                if(!(date("w")==0 && $detail_rules['week_day'] == 7)){
                    return false;
                }
            }
        }
        if($detail_rules['user_time_limit']['user_limit']){
            $where = '';
            switch ($detail_rules['user_time_limit']['user_time']) {
                case 0:
                    # code...
                    break;
                case 1:
                    $where = " and o.time >= '".date('Y-m-d',time())."'";
                    break;
                case 2:
                    $where = " and o.time >= '".date('Y-m-d',(time()-((date('w')==0?7:date('w'))-1)*86400))."'";
                    break;
                case 3:
                    $where = " and o.time >= '".date('Y-m',time())."-01'";
                    break;
                default:
                    # code...
                    break;
            }
            if($order_id!=0){
                $where .= " and o.id<>".$order_id;
            }
            $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and o.uid=".$uid." and p.discount_id=".$discount_id.$where;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $count1 = $result['total_num']; 

            if($p_order_id!=0){
                $where .= " and o.id<>".$p_order_id;
            }
            $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and o.uid=".$uid." and p.discount_id=".$discount_id.$where;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $count2 = $result['total_num']; 
            $count = $count1 + $count2;
            if($count>=$detail_rules['user_time_limit']['user_limit']){
                return false;
            }
        }
        if($detail_rules['user_time_limit']['user_all_limit'] && $detail_rules['user_time_limit']['user_all_limit'] > 0 ){
            $where = '';
            if($order_id!=0){
                $where .= " and o.id<>".$order_id;
            }
            $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and o.uid=".$uid." and p.discount_id=".$discount_id.$where;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $count1 = $result['total_num']; 

            if($p_order_id!=0){
                $where .= " and o.id<>".$p_order_id;
            }
            $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and o.uid=".$uid." and p.discount_id=".$discount_id.$where;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $count2 = $result['total_num']; 
            $count = $count1 + $count2;
            if($count>=$detail_rules['user_time_limit']['user_all_limit']){
                return false;
            }
        }
        if($order_id){
            $sql = "select id from ttgy_order_pay_discount where order_id=".$order_id." and discount_id=".$discount_id;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            if($result){
                return true;
            }
        }
        if($p_order_id){
            $sql = "select id from ttgy_order_pay_discount where p_order_id=".$p_order_id." and discount_id=".$discount_id;
            $query = $this->db->query($sql);
            $result = $query->row_array();
            if($result){
                return true;
            }
        }
        
        if($detail_rules['day_limit']){
            $day_key = date('Y-m-d',time())."-".$discount_id;
            $day_total_num = $this->redis->get($day_key);
            if($day_total_num){

            }else{
                $where = " and o.time >= '".date('Y-m-d',time())."'";
                // if($order_id!=0){
                //     $where .= " and o.id<>".$order_id;
                // }
                $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
                $query = $this->db->query($sql);
                $result = $query->row_array();
                $day_total_num1 = $result['total_num']?$result['total_num']:0;

                $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
                $query = $this->db->query($sql);
                $result = $query->row_array();
                $day_total_num2 = $result['total_num']?$result['total_num']:0;

                $day_total_num = $day_total_num1 + $day_total_num2;
                // $this->redis->set($day_key,$day_total_num);
                // $this->redis->expire($day_key,3600*24);
            }
            if($day_total_num>=$detail_rules['day_limit']){
                return false;
            }
        }
        if($detail_rules['week_limit']){
            $week_key = date('Y').'-'.date('W')."-week-".$discount_id;
            $week_total_num = $this->redis->get($week_key);
            if($week_total_num){

            }else{
                $where = " and o.time >= '".date('Y-m-d',(time()-((date('w')==0?7:date('w'))-1)*86400))."'";
                // if($order_id!=0){
                //     $where .= " and o.id<>".$order_id;
                // }
                $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
                $query = $this->db->query($sql);
                $result = $query->row_array();
                $week_total_num1 = $result['total_num']?$result['total_num']:0;

                $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
                $query = $this->db->query($sql);
                $result = $query->row_array();
                $week_total_num2 = $result['total_num']?$result['total_num']:0;

                $week_total_num = $week_total_num1 + $week_total_num2;
                // $this->redis->set($week_key,$week_total_num);
                // $this->redis->expire($week_key,3600*24*7);
            }
            if($week_total_num>=$detail_rules['week_limit']){
                return false;
            }
        }
        return true;
    }

    function checkOrderPayDiscount($order_id){
        $sql = "select sum(od.cut_money) as total_pay_discount,o.money,o.new_pay_discount from ttgy_order_pay_discount od join ttgy_order o on od.order_id=o.id join ttgy_pay_discount d on d.id=od.discount_id where o.id=".$order_id." and d.pay_parent_id=o.pay_parent_id and d.pay_id=o.pay_id";
        $query = $this->db->query($sql);
        $result = $query->row_array();
        if($result && $result['total_pay_discount']>0){
            $money = bcsub(bcadd($result['money'], $result['new_pay_discount'],2),$result['total_pay_discount'],2);
            $this->db->update('order',array('new_pay_discount'=>$result['total_pay_discount'],'money'=>$money),array('id'=>$order_id));
        }else{
            $money = bcadd($result['money'], $result['new_pay_discount'],2);
            $result['new_pay_discount']>0 and $this->db->update('order',array('new_pay_discount'=>0,'money'=>$money),array('id'=>$order_id,'new_pay_discount >'=>'0'));
        }
        return true;
    }

    private function checkSaleProduct($order_id,$p_order_id,$sale_pids,$sale_money){
        $this->load->library('orderredis');
        $this->orderredis = $this->orderredis->getConn();
        if($p_order_id){
            $op_info = $this->orderredis->get('p_sale_pro_discount-'.$p_order_id);
            $op_info and $op_info = json_decode($op_info,true);
            if($op_info && $op_info[0]['product_id'] && $op_info[0]['total_money']){

            }else{
                $op_info = $this->db->select('product_id,total_money')
                                ->from('b2o_parent_order_product')
                                ->where('order_id',$p_order_id)
                                ->where('type','1')
                                ->get()->result_array();
                $this->orderredis->set('p_sale_pro_discount-'.$p_order_id,json_encode($op_info));
                $this->orderredis->expire('p_sale_pro_discount-'.$p_order_id,3600);
            }
        }else{
            $op_info = $this->orderredis->get('sale_pro_discount-'.$order_id);
            $op_info and $op_info = json_decode($op_info,true);
            if($op_info && $op_info[0]['product_id'] && $op_info[0]['total_money']){

            }else{
                $op_info = $this->db->select('product_id,total_money')
                                    ->from('order_product')
                                    ->where('order_id',$order_id)
                                    ->where('type','1')
                                    ->get()->result_array();
                $this->orderredis->set('sale_pro_discount-'.$order_id,json_encode($op_info));
                $this->orderredis->expire('sale_pro_discount-'.$order_id,3600);
            }
        }
        if(empty($op_info)) return false;
        $op_money = 0;
        if(!is_array($sale_pids)) $sale_pids = explode(',', $sale_pids);
        foreach ($op_info as $op) {
            if(in_array($op['product_id'], $sale_pids)){
                $op_money = bcadd($op_money, $op['total_money'], 2);
            }
        }
        if(bccomp($op_money, $sale_money, 2) != -1){
            return true;
        }else{
            return false;
        }
    }
}

