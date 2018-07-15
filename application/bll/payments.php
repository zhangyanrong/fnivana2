<?php
namespace bll;

class Payments {

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    private function is_pay_wd($province_id){
        $area_refelect = $this->ci->config->item("area_refelect");

        return in_array($province_id,$area_refelect['1']) ? false : true;
    }

    /**
     * 获取支付方式配置
     *
     * @return void
     * @author 
     **/
    public function getMethods($province_id)
    {
        $this->ci->load->library('terminal');
        $source = $this->ci->terminal->get_source();

        $this->ci->load->model('pay_discount_model');

        $pay_array  =  $this->ci->config->item("pay_array");

        //非自建物流不能线下支付start
        $is_pay_wd = $this->is_pay_wd($province_id);
        if(($is_pay_wd === true) && $pay_array['4']['name']=="线下支付"){
            unset($pay_array['4']);
        }
        //非自建物流不能线下支付end

        $methods = array(); $i = 0;
        foreach ($pay_array as $key => $value) {

            $methods[$i]['pay_parent_id']   = $key;
            $methods[$i]['pay_parent_name'] = $value['name'];
            $discount_rule = array();
            if(!empty($value['son'])){
                $j = 0;
                foreach ($value['son'] as $k => $v) {
                    $methods[$i]['son'][$j]['pay_id']      = $k;
                    $methods[$i]['son'][$j]['pay_name']    = $v;
                    $methods[$i]['son'][$j]['has_invoice'] = $this->has_invoice($key,$k);
                    $methods[$i]['son'][$j]['icon']        = PIC_URL.'assets/images/bank/app/'.$key.'_'.$k.'.png';

                    //$discount_rule = $this->ci->pay_discount_model->getPayDiscountView($key,$k,$source);
                    $discount_rule_tmp = '';
                    if(!empty($discount_rule) && is_array($discount_rule)){
                        foreach ($discount_rule as $dr_k => $dr_v) {
                            $discount_rule_tmp .= $dr_v.'；';
                        }
                    }
                    $discount_rule and $methods[$i]['son'][$j]['discount_rule'] = trim($discount_rule_tmp,'；');
                    $j++;
                }
            }else{
                $methods[$i]['son'][0]['pay_id']      = 0;
                $methods[$i]['son'][0]['pay_name']    = $value['name'];
                $methods[$i]['son'][0]['has_invoice'] = $this->has_invoice($key,0);
                $methods[$i]['son'][0]['icon']        = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$key.'_0.png';

                //$discount_rule = $this->ci->pay_discount_model->getPayDiscountView($key,0,$source);

                $discount_rule_tmp = '';
                if(!empty($discount_rule) && is_array($discount_rule)){
                    foreach ($discount_rule as $dr_k => $dr_v) {
                        $discount_rule_tmp .= $dr_v.'；';
                    }
                }
                $discount_rule and $methods[$i]['son'][0]['discount_rule'] = trim($discount_rule_tmp,'；');
            }
            $i++;
        }
        
        return $methods;
    }

    private function has_invoice($pay_parent=0, $pay_son=0) {
        $current_payment = $pay_parent;
        if ($pay_son) {
            $current_payment = $current_payment . '-' . $pay_son;
        }
        $payments = $this->ci->config->item('no_invoice');
        return array_key_exists($current_payment, $payments) ? 0 : 1;
    }

}