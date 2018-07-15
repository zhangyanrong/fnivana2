<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Region extends wap{

	function __construct($params=array()){
		$this->ci = &get_instance();
	}
	
	function getPay($params){
		/*合法支付方式配置start*/
//		$allow_pay = array(
//			'1','3','4','5','7','9','8'
//		);
        $allow_pay = array(
			'1','3','4','5','7','9'
		);
		/*合法支付方式配置end*/
		$result = parent::call_bll($params);
		foreach ($result as $key => $value) {
			if(!in_array($value['pay_parent_id'], $allow_pay)){
				unset($result[$key]);
			}
		}

        if(isset($result['user_money'])){
            $user_money = $result['user_money'];
            unset($result['user_money']);
        }

		$pay_arr = array();
            foreach ($result as $value) {
                switch ($value['pay_parent_id']) {
                    case '1':
                        $pay_key = 'online';
                        $pay_arr['online']=array(
                                    'name'=>'在线支付',
                                    'pays'=>array(),
                                );
                        break;
                    case '3':
                        $pay_key = 'bank';
                        $pay_arr['bank']=array(
                                    'name'=>'网上银行支付',
                                    'pays'=>array(),
                                );
                        break;
                    case '5':
                        $pay_key = 'fday';
                        $pay_arr['fday']=array(
                                    'name'=>'帐户余额支付',
                                    'pays'=>array(),
                                );
                        break;
                    case '4':
                        $pay_key = 'offline';
                        $pay_arr['offline']=array(
                                    'name'=>'线下支付',
                                    'pays'=>array(),
                                );
                        break;
                    case '7':
                        $pay_key = 'online';
                        // $pay_arr['online']=array(
                        //             'name'=>'在线支付',
                        //             'pays'=>array(),
                        //         );
                        break;
//                    case '8':
//                        $pay_key = 'online';
//                        break;
                    default:
                        $pay_key = '';
                        break;
                }
                if($pay_key){
                    $pay_tmp = array();
                    foreach ($value['son'] as $v) {
                        $pay_tmp['pay_parent_id'] = $value['pay_parent_id'];
                        $pay_tmp['pay_id'] = $v['pay_id'];
                        $pay_tmp['pay_name'] = $v['pay_name'];
                        $pay_tmp['has_invoice'] = $v['has_invoice'];
                        $pay_tmp['icon'] = $v['icon'];
                        $pay_tmp['discount_rule'] = $v['discount_rule'];
                        $pay_arr[$pay_key]['pays'][] = $pay_tmp;
                    }
                }
            }

        /*招商一网通*/
        // foreach($pay_arr['bank']['pays'] as $key=>$val)
        // {
        //     if($val['pay_id'] == '00108')
        //     {
        //         unset($pay_arr['bank']['pays'][$key]);
        //     }
        // }

        /*余额改造 start*/
//        if(strcmp($params['version'], '3.4.0') >= 0)
//        {
//            foreach($pay_arr['bank']['pays'] as $val)
//            {
//                $pay_arr['online']['pays'][] = $val;
//            }
//            unset($pay_arr['bank']);
//            unset($pay_arr['fday']);
//        }
        /*余额改造 end*/

		return $pay_arr;
	}
}