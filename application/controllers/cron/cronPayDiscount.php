<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CronPayDiscount extends CI_Controller {
	function __construct(){
		parent::__construct ();
		$this->load->helper('public');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
	}

	function discountOrderLimit(){
        $now_date = time();
        $this->db->from("pay_discount");
        $this->db->where(array("s_time <="=>$now_date,"e_time >="=>$now_date));
        $query = $this->db->get();
        $result = $query->result_array();
        foreach ($result as $key => $value) {
            $this->setOrderLimit($value['id']);
            $this->setOrderDayLimit($value['id']);
            $this->setOrderWeekLimit($value['id']);
        }
    }

    function setOrderLimit($discount_id){
        $discount_key = "pay_discount_".$discount_id;
        $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $total_num1 = $result['total_num'];
        $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $total_num2 = $result['total_num'];
        $total_num = $total_num1 + $total_num2;
        $this->redis->set($discount_key,$total_num);
        $this->redis->expire($discount_key,3600*24);
        return $total_num;
    }

    function setOrderDayLimit($discount_id){
        $day_key = date('Y-m-d',time())."-".$discount_id;
        $where = " and o.time >= '".date('Y-m-d 23:30:00',strtotime('-1 day'))."'";
        $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $day_total_num1 = $result['total_num']?$result['total_num']:0;
        $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $day_total_num2 = $result['total_num']?$result['total_num']:0;
        $day_total_num = $day_total_num1 + $day_total_num2;
        $this->redis->set($day_key,$day_total_num);
        $this->redis->expire($day_key,3600*24);
        return $day_total_num;
    }

    function setOrderWeekLimit($discount_id){
        $week_key = date('Y').'-'.date('W')."-week-".$discount_id;
        $where = " and o.time >= '".date('Y-m-d',(time()-((date('w')==0?7:date('w'))-1)*86400))."'";
        $sql = "select count(o.id) as total_num from ttgy_order o join ttgy_order_pay_discount p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $week_total_num1 = $result['total_num']?$result['total_num']:0;
        $sql = "select count(o.id) as total_num from ttgy_b2o_parent_order o join ttgy_order_pay_discount p on o.id=p.p_order_id where o.order_status=1 and o.operation_id<>5 and p.discount_id=".$discount_id.$where;
        $query = $this->db->query($sql);
        $result = $query->row_array();
        $week_total_num2 = $result['total_num']?$result['total_num']:0;
        $week_total_num = $week_total_num1 + $week_total_num2;
        $this->redis->set($week_key,$week_total_num);
        $this->redis->expire($week_key,3600*24*7);
        return $week_total_num;
    }

    public function checkDiscountLimit(){
        $now_date = time();
        $this->db->from("pay_discount");
        $this->db->where(array("s_time <="=>$now_date,"e_time >="=>$now_date));
        $query = $this->db->get();
        $result = $query->result_array();
        $send_sms = array();
        $pay_array  =  $this->config->item("pay_array");
        foreach ($result as $key => $value) {
            $rules = unserialize($value['detail_rules']);
            $checkOrderLimit = $this->checkOrderLimit($value['id'],$value['order_limit']);
            $checkOrderDayLimit = $this->checkOrderDayLimit($value['id'],$rules['day_limit']);
            $checkOrderWeekLimit = $this->checkOrderWeekLimit($value['id'],$rules['week_limit']);
            if($pay_array[$value['pay_parent_id']]['son']){
                $pay_name = $pay_array[$value['pay_parent_id']]['son'][$value['pay_id']];
            }else{
                $pay_name = $pay_array[$value['pay_parent_id']]['name'];
            }
            if($checkOrderLimit === true){
                $send_sms[] = $value['id'].':'.$pay_name.":总单量已超1%".date('Y-m-d H:i:s');
            }
            if($checkOrderDayLimit === true){
                $send_sms[] = $value['id'].':'.$pay_name.":日单量已超1%".date('Y-m-d H:i:s');
            }
            if($checkOrderWeekLimit === true){
                $send_sms[] = $value['id'].':'.$pay_name.":周单量已超1%".date('Y-m-d H:i:s');
            }
        }
        if($send_sms){
            $this->load->library("notifyv1");
            $send_sms_arr = array_chunk($send_sms, 6);
            foreach ($send_sms_arr as $sms) {
                $send_params = array();
                $message = implode('、', $sms);
                $send_params['mobile'] = array('13524780797','13671981025','15002189415');
                $send_params['message'] = $message;
                $this->notifyv1->send('sms','group',$send_params);
            }
        }
    }

    private function checkOrderLimit($discount_id,$limit=1){
        $total_num = $this->setOrderLimit($discount_id);
        if($total_num > $limit){
            $check_discount_key = "check_pay_discount_".$discount_id;
            $last_check_nums = $this->redis->get($check_discount_key);
            if($last_check_nums && $last_check_nums>0){
                if(bccomp(bcdiv(bcsub($total_num, $last_check_nums,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }else{
                if(bccomp(bcdiv(bcsub($total_num, $limit,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }
        }
        return false;
    }

    private function checkOrderDayLimit($discount_id,$limit=1){
        $total_num = $this->setOrderDayLimit($discount_id);
        if($total_num > $limit){
            $check_discount_key = "check_".date('Y-m-d',time())."-".$discount_id;
            $last_check_nums = $this->redis->get($check_discount_key);
            if($last_check_nums && $last_check_nums>0){
                if(bccomp(bcdiv(bcsub($total_num, $last_check_nums,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }else{
                if(bccomp(bcdiv(bcsub($total_num, $limit,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }
        }
        return false;
    }

    private function checkOrderWeekLimit($discount_id,$limit=1){
        $total_num = $this->setOrderWeekLimit($discount_id);
        if($total_num > $limit){
            $check_discount_key = "check_".date('Y').'-'.date('W')."-week-".$discount_id;
            $last_check_nums = $this->redis->get($check_discount_key);
            if($last_check_nums && $last_check_nums>0){
                if(bccomp(bcdiv(bcsub($total_num, $last_check_nums,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }else{
                if(bccomp(bcdiv(bcsub($total_num, $limit,2),$limit,2),0.01,2) >= 0){
                    $this->redis->set($check_discount_key,$total_num);
                    return true;
                }
            }
        }
        return false;
    }
}
