<?php
class Trade_model extends MY_Model {

    public function table_name(){
        return 'trade';
    }

    /*
     * 生成充值订单
     */
    public function generate_trade($trade_info){
        $trade_number = 'T'.date("ymdi").rand_code(4);
        $struct = $this->trade_init_struct();
        $trade_data = array();
        foreach ($struct as $value) {
            if($value == 'trade_number'){
                $trade_data['trade_number'] = $trade_number;
            }elseif(isset($trade_info[$value])){
                $trade_data[$value] = $trade_info[$value];
            }
        }
        $res = $this->db->insert('trade',$trade_data);
        if(! $res){
            $trade_number = $this->generate_trade($trade_info);
        }
        return $trade_number;
    }

    private function trade_init_struct(){
        $struct = array(
            'uid','trade_number','out_trade_no','payment','money','card_number','trade_no','status','type','time','post_at','has_deal','region','order_name','refund_id'
        );
        return $struct;
    }
}