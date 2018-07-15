<?php

/**
* @支付宝蚁盾风控接口
* @手机号Rain评分服务
*/
class ebuckler {

    private $ci;
    
    private $service;
    
    private $user_table = 'user';
    
    private $order_table = 'order';
    
    private $card_table = 'card';
    
    private $score_table = 'mobile_score';
    
    private $active_table = 'wqbaby_active';
    
    private $product_table = 'order_product';
    
    private $active_score_table = 'mobile_score_card';
    
    private $accessKey = '9W9F8AIg4sW1x722';
    
    private $accessSecret = '3VrkjOOuWpHJHsXg2wb2nb9Mj63oP8tP';
    
    private $customer = 'TTXG';
    
    private $method = 'alipay.ebuckler.mobile.rain.score.get';
    
    private $gateway = 'http://ebucklerapi.alipay.com/gateway.do';

    
	public function __construct()
    {
        $this->ci = & get_instance();
	}
    
    
    /***
     *@ 手机号Rain评分服务
     * mobile 手机号【Y】
     * from 请求来源【Y】
     * order_id 订单号【N】
     * refresh 强制刷新【N】
     * return array
     **/
	public function score( $params, $refresh = false )
	{
        if( $params['order_id'] ){
            if( strlen($params['order_id']) > 13 ){
                return array('code' => -3, 'msg' => 'order_id format error');    
            }else{
                $params['nonce'] = $params['order_id'];    
            }
        }else{
            $params['nonce'] = time() . rand(1000, 9999);
        }
        
        $request = array(
            'method' => $this->method,
            'version' => '2.0',
            'accessKey' => $this->accessKey,
            'signatureType' => 'HMAC-SHA1',
            'timestamp' => date("Y-m-d\TH:i:s\Z"),
            'format' => 'json',
            'nonce' => $params['nonce'],
            'customer' => $this->customer
        );
        
        if( $params['mobiles'] ){
            $i = 0;
            foreach( $params['mobiles'] as $key => $mobile ){
                $request['mobileList.' . $i++ ] = $mobile;
            }
        }elseif( $params['mobile'] ){
            if( !preg_match("/^1[34578]\d{9}$/", $params['mobile'], $match ) ){
                return array('code' => -2, 'msg' => 'mobile format error');
            }
            $request['mobileList.0'] = $params['mobile'];
        }else{
            return array('code' => -1, 'msg' => 'params error');
        }
        
        if( $request['mobileList.0'] && $refresh === false ){
            if($row = $this->get_score( $request['mobileList.0'] )){
                return array( 'code' => 1, 'data' => $row );
            }
        }
        
        $datagram = '';
        ksort($request);
        foreach ($request as $key => $val) {
            if($datagram){
                $datagram .= "&" . $key . "=" . urlencode($val);
            }else{
                $datagram = $key . "=" . urlencode($val);
            }
        }
        
        $signature = $this->signature('GET/' . $datagram, $this->accessSecret);
        
        $request_url = $this->gateway . "?" . $datagram . "&signature=" . $signature;
        
        $response = $this->request( $request_url );
        
        if( $response ){
            $this->ci->load->database();
            
            $result = array();
            $report = $this->parse( $response );
            
            foreach( $report as $mobile => $row ){
                $result[$mobile] = $row;
                $params['mobile'] = $mobile;
                $this->set_score( $row, $params );
            }
            
            return array( 'code' => 1, 'data' => $result );
        }
        
        return array('code' => -4, 'msg' => 'query fail');
	}
    
    /**
     * Activity_Pattern_RepetitivePurchase：
     *      重复购物指数：手机账户购买行为是否呈现高频重复的特征模式，短时间内集中重复购买同类商品的用户，一般风险偏高
     * Activity_Tendency_DailyPurchase
     *      正常购物指数：手机用户购物行为的倾向性，正常用户群体日常购买的主要为不易变现、不易发生抢购的普通商品，如果与此相反，则风险较高
     * Identity_Anomaly_RobotRegister
     *      机器注册指数：与当前手机相关的账户是否为机器批量注册，批量注册的账户一般不用于正常购物用途，风险较高
     * Identity_Authenticity_History
     *      号码非正常使用概率：手机号是否在互联网上出现过，无历史足迹的手机号，有可能为新手机号，但更有可能是非正常用途的黑卡、小号，风险极高
     * Network_Quality_RiskList
     *      关系网络历史风险：主动联系过当前手机的联系人数量，如果没有他人联系过当前手机，手机用于正常通信用途的概率较低，风险较高
     **/
    function parse( $response )
    {
        $report = array();
        $response = json_decode($response, true);
        
        if( $response['list'] ){
            foreach( $response['list'] as $key => $nodes ){
            
                $mobile = $nodes['mobile'];
                if( $nodes['infoCodeList'] ){
                    foreach( $nodes['infoCodeList'] as $row ){
                        
                        $report[$mobile]['repetitionIndex'] = 0;
                        $report[$mobile]['regularIndex'] = 0;
                        $report[$mobile]['robotIndex'] = 0;
                        $report[$mobile]['suspectIndex'] = 0;
                        $report[$mobile]['networkIndex'] = 0;
                        
                        if( $row['riskFactorCode'] == 'Activity_Pattern_RepetitivePurchase' ){
                            $report[$mobile]['repetitionIndex'] = (float)$row['riskMagnitude'];
                        }elseif( $row['riskFactorCode'] == 'Activity_Tendency_DailyPurchase' ){
                            $report[$mobile]['regularIndex'] = (float)$row['riskMagnitude'];
                        }elseif( $row['riskFactorCode'] == 'Identity_Anomaly_RobotRegister' ){
                            $report[$mobile]['robotIndex'] = (float)$row['riskMagnitude'];
                        }elseif( $row['riskFactorCode'] == 'Identity_Authenticity_History' ){
                            $report[$mobile]['suspectIndex'] = (float)$row['riskMagnitude'];
                        }elseif( $row['riskFactorCode'] == 'Network_Quality_RiskList' ){
                            $report[$mobile]['networkIndex'] = (float)$row['riskMagnitude'];
                        }
                    }
                    
                    $history_result = $this->history_score( $mobile );
                    
                    $rating = $this->reset_rating( $mobile, $nodes['score'], $history_result );
                    
                    $report[$mobile]['rating'] = $rating;
                    $report[$mobile]['score'] = $nodes['score'];
                    $report[$mobile]['user_id'] = $history_result['user_id'];
                    $report[$mobile]['history_score'] = $history_result['history_score'];
                    $report[$mobile]['average_money'] = $history_result['average_money'];
                    $report[$mobile]['count_order'] = $history_result['count_order'];
                    $report[$mobile]['total_money'] = $history_result['total_money'];
                    $report[$mobile]['total_goods_money'] = $history_result['total_goods_money'];
                    $report[$mobile]['total_discount_money'] = $history_result['total_discount_money'];
                    $report[$mobile]['querytime'] = date("Y-m-d H:i:s");
                }
            }
        }
        
        return $report;
    }
    
    
    /**
     * 设置风险等级
     * $mobile 手机号
     * $score 蚁盾评分
     * $history_score 历史订单评分
     */
    function reset_rating( $mobile, $score, $history_score )
    {
        $rating = '';
        $used_card_rating = 'L';
        $assure_user = false;
        
        if( $score <= 10 ){
            $rating = 'L';    
        }elseif( $score > 10 && $score <= 80 ){
            $rating = 'M';    
        }elseif( $score > 80 ){
            $rating = 'H';
        }
        
        if( $history_score['count_order'] > 0 ){
            
            if( $history_score['history_score'] >= 50 ){
                $rating = 'H';
            }elseif( $history_score['history_score'] >= 30 && $history_score['history_score'] < 50 ){
                if( $rating != 'H' ){
                    $rating = 'M';
                }
            }elseif( $history_score['history_score'] < 30 ){
                //if( $history_score['count_order'] > 1 ){
                    $rating = 'L';
                    if( $score < 80 ){
                        $assure_user = true;
                    }
                //}
                
            }
        }
        
        //已注册用户，判断优惠券使用情况
        $active_count = $not_used_count = $total_count = 0;
        
        if( $history_score['user_id'] ){
            
            $card_data_obj = $this->ci->db->from( $this->card_table )
                ->select( '`id`,`is_used`,`card_number`' )
                ->where( "`uid` = '" . $history_score['user_id'] . "'" )
                ->get();
                
            $card_data = $card_data_obj ? $card_data_obj->result_array() : array();
                
            if( $card_data ){
                
                foreach( $card_data as $row ){
                    
                    if(strpos($row['card_number'], 'register') === 0){
                        continue;
                    }
                    
                    $total_count++;
                    if( $row['is_used'] == 0 ){
                        $not_used_count++;
                    }
                }
                
                if( $total_count == $not_used_count && $total_count != 0 ){
                    if( $not_used_count >= 4 ){
                        $used_card_rating = 'H';
                    }elseif( $not_used_count < 4 && $not_used_count >= 2 ){
                        $used_card_rating = 'M';
                    }else{
                        $used_card_rating = 'L';
                    }
                }
            }
            
        }else{
            
            $active_data_obj = $this->ci->db->from( $this->active_table )
                ->select( '`id`,`card_money`' )
                ->where( "`mobile` = '" . $mobile . "'" )
                ->get();
            
            $active_data = $active_data_obj ? $active_data_obj->result_array() : array();
            
            $active_count = count( $active_data );
        
            if( $active_count >= 4 ){
                $used_card_rating = 'H';
            }elseif( $active_count < 4 && $active_count >= 2 ){
                $used_card_rating = 'M';
            }else{
                $used_card_rating = 'L';
            }
        }
        
        $this->set_card_score( array(
            'user_id' => $history_score['user_id'],
            'mobile' => $mobile,
            'used_card_rating' => $used_card_rating,
            'not_used_count' => $not_used_count,
            'total_count' => $total_count,
            'active_count' => $active_count
        ));
        
        if( $rating == 'H' || $used_card_rating == 'H' ){
            return $assure_user ? 'L' : 'H';
        }elseif( $rating == 'M' || $used_card_rating == 'M' ){
            return $assure_user ? 'L' : 'M';
        }else{
            return 'L';
        }
        
        return $rating;
    }
    
    
    /**
     * 根据历史订单数据，给手机号评分
     */
    function history_score( $mobile )
    {
        //折扣金额占商品金额占比
        $history_score = 0;
        
        //客户订单平均支付金额
        $average_money = 0;
        
        $user_id = '';
        $count_order = 0;
        $total_money = 0;
        $average_money = 0;
        $total_goods_money = 0;
        $total_discount_money = 0;
        
        $userinfo = $this->ci->db->from( $this->user_table )
            ->select( '*' )
            ->where( "`mobile` = '" . $mobile . "'" )
            ->get()->row_array();
        
        if( $userinfo ){
            $user_id = $userinfo['id'];
            $history_data = $this->ci->db->from( $this->order_table )
                ->select( 'id,order_name,money,goods_money,card_money,manbai_money,member_card_money,pay_discount,new_pay_discount,oauth_discount,jf_money' )
                ->where( "`uid` = '" . $user_id . "' AND `order_status` = 1 AND `operation_id` != 5 AND order_type NOT IN (3,4,7) AND `pay_parent_id` != 6" )
                ->get()->result_array();
        }
        
        if( $history_data ){
            
            $pro_discount_money = $pro_total_price = $pro_total_money = array();
            
            foreach( $history_data as $row ){
                $count_order++;
                $total_money += $row['money'];
                //$total_goods_money += $row['goods_money'];
                //$total_discount_money += $row['card_money'];
                
                $order_id = $row['id'];
                
                $product_data = $this->ci->db->from( $this->product_table )
                    ->select( 'price,qty,total_money' )
                    ->where( "`order_id` = '" . $order_id ."'" )
                    ->get()->result_array();
                
                $pro_total_price[$order_id] = 0;
                $pro_total_money[$order_id] = 0;
                $pro_discount_money[$order_id] = 0;
                foreach( $product_data as $pro_row ){
                    $pro_total_price[$order_id] += ($pro_row['price'] * $pro_row['qty']);
                    $pro_total_money[$order_id] += $pro_row['total_money'];
                }
                
                $pro_discount_money[$order_id] = $pro_total_price[$order_id] - $pro_total_money[$order_id] + $row['manbai_money'] + $row['member_card_money'] + $row['pay_discount'] + $row['new_pay_discount'] + $row['oauth_discount'] + $row['card_money'] + $row['jf_money'];
            }
            
            $total_goods_money = array_sum( $pro_total_price );
            $total_discount_money = array_sum( $pro_discount_money );
            
            //计算平均客单价
            if($count_order > 0){
                $average_money =  $total_money / $count_order;    
            }
            
            //计算折扣占比
            $discount_ratio = 0;
            if( $count_order > 0 && $total_money > 0 ){
                $discount_ratio = $total_discount_money / $total_goods_money;
                $history_score = $discount_ratio * 100;
            }
        }
        
        return array(  
            'user_id' => $user_id, 
            'history_score' => $history_score, 
            'average_money' => $average_money,
            'count_order' => $count_order,
            'total_money' => $total_money,
            'total_goods_money' => $total_goods_money,
            'total_discount_money' => $total_discount_money
        );
    }
    
    
    function get_score( $mobile )
    {
        $row = $this->ci->db->from( $this->score_table )
            ->select( '*' )
            ->where( "`mobile` = '" . $mobile . "'" )
            ->order_by( "querytime", "desc" )
            ->get()->row_array();
        
        $result = array();
        
        if( $row ){
    
            $history_result = $this->history_score( $mobile );
    
            if( $row['exemption'] == 1){
                $rating = 'L';
            }else{
                $rating = $this->reset_rating( $row['mobile'], $row['score'], $history_result );    
            }
            
            $history_result['rating'] = $rating;
            $history_result['querytime'] = date("Y-m-d H:i:s");
            
            $result[$mobile] = array(
                'repetitionIndex' => $row['repetition'],
                'regularIndex' => $row['regular'],
                'robotIndex' => $row['robot'],
                'networkIndex' => $row['network'],
                'suspectIndex' => $row['suspect'],
                'rating' => $rating,
                'score' => $row['score'],
                'history_score' => $history_result['history_score'],
                'average_money' => $history_result['average_money'],
                'count_order' => $history_result['count_order'],
                'total_money' => $history_result['total_money'],
                'total_goods_money' => $history_result['total_goods_money'],
                'total_discount_money' => $history_result['total_discount_money'],
                'querytime' => $history_result['querytime'],
            );
            
            $this->ci->db->where( 'id', $row['id'] );
            $this->ci->db->update( 'ttgy_mobile_score', $history_result );
        }
        
        return $result;
    }
    
    function set_score( $report, $params )
    {
        return $this->ci->db->insert( $this->score_table, array(
            'user_id' => $report['user_id'],
            'mobile' => $params['mobile'],
            'nonce' => $params['nonce'],
            'source' => $params['from'],
            'repetition' => $report['repetitionIndex'],
            'robot' => $report['robotIndex'],
            'regular' => $report['regularIndex'],
            'network' => $report['networkIndex'],
            'suspect' => $report['suspectIndex'],
            'rating' => $report['rating'],
            'score' => $report['score'],
            'history_score' => $report['history_score'],
            'average_money' => $report['average_money'],
            'count_order' => $report['count_order'],
            'total_money' => $report['total_money'],
            'total_goods_money' => $report['total_goods_money'],
            'total_discount_money' => $report['total_discount_money'],
            'querytime' => date("Y-m-d H:i:s"),
        ));
    }
    
    
    function set_card_score( $params )
    {
        $active_data_obj = $this->ci->db->from( $this->active_score_table )
            ->select( '`id`,`mobile`' )
            ->where( "`mobile` = '" . $params['mobile'] . "'" )
            ->get();
        
        $active_data = $active_data_obj ? $active_data_obj->row_array() : array();
       
        if( $active_data ){
            $this->ci->db->where( 'id', $active_data['id'] );
            $params['query_time'] = date("Y-m-d H:i:s");
            $this->ci->db->update( $this->active_score_table, $params );
        }else{
            $this->ci->db->insert( $this->active_score_table, array(
                'user_id' => $params['user_id'],
                'mobile' => $params['mobile'],
                'used_card_rating' => $params['used_card_rating'],
                'not_used_count' => $params['not_used_count'],
                'total_count' => $params['total_count'],
                'active_count' => $params['active_count'],
                'query_time' => date("Y-m-d H:i:s"),
            ));
        }
        
        return true;
    }
    
    
    
    /**
     * 获取hmac_sha1签名的值
     *
     * @param $str 源串
     * @param $key 密钥
     *
     * @return 签名值
     */
    public function signature($str, $key) 
    {
        $signature = "";
        
        if(function_exists('hash_hmac')){
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        }else{
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                    'H*', $hashfunc(
                            ($key ^ $opad) . pack(
                                    'H*', $hashfunc(
                                            ($key ^ $ipad) . $str
                                    )
                            )
                    )
            );
            $signature =base64_encode($hmac);
        }
        
        return str_replace(array('+', '/', '='), array('-', '_', ''), $signature);
    }
    
    
	/**
	 * 执行一个 HTTP GET请求
	 *
	 * @param string $url 执行请求的url
	 * @return array 返回网页内容
	 */
	public function request($url, $post_data = '')
    {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		if( $post_data ){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		}
        
		$res = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
		return $res;
	}
    
}