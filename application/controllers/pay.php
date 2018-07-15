<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pay extends CI_Controller {


    function weixin()
    {
        //error_log(print_r(file_get_contents('php://input', 'r'),1)."\n\r",3,"/tmp/lsc.log");
        if(isset($_POST['order_amount'])){
          $input = (Object)$_POST;
        }else{
          $input = json_decode(file_get_contents('php://input', 'r'));
	}
        $result = array();
        if( !isset($input->order_amount))
        {
            $result['code'] = "500";
            $result['msg'] = "订单总价不能为空";
            exit( json_encode($result) );
        }
        if( !isset($input->product_name))
        {
            $result['code'] = "500";
            $result['msg'] = "订单够商品名称不能为空";
            exit( json_encode($result) );
        }
        if( !isset($input->order_number))
        {
            $result['code'] = "500";
            $result['msg'] = "订单号不能为空";
            exit( json_encode($result) );
        }

        require_once ("pay_resources/wechat/classes/RequestHandler.class.php");
        require_once ("pay_resources/wechat/tenpay_config.php");
        require_once ("pay_resources/wechat/classes/ResponseHandler.class.php");
        require ("pay_resources/wechat/classes/client/TenpayHttpClient.class.php");
        //获取提交的商品价格
        $order_price=trim($input->order_amount);
        //获取提交的商品名称
        $product_name=trim($input->product_name);
        //获取提交的订单号
        $out_trade_no=trim($input->order_number);
        $notify_url = "http://wapi.fruitday.com/pay/wx_pay_succ";


        $outparams =array();
        //商品价格（包含运费），以分为单位
        $total_fee= $order_price*100;
        //输出类型
        $out_type	= strtoupper($_GET['out_type']);
        $plat_from	= strtoupper($_GET['plat']);
        //获取token值
        $reqHandler = new RequestHandler();
        $reqHandler->init($APP_ID, $APP_SECRET, $PARTNER_KEY, $APP_KEY);
        // $this->load->driver('cache');
        // if ($this->cache->apc->is_supported())
        // {
        //     if ($data = $this->cache->apc->get('weixin_token'))
        //     {
        //         $reqHandler->Token=$data;
        //     }else {
        //         $Token = $reqHandler->GetToken();
        //         $this->cache->apc->save('weixin_token', $Token, 3000);
        //     }
        // } else {
        //     //echo "not support";exit;
        //     $reqHandler->GetToken();
        // }
        $this->load->library("ocs");
        if ($this->ocs->connect())
            {  
                    if ($data = $this->ocs->get('weixin_token'))
                    {  
                        $reqHandler->Token=$data;
                    }else {
                        $Token= $reqHandler->GetToken();
                        $this->ocs->set('weixin_token', $Token, 3000);
                    }
                } else {
                    $reqHandler->GetToken();
        }
        if ( $reqHandler->Token !='' ){
            //=========================
            //生成预支付单
            //=========================
            //设置packet支付参数
            $packageParams =array();		

            $packageParams['bank_type']		= 'WX';	            //支付类型
            $packageParams['body']			= $out_trade_no;					//商品描述
            $packageParams['fee_type']		= '1';				//银行币种
            $packageParams['input_charset']	= 'UTF-8';		    //字符集
            $packageParams['notify_url']	= $notify_url;	    //通知地址
            $packageParams['out_trade_no']	= $out_trade_no;		        //商户订单号
            $packageParams['partner']		= $PARTNER;		        //设置商户号
            $packageParams['total_fee']		= $total_fee;			//商品总金额,以分为单位
            $packageParams['spbill_create_ip']= $_SERVER['REMOTE_ADDR'];  //支付机器IP
            //获取package包
            $package= $reqHandler->genPackage($packageParams);
            $time_stamp = time();
            $nonce_str = md5(rand());
            //设置支付参数
            $signParams =array();
            $signParams['appid']	=$APP_ID;
            $signParams['appkey']	=$APP_KEY;
            $signParams['noncestr']	=$nonce_str;
            $signParams['package']	=$package;
            $signParams['timestamp']=$time_stamp;
            $signParams['traceid']	=$out_trade_no;
            //生成支付签名
            $sign = $reqHandler->createSHA1Sign($signParams);
            //增加非参与签名的额外参数
            $signParams['sign_method']		='sha1';
            $signParams['app_signature']	=$sign;
            //剔除appkey
            unset($signParams['appkey']); 
            //获取prepayid
            $prepayid=$reqHandler->sendPrepay($signParams);

            if( ! $prepayid )
            {
                $Token=$reqHandler->GetToken();
                // $this->cache->apc->save('weixin_token', $Token, 3000);
                $this->ocs->set('weixin_token', $Token, 3000);
                $prepayid=$reqHandler->sendPrepay($signParams);
            }

            if ($prepayid != null) {
                $pack	= 'Sign=WXPay';
                //输出参数列表
                $prePayParams =array();
                $prePayParams['appid']		=$APP_ID;
                $prePayParams['appkey']		=$APP_KEY;
                $prePayParams['noncestr']	=$nonce_str;
                $prePayParams['package']	=$pack;
                $prePayParams['partnerid']	=$PARTNER;
                $prePayParams['prepayid']	=$prepayid;
                $prePayParams['timestamp']	=$time_stamp;
                //生成签名
                $sign=$reqHandler->createSHA1Sign($prePayParams);

                $outparams['retcode']=0;
                $outparams['retmsg']='ok';
                $outparams['appid']=$APP_ID;
                $outparams['noncestr']=$nonce_str;
                $outparams['package']=$pack;
                $outparams['prepayid']=$prepayid;
                $outparams['timestamp']=$time_stamp;
                $outparams['sign']=$sign;

            }else{
                $outparams['retcode']=-2;
                $outparams['retmsg']='错误：获取prepayId失败';
            }
        }else{
            $outparams['retcode']=-1;
            $outparams['retmsg']='错误：获取不到Token';
        }


        ob_clean();
        if($outparams['retcode'] == -1)
        {
            $result['code'] = "500";
            $result['msg'] = "错误：获取不到Token";
            exit( json_encode($result) );
        }
        if($outparams['retcode'] == -2)
        {
            $result['code'] = "500";
            $result['msg'] = "错误：获取prepayId失败，可能token过期";
            exit( json_encode($result) );
        }
        $result['code'] = "200";
        $result['msg'] = array("prepayid"=>$prepayid);
        echo json_encode($result);
        //debug信息,注意参数含有特殊字符，需要JsEncode
        if ($DEBUG_ ){
            echo PHP_EOL  .'/*' . ($reqHandler->getDebugInfo()) . '*/';
        }

        //$data = $this->curl->request($genprepayUrl, $postData , "JSON");
        //$data = $data["response"];

        //echo $data;
    }

    function wx_pay_succ(){
        echo "success";
    }
}
