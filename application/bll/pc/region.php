<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Region extends pc{

	function __construct($params=array()){
		$this->ci = &get_instance();

        $this->ci->load->model("region_model");
        $this->ci->load->model("pay_discount_model");
        $this->ci->load->helper('public');
	}

    private function is_pay_wd($province){
        $area_refelect = $this->ci->config->item("area_refelect");
        if(in_array($province, $area_refelect['1']) || in_array($province, $area_refelect['5'])){
            return false;
        }else{
            return true;
        }
    }

    private function has_invoice($pay_parent=0, $pay_son=0) {
        $current_payment = $pay_parent;
        if ($pay_son) {
            $current_payment = $current_payment . '-' . $pay_son;
        }
        $payments = $this->ci->config->item('no_invoice');
        return array_key_exists($current_payment, $payments) ? 0 : 1;
    }

    /*
     * 支付方式
     */
    function getPay($params)
    {
        $result = parent::call_bll($params);

        foreach($result as $key=>$val)
        {
            if($val['pay_parent_id'] == 3)
            {
                foreach($result[$key]['son'] as $k=>$v)
                {
                    if($v['pay_id'] == '00108')   //一网通
                    {
                        unset($result[$key]['son'][$k]);
                    }

                    if($v['pay_id'] == '00103')  //交通银行信用卡
                    {
                        unset($result[$key]['son'][$k]);
                    }
                }
            }
        }

        return $result;
    }

}