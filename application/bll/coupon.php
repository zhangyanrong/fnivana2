<?php
namespace bll;

class Coupon
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 送优惠劵
     *
     * @return void
     * @author 
     **/
    public function send_coupon($coupon)
    {
        if (!$coupon || $coupon['money'] == '' || $coupon['created_by'] == '' || $coupon['secret'] != 'MZMWMYT TYMWMZM') return false;


        $coupon['card_number'] = $coupon['card_number'] ? $coupon['card_number'] : $this->gen_code();
        $rs = $this->ci->db->insert("card", 
            array(
                'uid' => $coupon['uid'], 
                'card_number'   => $coupon['card_number'], 
                'card_money'    => $coupon['money'], 
                'maketing'      => $coupon['source'], 
                'is_used'       => $coupon['has_used'], 
                'is_sent'       => $coupon['has_sent'], 
                'remarks'       => urldecode($coupon['notes']), 
                'time'          => $coupon['created_at'], 
                'to_date'       => $coupon['expired_at'], 
                'max_use_times' => array_key_exists('max_use_times', $coupon) ? $coupon['max_use_times'] : 1,
                'min_spending'  => array_key_exists('min_spending', $coupon) ? $coupon['min_spending'] : 0,
            )
        );


        if($rs && $coupon['mobile'] ) {
            $this->ci->load->model("jobs_model");
            $job = array(
                'mobile' => $coupon['mobile'],
                'text' => "您".$coupon['notes'].$coupon['money']."元优惠券，券号:".$coupon['card_number']."，使用截止日期:".$coupon['expired_at'],
            );
            $this->ci->jobs_model->add($job,"sms");
        }

        return $rs ? true : false;
    }

    public function gen_code($len=10)
    {
        $number = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $number = explode(",",$number);
        
        $code = '';
        for($i=0;$i<$len;$i++){
            $k = rand(0,31);
            $code .= $number[$k];
        }
        
        // 验证是否存在
        $card = $this->ci->db->from("card")
                             ->where(array("card_number" => $code))
                             ->get()
                             ->row_array();
        if ($card) {
            return $this->gen_code($len);
        }

        return $code;
    }
}