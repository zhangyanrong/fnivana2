<?php
namespace bll\app;
include_once("app.php");
/**
* 商品相关接口
*/
class Region extends app{

	function __construct($params=array()){
		$this->ci = &get_instance();
	}
	
	function getPay($params){
		/*合法支付方式配置start*/
        if($params['platform'] == 'IOS' && strcmp($params['version'], '3.6.0') >= 0)
        {
            $allow_pay = array(
                '1','3','4','5','7','8','11','12','13','15'
            );
        }
        else
        {
            $allow_pay = array(
                '1','3','4','5','7','8','12','13','15'
            );
        }
		/*合法支付方式配置end*/
		$result = parent::call_bll($params);
        if(isset($result['code']) && $result['code']!='200'){
            return $result;
        }
        if(isset($result['user_money'])){
            $user_money = $result['user_money'];
            unset($result['user_money']);
        }
		foreach ($result as $key => $value) {
			if(!in_array($value['pay_parent_id'], $allow_pay)){
				unset($result[$key]);
			}
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
                    case '8':
                        $pay_key = 'online';
                        break;
                    case '11':
                        $pay_key = 'online';
                        break;
                    case '12':
                        $pay_key = 'online';
                        break;
                    case '13':
                        $pay_key = 'online';
                        break;
                    case '15':
                        $pay_key = 'online';
                        break;
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
                        $pay_tmp['no_invoice_message'] = $v['no_invoice_message'];
                        $pay_tmp['icon'] = $v['icon'];
                        $pay_tmp['discount_rule'] = $v['discount_rule'];
                        $pay_tmp['prompt'] = '';

                        //默认选中－支付宝
                        if($value['pay_parent_id'] == 1)
                        {
                            $pay_tmp['discount_rule'] ='推荐使用';
                        }
                        else if($value['pay_parent_id'] == 7)
                        {
                            $pay_tmp['is_select'] = 1;
                            $pay_tmp['discount_rule'] ='推荐使用';
                        }
                        else
                        {
                            $pay_tmp['is_select'] = 0;
                        }

                        if($v['pay_id'] == '00108')
                        {
                            // $pay_tmp['discount_rule'] ='一网通新用户首次支付享随机立减，最高99元';
                            // $pay_tmp['prompt'] = '(支付优惠会在合作方页面扣除)';
                        }

                        if($value['pay_parent_id'] == 8)
                        {
                            //$pay_tmp['discount_rule'] ='62建行龙卡银联信用卡满162减62，11月25日专享，名额有限';
                            // $pay_tmp['prompt'] = '(支付优惠会在合作方页面扣除)';
                        }
                        
                        if($value['pay_parent_id'] == 11)
                        {
                            $pay_tmp['discount_rule'] ='京东白条闪付每周一次满150减30，每日一次随机立减（10/23-11/30）';
                            // $pay_tmp['prompt'] = '(支付优惠会在合作方页面扣除)';
                        }

                        if(!empty($user_money)){
                            $pay_tmp['user_money'] = $user_money;
                        }
                        $pay_arr[$pay_key]['pays'][] = $pay_tmp;
                    }
                }
            }

        /*招商一网通*/
        if(strcmp($params['version'], '3.9.0') == 0)
        {
            foreach($pay_arr['bank']['pays'] as $key=>$val)
            {
                if($val['pay_id'] == '00108')
                {
                    unset($pay_arr['bank']['pays'][$key]);
                }
            }
        }

        /*余额改造 start*/
        if(strcmp($params['version'], '3.4.0') >= 0)
        {
            foreach($pay_arr['bank']['pays'] as $val)
            {
                $pay_arr['online']['pays'][] = $val;
            }
            unset($pay_arr['bank']);
            unset($pay_arr['fday']);

            //微信排序
            $z_pay =  $pay_arr['online']['pays'][0];
            $pay_arr['online']['pays'][0] = $pay_arr['online']['pays'][1];
            $pay_arr['online']['pays'][1] = $z_pay;
            
            //Apple pay排序
            foreach($pay_arr['online']['pays'] as $k=>$v)
            {
                if($v['pay_parent_id'] == '11')
                {
                    unset($pay_arr['online']['pays'][$k]);
                    array_splice($pay_arr['online']['pays'],5,0,array(0=>$v));
                    break;
                }
            }

             //招商排序
            /*foreach($pay_arr['online']['pays'] as $k=>$v)
            {
                if($v['pay_id'] == '00108')
                {
                    array_splice($pay_arr['online']['pays'],3,0,array(0=>$v));
                }
            }*/

            // $last_key = count($pay_arr['online']['pays'])-1;
            // unset($pay_arr['online']['pays'][$last_key]);
        }
        /*余额改造 end*/

		return $pay_arr;
	}

    /*
     * 配送时间－New
     */
    function getSendTime($params)
    {
        $result = parent::call_bll($params);

        if(strcmp($params['version'],'4.0.0') >= 0)
        {
            if(!empty($result))
            {
                //过滤
                foreach($result as $key=>$val)
                {
                    foreach($val['time'] as $k=>$v)
                    {
                        if($v['disable'] == 'false')
                        {
                            unset($result[$key]['time'][$k]);
                        }
                        else{
                            $result[$key]['times'][] = $result[$key]['time'][$k];
                        }
                    }
                    $result[$key]['time'] = $result[$key]['times'];
                    unset($result[$key]['times']);
                }
                
                //筛选
                foreach($result as $key=>$val)
                {
                    if (count($result[$key]['time']) == 1 && $result[$key]['time'][0]['disable'] == 'false') {
                        unset($result[$key]);
                    }
                }

                //重组
                $rs = array();
                $result = array_merge($rs,$result);
            }
        }

        return $result;
    }

}