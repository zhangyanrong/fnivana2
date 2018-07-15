<?php
/**
 * 劵卡
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: card.php 1 2014-08-14 10:34:22Z pax $
 * @link      http://www.fruitday.com
 **/    
namespace bll\pool;

class Card
{
    private $_card_type = array(
        20 => '提货券',
        21 => '充值卡',
        22 => '果实卡',
        23 => '券',
        24 => '电子券',
        );

    public function __construct()
    {
        $this->ci = & get_instance();
    }

    /**
     * 获取卡券
     *
     * @return void
     * @author 
     **/
    private function getCards($cardsno,$card_type)
    {
        $cardList = array();
        switch ($card_type) {
            case '21': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $giftcards = $this->ci->gift_cards_model->getList('card_number,to_date,is_used,activation',array('card_number'=>$cardsno));
                foreach ($giftcards as $key => $value) {
                    $cardList[] = array(
                        'card_type' => '',
                        'flag' => '',
                        'card_number' => $value['card_number'],
                        'is_used' => $value['is_used'],
                        'is_sent' => $value['activation'],
                        'is_delete' => 0,
                        'to_date' => $value['to_date'],
                        );
                }
                break;
            case '20': // 提货券
                $this->ci->load->model('pro_card_model');

                $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$cardsno,'card_type'=>array(1,4)));
		
                // foreach ($cardList as $key => $value) {
                //     $cardList[$key]['card_number'] = strtoupper($value['card_number']);
                // }
		
                break;  
            case '22': // 果实卡
                $this->ci->load->model('pro_card_model');
                $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$cardsno,'card_type'=>'3'));

                break;
            case '23': // 提货券
                $this->ci->load->model('pro_card_model');
                $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$cardsno,'card_type'=>'1'));
        
                // foreach ($cardList as $key => $value) {
                //     $cardList[$key]['card_number'] = strtoupper($value['card_number']);
                // }
        
                break;  
            case '24': // 电子充值卡
                $this->ci->load->model('gift_cards_model');
                $giftcards = $this->ci->gift_cards_model->getList('card_number,to_date,is_used,activation',array('card_number'=>$cardsno));
                foreach ($giftcards as $key => $value) {
                    $cardList[] = array(
                        'card_type' => '',
                        'flag' => '',
                        'card_number' => $value['card_number'],
                        'is_used' => $value['is_used'],
                        'is_sent' => $value['activation'],
                        'is_delete' => 0,
                        'to_date' => $value['to_date'],
                        );
                }
                break;
            default:
                # code...
                break;
        }

        return $cardList;
    }

    /**
     * 验证卡号的有效(批量操作)
     *
     * @return void
     * @author 
     **/
    public function check($filter)     
    {
        $this->rpc_log = array('rpc_desc' => '卡号验证','obj_type'=>'card');
        if (!$filter['card_number']) return array('result'=>0,'msg' => '卡号不能为空');

        $flag        = (string) $filter['flag'];
        $card_type   = (string) $filter['card_type'];
        $card_number = $filter['card_number'];

        // $this->ci->load->model('pro_card_model');
        // $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$filter['card_number']));
        $cardList = $this->getCards($card_number,$card_type);

        $error = array(); $true = array(); $i = 0;
        foreach ($cardList as $key => $value) {
            $kk = array_search($value['card_number'], $filter['card_number']);

            if ($kk !== false) {
                unset($filter['card_number'][$kk]);
            }else{
                $kk = array_search(strtoupper($value['card_number']), $filter['card_number']);
                if ($kk !== false) {
                    unset($filter['card_number'][$kk]);
                }else{
                    $kk = array_search(strtolower($value['card_number']), $filter['card_number']);
                    if ($kk !== false) {
                        unset($filter['card_number'][$kk]);
                    }
                }
            }

            // if ($flag != $value['flag'] && $value['card_type'] == '1') {
            //     $error[$value['card_number']] = '产品标识不符';
            //     continue;    
            // }

            if ($value['is_used'] != '0') {
                $error[$value['card_number']] = '该卡已被使用';
                continue;
            }

            if ($value['is_sent'] != '0') {
                $error[$value['card_number']] = '该卡已激活';
                continue;
            }

            if ($value['is_delete'] != '0') {
                $error[$value['card_number']] = '该卡已失效';
                continue;
            }

            if (strtotime($value['to_date']) < time()) {
                $error[$value['card_number']] = '该卡已过期';
                continue;
            }

            $true[$i] = $value['card_number'];

            $i++;
        }

        foreach ($filter['card_number'] as $key => $card_number) {
            $error[$card_number] = '该卡无效';
        }


        return array('result' => 1, 'data' => array('true_card'=>$true, 'error_card'=>$error));
    }


    private function _card_active($cardsno,$card_type,$orderNo='',$wh_id=0,$mobile='')
    {

        $affected_rows = 0;
        switch ($card_type) {
            case '21': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'1','active_time'=>date('Y-m-d H:i:s'),'ordername'=>$orderNo,'wh_id'=>$wh_id,'mobile'=>$mobile),array('card_number'=>$cardsno));
                break;
            case '20': 
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '1','active_time'=>date('Y-m-d H:i:s'),'oms_order_name'=>$orderNo,'wh_id'=>$wh_id,'mobile'=>$mobile),array('card_number' => $cardsno));
                break;
            case '22': // 提货券
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '1','active_time'=>date('Y-m-d H:i:s'),'oms_order_name'=>$orderNo,'wh_id'=>$wh_id,'mobile'=>$mobile),array('card_number' => $cardsno));
                break;
            case '23': // 提货券
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '1','active_time'=>date('Y-m-d H:i:s'),'oms_order_name'=>$orderNo,'wh_id'=>$wh_id,'mobile'=>$mobile),array('card_number' => $cardsno));
                break;
            case '24': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'1','active_time'=>date('Y-m-d H:i:s'),'ordername'=>$orderNo,'wh_id'=>$wh_id,'mobile'=>$mobile),array('card_number'=>$cardsno));
                break;
            default:
                break;
        }

        return $affected_rows;
    }

    private function _card_inactive($cardsno,$card_type)
    {

        $affected_rows = 0;
        switch ($card_type) {
            case '21': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'0'),array('card_number'=>$cardsno));
                break;
            case '20': // 提货券
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '0'),array('card_number' => $cardsno));
                break;
            case '22': // 果实卡
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '0'),array('card_number' => $cardsno));
                break;
            case '23': // 果实卡
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '0'),array('card_number' => $cardsno));
                break;
            case '24': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'0'),array('card_number'=>$cardsno));
                break;
            default:
                break;
        }

        return $affected_rows;
    }

    private function _card_cancel($cardsno,$card_type)
    {

        $affected_rows = 0;
        switch ($card_type) {
            case '21': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'0','content'=>'OMS充值卡作废','is_used'=>'1'),array('card_number'=>$cardsno));
                break;
            case '20': // 提货券
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_delete' => '1','is_sent'=>'0'),array('card_number' => $cardsno));
                break;
            case '22': // 果实卡
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_delete' => '1'),array('card_number' => $cardsno));
                break;
            case '23': // 果实卡
                $this->ci->load->model('pro_card_model');
                $affected_rows = $this->ci->pro_card_model->update(array('is_delete' => '1'),array('card_number' => $cardsno));
                break;
            case '24': // 充值卡
                $this->ci->load->model('gift_cards_model');
                $affected_rows = $this->ci->gift_cards_model->update(array('activation'=>'0','content'=>'OMS充值卡作废','is_used'=>'1'),array('card_number'=>$cardsno));
                break;
            default:
                break;
        }

        return $affected_rows;
    }

    /**
     * 卡号激活(批量操作)
     *
     * @return void
     * @author 
     **/
    public function active($filter)
    {
        $this->rpc_log = array('rpc_desc' => '卡号激活','obj_type'=>'card');
        if (!$filter['card_number']) return array('result'=>0,'msg' => '卡号不能为空');

        $flag = (string) $filter['flag'];
        $card_type = (string) $filter['card_type'];
        $card_number = $filter['card_number'];
        $orderNo = $filter['orderNo'];
        $wh_id = $filter['wh_id'];
        $mobile = trim($filter['mobile']);

        // $this->ci->load->model('pro_card_model');
        // $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$filter['card_number']));
        $cardList = $this->getCards($card_number,$card_type);

        $error_card = ''; $error_msg='';
        foreach ($cardList as $key => $value) {
            $kk = array_search($value['card_number'], $filter['card_number']);

            if ($kk !== false) {
                unset($filter['card_number'][$kk]);
            }else{
                $kk = array_search(strtoupper($value['card_number']), $filter['card_number']);
                if ($kk !== false) {
                    unset($filter['card_number'][$kk]);
                }else{
                    $kk = array_search(strtolower($value['card_number']), $filter['card_number']);
                    if ($kk !== false) {
                        unset($filter['card_number'][$kk]);
                    }
                }
            }

            // if ($flag != $value['flag'] && $value['card_type'] == '1') {
            //     return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'产品标识不符');
            // }


            if ($value['is_used'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已被使用');
            }

            if ($value['is_sent'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已激活');
            }

            if ($value['is_delete'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已失效');
            }

            if (strtotime($value['to_date']) < time()) {
               return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已过期');
            }

            $true[] = $value['card_number'];
        }

        foreach ($filter['card_number'] as $key => $card_number) {
            return array('result'=>0,'errorCard'=>$card_number,'errorMsg'=>'该卡无效');
        }

        if ($true) {
            // $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '1'),array('card_number' => $true));
            $affected_rows = $this->_card_active($true,$card_type,$orderNo,$wh_id,$mobile);
            if (!$affected_rows) {
                return array('result'=>0,'errorCard'=>'','errorMsg'=>'激活失败');
            }
        }

        

        return array('result' => 1, 'data' => array('true_card'=>$true));
    }

    /**
     * 卡号失效(批量操作)
     *
     * @return void
     * @author 
     **/
    public function inactive($filter)
    {
       if (!$filter['card_number']) return array('result'=>0,'msg' => '卡号不能为空');
       $flag = (string) $filter['flag'];
       $card_type = (string) $filter['card_type'];
       $card_number = $filter['card_number'];

        // $this->ci->load->model('pro_card_model');
        // $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$filter['card_number']));
       $cardList = $this->getCards($card_number,$card_type);

        $error = array(); $true = array();
        foreach ($cardList as $key => $value) {
            $kk = array_search($value['card_number'], $filter['card_number']);

            if ($kk !== false) {
                unset($filter['card_number'][$kk]);
            }else{
                $kk = array_search(strtoupper($value['card_number']), $filter['card_number']);
                if ($kk !== false) {
                    unset($filter['card_number'][$kk]);
                }else{
                    $kk = array_search(strtolower($value['card_number']), $filter['card_number']);
                    if ($kk !== false) {
                        unset($filter['card_number'][$kk]);
                    }
                }
            }

            // if ($flag != $value['flag'] && $value['card_type'] == '1') {
            //     return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'产品标识不符');
            // }

            if ($value['is_used'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已被使用');
            }

            if ($value['is_sent'] != '1') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡未激活');
            }

            if ($value['is_delete'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已失效');
            }

            if (strtotime($value['to_date']) < time()) {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已过期');
            }

            $true[] = $value['card_number'];
        }

        foreach ($filter['card_number'] as $key => $card_number) {
            return array('result'=>0,'errorCard'=>$card_number,'errorMsg'=>'该卡无效');
        }

        if ($true) {
            // $affected_rows = $this->ci->pro_card_model->update(array('is_sent' => '0'),array('card_number' => $true));        
            $affected_rows = $this->_card_inactive($true,$card_type);

            if (!$affected_rows) {
                return array('result'=>0,'errorCard'=>'','errorMsg'=>'卡号置失效失败');
            }
        }

        
        $this->rpc_log = array('rpc_desc' => '卡号失效','obj_type'=>'card');

        return array('result' => 1, 'data' => array('true_card' => $true));
    }

    /**
     * 卡号取消(批量操作)
     *
     * @return void
     * @author 
     **/
    public function cancel($filter)
    {
       if (!$filter['card_number']) return array('result'=>0,'msg' => '卡号不能为空');
       $flag = (string) $filter['flag'];
       $card_type = (string) $filter['card_type'];
       $card_number = $filter['card_number'];

       $cardList = $this->getCards($card_number,$card_type);

        // $this->ci->load->model('pro_card_model');
        // $cardList = $this->ci->pro_card_model->getList('card_type,flag,card_number,is_used,is_sent,is_delete,to_date',array('card_number'=>$filter['card_number']));

        $error = array(); $true = array();
        foreach ($cardList as $key => $value) {
            $kk = array_search($value['card_number'], $filter['card_number']);

            if ($kk !== false) {
                unset($filter['card_number'][$kk]);
            }else{
                $kk = array_search(strtoupper($value['card_number']), $filter['card_number']);
                if ($kk !== false) {
                    unset($filter['card_number'][$kk]);
                }else{
                    $kk = array_search(strtolower($value['card_number']), $filter['card_number']);
                    if ($kk !== false) {
                        unset($filter['card_number'][$kk]);
                    }
                }
            }

            // if ($flag != $value['flag'] && $value['card_type'] == '1') {
            //     return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'产品标识不符');
            // }

            if ($value['is_used'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已被使用');
            }

            // if ($value['is_sent'] != '1') {
            //     $error[$value['card_number']] = '该卡未激活';
            //     continue;
            // }

            if ($value['is_delete'] != '0') {
                return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已失效');
            }

            // if (strtotime($value['to_date']) < time()) {
            //     return array('result'=>0,'errorCard'=>$value['card_number'],'errorMsg'=>'该卡已过期');
            // }

            $true[] = $value['card_number'];
        }

        foreach ($filter['card_number'] as $key => $card_number) {
            return array('result'=>0,'errorCard'=>$card_number,'errorMsg'=>'该卡无效');
        }



        if ($true) {
            // $affected_rows = $this->ci->pro_card_model->update(array('is_delete' => '1'),array('card_number' => $true));        
            $affected_rows = $this->_card_cancel($true,$card_type);

            if (!$affected_rows) {
                return array('result'=>0,'errorCard'=>'','errorMsg'=>'卡号取消失败');
            }
        }

        $this->rpc_log = array('rpc_desc' => '卡号取消','obj_type'=>'card');

        return array('result' => 1, 'data' => array('true_card' => $true));

    }

    function check_pro_card($filter){
        $data = array();
        if (!$filter['card_number']) return array('result'=>0,'errorMsg' => '卡号不能为空');
        if (!$filter['card_passwd']) return array('result'=>0,'errorMsg' => '卡密不能为空');
        $card_number = trim($filter['card_number']);
        $card_passwd = $filter['card_passwd'];
        $this->ci->load->model('pro_card_model');
        $cardinfo = $this->ci->pro_card_model->dump(array('card_number'=>$card_number),'card_number,card_passwd,is_used,is_sent,is_delete,start_time,to_date,product_id,card_money,remarks,is_freeze,card_type');

        if(empty($cardinfo)) return array('result'=>0,'errorMsg' => '券卡不存在');

        $md5_cardpass = md5(substr(md5($card_passwd), 0,-1).'f');
        if($md5_cardpass != $cardinfo['card_passwd']) return array('result'=>0,'errorMsg' => '密码错误');

        if($cardinfo['card_type']=='4'){
            return array('result'=>0,'errorMsg'=>'该卡为周期购提货券，仅支持用户在线兑换');
        }

        if($cardinfo['is_freeze']=='1'){
            return array('result'=>0,'errorMsg'=>'该卡已冻结');
        }
        if($cardinfo['is_sent']=='0'){
            return array('result'=>0,'errorMsg'=>'该卡未激活');
        }
        if($cardinfo['is_used']=='1'){
            return array('result'=>0,'errorMsg'=>'该卡已使用');
        }
        if($cardinfo['start_time']>date("Y-m-d")){
            return array('result'=>0,'errorMsg'=>'该提货券有效期还未开始');
        }
        if($cardinfo['is_delete']=='1'){
            return array('result'=>0,'errorMsg'=>'卡号无效');
        }
        $product_id_arr = explode(',',trim($cardinfo['product_id']));
        if(empty($product_id_arr)) return array('result'=>0,'errorMsg' => '券卡没有对应的商品');
        $product_id = implode(',', $product_id_arr);
        $sql = "select id,group_pro,product_name,cang_id from ttgy_product where id in(".$product_id.")";
        $result = $this->ci->db->query($sql)->result_array();
        $new_product_id_arr = array();
        $product_names = array();
        $cang_ids = array();
        foreach ($result as $key => $value) {
            if($value['group_pro']){
                $new_product_id_arr[$value['id']] = explode(',', trim($value['group_pro']));
            }else{
                $new_product_id_arr[$value['id']] = array($value['id']);
            }
            $product_names[$value['id']] = $value['product_name'];
            if($value['cang_id']) $cang_id = explode(',', $value['cang_id']);
            else $cang_id = array();
            if($key == 0){
                $cang_ids = $cang_id;
            }else{
                $cang_ids = array_intersect($cang_ids,$cang_id);
            }
        }
        $pro_data = array();
        $_one_data = array();
        foreach ($new_product_id_arr as $key => $value) {
            $products_id = array();
            $product_no = array();
            $product_id = implode(',', $value);
            $count_array = array_count_values($value);
            $sql = "SELECT product_id,product_no FROM ttgy_product_price WHERE product_id IN(".$product_id.") GROUP BY product_id";
            $result = $this->ci->db->query($sql)->result_array();
            foreach ($result as $val) {
                $add_nums = $count_array[$val['product_id']]-1;
                $product_no[$val['product_no']]['product_no'] = $val['product_no'];
                $product_no[$val['product_no']]['num'] = $product_no[$val['product_no']]['num']?$product_no[$val['product_no']]['num']+1:1;
                $product_no[$val['product_no']]['num'] += $add_nums; 
                $products_id[] = $val['product_id'];
            }
            $_one_data['group_name'] = $product_names[$key];
            $_one_data['product_group'] = array_values($product_no);


            if($diff_product = array_diff($value,$products_id)){
                $diff_pros = implode(',', $diff_product);
                //unset($product_no);
                $_one_data['errorMsg'] = '券卡中商品ID'.$diff_pros.'没有有效商品';
                //$_one_data['product_group'] = $product_no;
            }else{
                $_one_data['errorMsg'] = '';
            }
            
            $pro_data[] = $_one_data;
        }
        //$product_id = implode(',', $product_id_arr);
        $send_region = $this->getSendRgionByCangId($cang_ids);
        $data['is_expire'] = 0;
        if($cardinfo['to_date']<date("Y-m-d")){
            $data['is_expire'] = 1;
        }
        $data['pro_data'] = $pro_data;
        $data['card_money'] = $cardinfo['card_money'];
        $data['card_name'] = $cardinfo['remarks'];
        $data['start_time'] = $cardinfo['start_time'];
        $data['end_time'] = $cardinfo['to_date'];
        $data['send_region'] = $send_region;
        return array('result'=>1,'data'=>$data);
    }

    function exchange_pro_card($filter){
        $this->rpc_log = array('rpc_desc' => '券卡兑换','obj_type'=>'card','obj_name'=>$filter['card_number']);
        if (!$filter['order_name']) return array('result'=>0,'errorMsg' => '订单编号不能为空');
        $check = $this->check_pro_card($filter);
        if($check['result'] == 1){
            $card_number = trim($filter['card_number']);
            $oms_order = $filter['order_name'];
            $this->ci->load->model('pro_card_model');
            if($this->ci->pro_card_model->update(array('is_used' => '1','oms_order'=>$oms_order,'used_time'=>date('Y-m-d H:i:s')),array('card_number' => $card_number))){
                $data['msg'] = '兑换成功';
                return array('result'=>1,'data'=>$data);
            }else{
                return array('result'=>0,'errorMsg'=>'兑换失败，请稍后重新再试');
            }
        }else{
            return $check;
        }
    }

    function repair_pro_card($filter){
        $this->rpc_log = array('rpc_desc' => '券卡恢复','obj_type'=>'card','obj_name'=>$filter['card_number']);
        if (!$filter['order_name']) return array('result'=>0,'errorMsg' => '订单编号不能为空');
        if (!$filter['card_number']) return array('result'=>0,'errorMsg' => '券卡号不能为空');
        $this->ci->load->model('pro_card_model');
        $card_number = trim($filter['card_number']);
        $card_info = $this->ci->pro_card_model->dump(array('card_number'=>$card_number));
        if($card_info){
            if($card_info['oms_order'] != $filter['order_name']){
                return array('result'=>0,'errorMsg'=>'对应订单号不正确');
            }
            if($card_info['card_type'] == '4'){
                return array('result'=>0,'errorMsg'=>'该卡为周期购提货券，不允许取消使用');
            }
            if($card_info['is_used'] != 1){
                return array('result'=>0,'errorMsg'=>'该提货券未使用');
            }
            if($card_info['is_sent'] != 1){
                return array('result'=>0,'errorMsg'=>'该提货券未激活');
            }
            if($this->ci->pro_card_model->update(array('is_used' => '0','oms_order'=>''),array('card_number' => $card_number))){
                $data['msg'] = '恢复成功';
                return array('result'=>1,'data'=>$data);
            }else{
                return array('result'=>0,'errorMsg'=>'恢复失败，请稍后重新再试');
            }
        }
        return array('result'=>0,'errorMsg'=>'无此提货券');
    }

    private function getSendRgionByCangId($cang_ids){
        $data = array();
        if(empty($cang_ids)) return $data;
        $cang_ids = array_unique($cang_ids);
        $this->ci->db->select('region_id');
        $this->ci->db->from('warehouse_region');
        $this->ci->db->where_in('warehouse_id',$cang_ids);
        $result  = $this->ci->db->get()->result_array();
        $area_ids = array();
        foreach ($result as $value) {
            $area_ids[] = $value['region_id'];
        }
        $area_ids = array_unique($area_ids);
        if(empty($area_ids)) return $data;
        $a_ids = implode(',', $area_ids);
        $sql = "SELECT DISTINCT CASE WHEN a.pid=0 THEN a.name WHEN a2.pid=0 THEN a2.name WHEN a3.pid=0 THEN a3.name END AS region_name FROM ttgy_area a JOIN ttgy_area a2 ON a.id=a2.pid  JOIN ttgy_area a3 ON a2.id=a3.pid WHERE a3.id IN(".$a_ids.") OR a2.id IN(".$a_ids.") OR a.id IN(".$a_ids.")";
        $result = $this->ci->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
            $data[] = $value['region_name'];
        }
        return $data;
    }

    public function addCard($filter){
        $this->rpc_log = array('rpc_desc' => '添加优惠券','obj_type'=>'addcard','obj_name'=>$filter['tag']);
        if (!$filter['tag']) return array('result'=>0,'errorMsg' => 'tag码不能为空');
        if (!$filter['uids']) return array('result'=>0,'errorMsg' => '客户ID不能为空');
        //if (!$filter['batch']) return array('result'=>0,'errorMsg' => '客户ID不能为空');
        $uids = explode(',', $filter['uids']);
        $tag = $filter['tag'];
        if(empty($uids)) return array('result'=>0,'errorMsg' => '无有效客户ID');
        $batch = $filter['batch']?$filter['batch']:0;
        $sql = "select * from ttgy_mobile_card where card_tag='".$tag."'";
        $tmpl = $this->ci->db->query($sql)->row_array();
        if(empty($tmpl)) return array('result'=>0,'errorMsg' => '无优惠券模版');
        $card_prefix = $tmpl['p_card_number'].$batch."-";
        $check_prefix = $this->check_prefix($card_prefix);
        if(!$check_prefix){
            return array('result'=>0,'errorMsg' => '已有重复前缀');
        }
        $rules = array();
        $rules['p_card_number'] = $card_prefix;
        $rules['card_money'] = $tmpl['card_money'];
        $rules['product_id'] = $tmpl['product_id'];
        $rules['remarks'] = $tmpl['remarks'];
        $rules['time'] = $tmpl['card_time']?date('Y-m-'.$tmpl['card_time']):date('Y-m-d');
        $rules['to_date'] = date('Y-m-d',strtotime($rules['time']."+".($tmpl['validity']-1)." day"));
        $rules['order_money_limit'] = $tmpl['order_money_limit'];
        $rules['direction'] = '';
        $rules['channel'] = '';
        $rules['maketing'] = '0';
        $rules['card_type'] = $tmpl['card_m_type'];
        $rules['department'] = $tmpl['department'];
        $rules['tag'] = $tmpl['card_tag'];
        $insert_rules = serialize($rules);
        $insert_data = array('uids'=>implode(',', $uids),'rules'=>$insert_rules,'card_prefix'=>$card_prefix,'status'=>0,'time'=>time());
        $res = $this->ci->db->insert('add_card',$insert_data);
        if($res) return array('result'=>1,'data' => '添加成功');
        else return array('result'=>0,'errorMsg' => '添加失败');
    }

    private function check_prefix($p_card_number){
        $sql = "select id from ttgy_card where card_number like '".$p_card_number."%'";
        $res = $this->ci->db->query($sql)->row_array();
        if(empty($res)){
            $sql = "select id from ttgy_add_card where card_prefix ='".$p_card_number."'";
            $res = $this->ci->db->query($sql)->row_array();
            if(empty($res)){
                return true;
            }
        }
        return false;
    }

    public function getCardsInfo($filter){
        $this->rpc_log = array('rpc_desc' => '优惠券查看','obj_type'=>'card','obj_name'=>$filter['tag']?$filter['tag']:$filter['card_number']);
        if (!$filter['tag'] && !$filter['card_number']) return array('result'=>0,'errorMsg' => 'tag码或者卡号不能为空');
        $data = array();
        $tag = $filter['tag']?$filter['tag']:'';
        $card_number = $filter['card_number']?$filter['card_number']:'';
        if($card_number){
            $sql = "select c.*,t.* FROM ttgy_card c JOIN ttgy_card_type t ON c.card_number=t.card_number where c.card_number='".$card_number."'";
            $card = $this->ci->db->query($sql)->row_array();
            if(empty($card)) return array('result'=>0,'errorMsg' => '无对应优惠券信息');
            $data['card_number'] = $card['card_number'];
            $data['uid'] = $card['uid'];
            $data['sendtime'] = $card['sendtime'];
            $data['card_money'] = $card['card_money'];
            $data['maketing'] = ($card['maketing']==0)?'B2C':'O2O';
            $data['restr_good'] = ($data['restr_good']==1)?'商品专用权':'通用优惠券';
            $data['product_id'] = $card['product_id']?$card['product_id']:0;
            $data['is_used'] = $card['is_used'];
            $data['remarks'] = $card['remarks'];
            $data['start_date'] = $card['time'];
            $data['to_date'] = $card['to_date'];
            $data['order_money_limit'] = $card['order_money_limit']?$card['order_money_limit']:0;
            $data['direction'] = $card['direction']?$card['direction']:'';
            $data['type'] = $card['type']?$card['type']:'';
            $op_name = '';
            if($card['op_id']){
                $sql = "select * from s_admin where id=".$card['op_id'];
                $operator = $this->ci->db->query($sql)->row_array();
                $operator and $op_name = $operator['name']?$operator['name']:'';
            }
            $data['operator'] = $op_name;
            $data['department'] = $card['department']?$card['department']:'';
        }else{
            $sql = "select count(c.id) as count_num,c.*,t.* FROM ttgy_card c JOIN ttgy_card_type t ON c.card_number=t.card_number where t.tag='".$tag."' group by c.is_used";
            $cards = $this->ci->db->query($sql)->result_array();
            if(empty($cards)) return array('result'=>0,'errorMsg' => '无对应优惠券信息');
            $op_id = 0;
            $data['used_count'] = $data['unused_count'] = 0;
            foreach ($cards as $key => $card) {
                $data['card_money'] = $card['card_money'];
                $data['maketing'] = ($card['maketing']==0)?'B2C':'O2O';
                $data['restr_good'] = ($data['restr_good']==1)?'商品专用权':'通用优惠券';
                $data['product_id'] = $card['product_id']?$card['product_id']:0;
                //$data['is_used'] = $card['is_used'];
                $data['remarks'] = $card['remarks'];
                $data['order_money_limit'] = $card['order_money_limit']?$card['order_money_limit']:0;
                $data['direction'] = $card['direction']?$card['direction']:'';
                $data['type'] = $card['type']?$card['type']:'';
                $data['department'] = $card['department']?$card['department']:'';
                $op_id = $card['op_id']?$card['op_id']:0;
                if($card['is_used'] == 1){
                    $data['used_count'] = $card['count_num']?$card['count_num']:0;
                }else{
                    $data['unused_count'] = $card['count_num']?$card['count_num']:0;
                }
            }
            $data['all_count'] = $data['used_count']+$data['unused_count'];
            $op_name = '';
            if($op_id){
                $sql = "select * from s_admin where id=".$card['op_id'];
                $operator = $this->ci->db->query($sql)->row_array();
                $operator and $op_name = $operator['name']?$operator['name']:'';
            }
            $data['operator'] = $op_name;
        }
        return array('result'=>1,'data' => json_encode($data));
    }
}