<?php
namespace bll;

class Cardchange
{
    public function __construct($params = array())
    {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
            $this->ci->load->library('session',array('session_id'=>$session_id));
        }
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $this->ci->load->helper('public');
        $this->ci->load->model('cardchange_model');
        $this->ci->load->model('warehouse_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('pro_card_model');
    }

    /*
    *提货券验证
    */
    function checkExchange($params){
        //必要参数验证start
        $required_fields = array(
            'card_number'=>array('required'=>array('code'=>'300','msg'=>'提货券卡号不能为空')),
            'card_passwd'=>array('required'=>array('code'=>'300','msg'=>'提货券密码不能为空')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        if (!empty($params['connect_id'])) {
            $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
            if ($uid_result['code'] != '200') {
                return $uid_result;
            } else {
                $uid = $uid_result['msg'];
            }
        }
        //获取session信息end
        $card_number = str_replace(' ', '', $params['card_number']);
        $passwd = $params['card_passwd'];
        $card_info = $this->ci->cardchange_model->get_pro_card_info($card_number);

        if(empty($card_info)){
           return array('code'=>'300','msg'=>'卡号验证错误');
        }
       $card_passwd = md5(substr(md5($passwd), 0,-1).'f');
       if($card_passwd == $card_info['card_passwd']){
            if($card_info['is_freeze']=='1'){
                return array('code'=>'300','msg'=>'该卡已冻结');
            }
            if($card_info['is_sent']=='0'){
                return array('code'=>'300','msg'=>'该卡未激活');
            }
            if($card_info['is_used']=='1'){
                return array('code'=>'300','msg'=>'该卡已使用');
            }
            if($card_info['start_time']>date("Y-m-d")){
                return array('code'=>'300','msg'=>'该提货券有效期还未开始');
            }
            if($card_info['to_date']<date("Y-m-d") && $card_info['is_expire']=='0'){
                return array('code'=>'300','msg'=>'该卡已过期');
            }
            if($card_info['is_delete']=='1'){
                return array('code'=>'300','msg'=>'卡号无效');
            }
            if ($card_info['is_auto']==1) {
                return array('code'=>'300','msg'=>'该提货券仅能通过电话兑换，请拨打400-720-0770进行兑换');
            }

            $product_id_arr = explode(',',$card_info['product_id']);
           //周期购券卡支付
            if($card_info['card_type'] == 4){
                $product_id_arr = array_map('trim', $product_id_arr);
                $pro_info = $product_id_arr;
            }else {
                $pro_info = $this->ci->product_model->getProcardProducts($product_id_arr);
                foreach ($pro_info as $key => $value) {
                    // 获取产品模板图片
                    if ($value['template_id']) {
                        $this->ci->load->model('b2o_product_template_image_model');
                        $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                        if (isset($templateImages['main'])) {
                            $value['photo'] = $templateImages['main']['image'];
                        }
                    }

                    $pro_info[$key]['photo'] = PIC_URL . $value['photo'];
                    if ($card_info['card_money'] > 0) {
                        $pro_info[$key]['price'] = $card_info['card_money'];
                    }
                    if ($card_info['hide_price']) {
                        $pro_info[$key]['price'] = '0.00';
                    }
                }
            }
            return array('code'=>'200','pro_info'=>$pro_info,'hide_price'=>$card_info['hide_price'], 'card_type' => $card_info['card_type']);
        } elseif ($card_info['error_num'] > 6) {
            return ['code' => '300', 'msg' => '该提货券已冻结，请联系客服兑换'];
        } else {
            $this->ci->pro_card_model->upErrorNum($card_number);
            return array('code'=>'301','msg'=>'卡号验证错误');
       }
    }



    function createOrder($params){
      //必要参数验证start
        $required_fields = array(
            'card_number'=>array('required'=>array('code'=>'300','msg'=>'提货券卡号不能为空')),
            'card_passwd'=>array('required'=>array('code'=>'300','msg'=>'提货券密码不能为空')),
            'name'=>array('required'=>array('code'=>'300','msg'=>'收货人不能为空')),
            'province'=>array('required'=>array('code'=>'300','msg'=>'收货地区不完整')),
            'city'=>array('required'=>array('code'=>'300','msg'=>'收货地区不完整')),
            'area'=>array('required'=>array('code'=>'300','msg'=>'收货地区不完整')),
            'address'=>array('required'=>array('code'=>'300','msg'=>'收货地址不能为空')),
            'mobile'=>array('required'=>array('code'=>'300','msg'=>'联系手机不能为空')),
            'price_id'=>array('required'=>array('code'=>'300','msg'=>'兑换商品错误')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        //必要参数验证end

        if(empty($params['is_2to3day']) && empty($params['shtime']) && empty($params['stime'])){
            return array("code"=>"300","msg"=>"配送时间不能为空");
        }

        // $params['is_2to3day'] = 'true';//todo fix ios bug 注释

        if(isset($params['is_2to3day']) && $params['is_2to3day']=='false'){
        /*截单时间判断start*/
        $this->ci->load->model("region_model");
        //$area_info = $this->ci->region_model->get_area_info($params['area']);
        $send_date_tmp = explode('-', $params['shtime']);
        //$cut_off_time = $area_info['cut_off_time'];
        //$cut_off_time_m = $area_info['cut_off_time_m'];
        //$cut_off_time_n = $area_info['cut_off_time_n'];
        $reset_shtime = false;
        $stime = $params['stime']; //by jackchen
        //$h = date('H');

        $reset_shtime = $this->ci->region_model->checkResetShtime($params['area'],$send_date_tmp[0],$stime,$params['province']);
        //支持上下午单
        // if($area_info['can_ampm_send']){
        //     if($h>=$cut_off_time_m && $send_date_tmp[0]==date('Ymd') && in_array($stime, array('0918','0914'))){
        //         $reset_shtime = true;
        //     }elseif($h>=$cut_off_time_n && $send_date_tmp[0] == date('Ymd')){
        //         $reset_shtime = true;
        //     }elseif($h>=$cut_off_time && $send_date_tmp[0]==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914'))){
        //         $reset_shtime = true;
        //     }
        //     if(strcmp($send_date_tmp[0], date('Ymd')) < 0){
        //         $reset_shtime = true;
        //     }
        //     if($send_date_tmp[0]==date('Ymd') && in_array($stime, array('0918','0914'))){
        //         $reset_shtime = true;
        //     }
        // }elseif($area_info['can_night_send']){ //支持早晚单
        //     if($h>=$cut_off_time_m && $send_date_tmp[0]==date('Ymd')){
        //         $reset_shtime = true;
        //     }elseif($h>=$cut_off_time && $send_date_tmp[0]==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914','1418'))){
        //         $reset_shtime = true;
        //     }
        //     if(strcmp($send_date_tmp[0], date('Ymd')) < 0){
        //         $reset_shtime = true;
        //     }
        //     if($send_date_tmp[0]==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
        //         $reset_shtime = true;
        //     }
        // }else{
        //     $send_free = $this->ci->region_model->is_send_wd($params['province']);
        //     if($send_free === true){
        //         if($h>=$cut_off_time && strcmp($send_date_tmp[0], date('Ymd')) <= 0){
        //             $reset_shtime = true;
        //         }
        //     }else{
        //         if(strcmp($send_date_tmp[0], date('Ymd')) <= 0){
        //             $reset_shtime = true;
        //         }
        //     }
        // }
        if($reset_shtime){
            return array("code"=>"300","msg"=>"您的下单时间已超过截单时间，请重新选择配送时间");
        }
        /*截单时间判断end*/
        }


        //获取session信息start
        $uid = 0;
        if (!empty($params['connect_id'])) {
            $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
            if($uid_result['code']!='200'){
                return $uid_result;
            }else{
                $uid = $uid_result['msg'];
            }
        }
        //获取session信息end

        if(isset($params['is_2to3day']) && $params['is_2to3day']=='true'){
            $params['shtime'] = 'after2to3days';
            $params['stime'] = '';
        }
        if(!isset($params['price_id']) || !isset($params['name']) || !isset($params['mobile']) || !isset($params['province']) || !isset($params['city']) || !isset($params['area']) || !isset($params['address']) || !isset($params['shtime'])){
            return array("code"=>"300","msg"=>"数据提交错误，请勿重复提交订单");
        }
        if($params['price_id']=='' || $params['name']==''  || $params['mobile']==''  || $params['province']=='' || $params['city']=='' || $params['area']=='' || $params['address']=='' || $params['shtime']==''){
            return array("code"=>"300","msg"=>"数据提交错误，请勿重复提交订单");
        }

        $card_number = str_replace(' ', '', $params['card_number']);
        $card_passwd = md5(substr(md5($params['card_passwd']), 0,-1).'f');

        //事务提交订单数据
        $this->ci->db->trans_start();

        $card_info = $this->ci->cardchange_model->get_pro_card_info($card_number);
         if(empty($card_info)){
             return array('code'=>'300','msg'=>'卡号验证错误');
         }else{
              if($card_info['is_sent']=='0'){
                return array('code'=>'300','msg'=>'该卡未激活');
              }
              if($card_info['is_used']=='1'){
                return array('code'=>'300','msg'=>'该卡已使用');
              }
              if($card_info['start_time']>date("Y-m-d")){
                return array('code'=>'300','msg'=>'该提货券有效期还未开始');
              }
              if($card_info['to_date']<date("Y-m-d") && $card_info['is_expire']=='0'){
                return array('code'=>'300','msg'=>'该卡已过期');
              }
              if($card_info['is_delete']=='1'){
                return array('code'=>'300','msg'=>'卡号无效');
              }
              if ($card_info['is_auto']==1) {
                return array('code'=>'300','msg'=>'该提货券仅能通过电话兑换，请拨打400-720-0770进行兑换');
              }
        }
        $product_id_arr = explode(',',$card_info['product_id']);

        $sheet_show_price = '1';
        if($card_info['hide_price']=='1'){
            $sheet_show_price = '0';
        }
        if (!empty($params['hide_price']) && $params['hide_price'] == '1') {
            $sheet_show_price = '0';
        }

        $pro_info = $this->ci->product_model->getProcardProducts($product_id_arr);

        $price_id_arr = array();
        $send_region = '';
        foreach ($pro_info as $key => $value) {
            $price_id_arr[] = $value['price_id'];
            if($params['price_id'] == $value['price_id'])
            {
                $send_region = $value['send_region'];
            }
        }

        if(!in_array($params['price_id'], $price_id_arr)){
            return array("code"=>"300","msg"=>"数据提交错误，请勿重复提交订单");
        }

        if(strpos($send_region,$params['province']) == false)
        {
            return array("code"=>"300","msg"=>"该礼盒不支持此区域配送，请重新选择礼盒兑换");
        }

        //插入user_address
        $address_id = 0;
        if ($uid) {
            $address_id = $this->ci->cardchange_model->addUserAddress($params,$uid);
        }
        // if(!$this->check_new_year($address_id)){
        //     $this->db->trans_rollback();
        //     return array("code"=>"500","msg"=>"抱歉，您选择的区域暂时不能配送，预计2月25日恢复。");
        // }
        $order_region = $this->ci->cardchange_model->get_order_region($address_id, $params['province']);
        if(strpos($params['shtime'], '-')>0){
            $stime = '';
        }else{
            $stime = $params['stime'];
        }

        if($card_info['card_money']>0){
            $price = $card_info['card_money'];
        }else{
            $price = $this->ci->cardchange_model->getPrice($params['price_id']);
        }


        //TMS
        $ware_id = '';
        if(!empty($address_id))
        {
            $this->ci->load->model('user_address_model');
            $user_add = $this->ci->user_address_model->dump(array('id' => $address_id));
            if(!empty($user_add) && !empty($user_add['tmscode']))
            {
                $arr_tmsCode = explode('-',$user_add['tmscode']);
                $tmsCode =$arr_tmsCode[0];
                $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                if(!empty($ware))
                {
                    $ware_id = $ware['id'];
                }
            }
        }
        if(!empty($ware_id))
        {
            $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($ware_id);
        }

        if(empty($warehouse_info))
        {
            $warehouse_info = $this->ci->warehouse_model->get_warehouse_by_region($params['area']);
        }

        if($warehouse_info){
            $cang_id = $warehouse_info['id'];
            $deliver_type = $warehouse_info['send_type'];
        }else{
            $area_cang = $this->ci->config->item('area_cang');
            $cang_id = $area_cang[$params['province']]['cang_id'];
            $deliver_type = $area_cang[$params['province']]['type'];
        }
        switch ($params['source']) {
            case 'wap':
                $order_channel = 2;
                break;
            case 'app':
                $order_channel = 6;
                break;
            default:
                $order_channel = 1;
                break;
        }
        $fields = array(
            'uid'=>$uid,
            "time"=> date("Y-m-d H:i:s"),
            "pay_name"=>"券卡支付-在线提货券支付",
            "pay_parent_id"=>'6',
            "pay_id"=>'1',
            "shtime"=>$params['shtime'],
            "stime"=>$stime,
            "send_date"=>'0000-00-00',
            "zipcode"=>'0',
          "hk"=>$params['hk'],
            "msg"=>$params['msg'],
            "money"=>$price,
            "goods_money"=>$price,
            "manbai_money"=>'0',
          "pay_status"=>"1",
            "score"=>'0',
            "address_id"=>$address_id,
            "order_status"=>'1',
            "referer_url"=>'',
            "order_region"=>$order_region,
            'cang_id' => $cang_id,
            'deliver_type' => $deliver_type,
            'sheet_show_price' => $sheet_show_price,
            'channel' => $order_channel,
            'pro_card_number' => $card_number,
        );

        // 需要支付
        if (!empty($card_info['pay_method'])) {
            $payMethod = json_decode($card_info['pay_method'], true)[0];
            $payParentId = $payMethod['pay_parent_id'];
            $payId = $payMethod['pay_id'];
            $payArray = $this->ci->config->item("pay_array");

            // 支付方式合法性验证
            if (!isset($payArray[$payParentId])) {
                return ['code' => '300', 'msg' => '指定支付方式错误，请联系客服'];
            }
            $parentName = $payArray[$payParentId]['name'];
            $sonName = '';

            if (!empty($payArray[$payParentId]['son'])) {
                $sonArray = $payArray[$payParentId]['son'];
                if (!isset($sonArray[$payId])) {
                    return array('code' => '300', 'msg' => '指定支付方式错误，请联系客服');
                }
                $sonName = $sonArray[$payId];
            } else {
                $payId = '0';
            }

            if (empty($sonName)) {
                $payName = $parentName;
            } else {
                $payName = $parentName . "-" . $sonName;
            }

            $fields = array_merge($fields, [
                'pay_name' => $payName,
                'pay_parent_id' => $payParentId,
                'pay_id' => $payId,
                'money' => $card_info['pay_money'],
                'method_money' => $card_info['pay_money'],
                'pro_card_money' => $price,
                'pay_status' => '0',
            ]);
        }

        $insert_result = $this->ci->cardchange_model->generate_order('order',$fields);

        //商品插入数据库
        $order_pro_info = $this->ci->cardchange_model->orderAddPro($insert_result['order_id'],$params['price_id'],$card_info['card_money']);

        //插入order_address
        $order_addr_info = $this->ci->cardchange_model->orderAddAddr($insert_result['order_id'],$address_id,$params);

        //插入赠品
//        $this->ci->cardchange_model->orderAddGift($insert_result['order_id'],$params['price_id']);

        //更新pro_card表
        $exchange_result = $this->ci->cardchange_model->exchange($card_number,$insert_result['order_name']);

        if ($exchange_result['result']=='fail') {
            $this->ci->db->trans_rollback();
            return array("code"=>"500","msg"=>"订单提交失败，请重新提交订单。");
        }

        $this->ci->db->trans_complete();
        if ($this->ci->db->trans_status() === FALSE)
        {
            $this->ci->db->trans_rollback();
            return array("code"=>"500","msg"=>"订单提交失败，请重新提交订单。");
        }
        else
        {
            $this->ci->db->trans_commit();
        }

        if($stime!=""){
            $send_time = substr($params['shtime'],0,8)." ".substr($stime,0,2)."~".substr($stime,2,2);
        }else {
            $send_time = substr($params['shtime'],0,8)." ".substr($params['shtime'],9,2)."~".substr($params['shtime'],11,2);
        }
        if($params['shtime']=='after2to3days'){
            $send_time = "2~3天内送达";
        }
        $address_info = array(
          'address' => $order_addr_info['region'].$order_addr_info['order_addr_info']['address'],
          'mobile' => $order_addr_info['order_addr_info']['mobile'],
          'name' => $order_addr_info['order_addr_info']['name'],
          'shtime' => $send_time,
          'msg' => $params['msg'],
          'hk' => $params['hk']
        );
        $oderinfo = array('order_name'=>$insert_result['order_name'],'address_info'=>$address_info, 'pay_status' => $fields['pay_status']);
        return array("code"=>"200","msg"=>"下单成功","order_info"=>$oderinfo);
    }

    /*
    *获取session
    */
    private function get_uid_by_connect_id($session_id,$lock_order=false){
        $session =   $this->ci->session->userdata;
        if(empty($session)){
            return array('code'=>'400','msg'=>'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);

        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if( !isset($userdata['id']) || $userdata['id'] == "" ){
            return array('code'=>'400','msg'=>'not this user,may be wrong connect id');
        }
        return array('code'=>'200','msg'=>$userdata['id']);
    }

}
