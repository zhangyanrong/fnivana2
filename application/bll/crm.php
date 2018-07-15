<?php
namespace bll;

class Crm
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->db_master = $this->ci->load->database('default_master', TRUE);
        $this->ci->load->helper('public');
    }

    //获取用户信息
    public function getUserInfo($params){

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billGetUserInfo($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }

    //用户是否是V4V5
    public function getUserRank($params){
        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billGetUserRank($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }


    //用户赠品补送        2016-01-14 add by dengjm
    public function sendGift($params){

        $mobile = trim($params['mobile']," ");

        if (empty($mobile) || !is_numeric($params['gift_send_id'])) {
            return array('code'=>300,'msg'=>'参数错误');
        }

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billSendGift($mobile,$params['gift_send_id']);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }


    //注销用户账户
    public function freezeUser($params){

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1[34578]\d{9}$/', $mobile)) {
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billFreezeUser($mobile);

            return $return_result;

        } else {
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }


    //获取最新消息
    public function getMessage($params){

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billGetMessage($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }

    //清空短信记录
    public function delMessage($params){

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billDelMessage($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }

    //获取充值交易
    public function tradeList($params){


        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billTradeList($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }

    //提货券订单
    public function cardOrder($params){

        $mobile = trim($params['mobile']," ");
        $search_start = empty($params['search_start']) ? '' : $params['search_start'];
        $search_end = empty($params['search_end']) ? '' : $params['search_end'];


        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billCardOrder($mobile,$search_start,$search_end);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }

    //积分查询
    public function userJf($params){

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billUserJf($mobile);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }

    //提货券查询
    public function tiHuoQuan($params){

        $card_number = $params['card_number'];

        if (empty($card_number)) {

            return array('code'=>300,'msg'=>'提货券卡号为空');
        } else {
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billTiHuoQuan($card_number);

            return $return_result;
        }

    }

    //充值卡查询
    public function chargeCard($params){

        $card_number = $params['card_number'];

        if (empty($card_number)) {

            return array('code'=>300,'msg'=>'充值卡卡号为空');
        } else {
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billChargeCard($card_number);

            return $return_result;
        }

    }

    //用户优惠券作废
    public function unSent($params){

        $card_number = $params['card_number'];
        if (empty($card_number)) {
            return array('code'=>300,'msg'=>'优惠券卡号为空');
        } else {
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billUnSent($card_number);

            return $return_result;
        }
    }

    //会员订单
    public function orderList($params){

        $mobile = trim($params['mobile']," ");
        $order_name = empty($params['order_name']) ? '' : $params['order_name'];
        $page = $params['page'] <=0 ? 1 : $params['page'];
        $pagesize = $params['pagesize'] <=0 ? 10 : $params['pagesize'];
        $search_start = empty($params['search_start']) ? '' : $params['search_start'];
        $search_end = empty($params['search_end']) ? '' : $params['search_end'];

        if (!empty($search_start)  && !empty($search_end) && $search_end< $search_start) {
            return array('code'=>300,'msg'=>'结束时间必须大于开始时间');
        }

        if (preg_match('/^1\d{10}$/', $mobile) || !empty($order_name)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billOrderList($mobile,$page,$pagesize,$order_name,$search_start,$search_end);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }

    //订单详情
    public function orderDetail($params){
        $order_id = $params['order_id'];

        if (is_numeric($order_id)) {
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billOrderDetail($order_id);

            return $return_result;
        } else {
            return array('code'=>300,'msg'=>'订单号错误');
        }

    }

    //添加优惠券     add by dengjm 2015-09-21
    public function saveCard($params){
        $mobile = trim($params['mobile']," ");


        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billSaveCard($params);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }

    }

    //操作用户积分      add by dengjm 2015-09-32
    public function operateJf($params){


        if (empty($params['reason'])) {
            return array('code'=>300,'msg'=>'操作积分原因必须填写！');
        }

        $mobile = trim($params['mobile']," ");

        if (preg_match('/^1\d{10}$/', $mobile)) {

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billOperateJf($params);

            return $return_result;

        }else{
            return array('code'=>300,'msg'=>'您输入的手机格式错误');
        }
    }

    //优惠券查询         add by dengjm 2015-10-28
    public function selectCard($params){

        if ($params['is_cardnumber']==1 && $params['is_ordername']==1) {
            return array('code'=>300,'msg'=>'传参过多');
        }

        if ($params['is_cardnumber']==1 || !empty($params['card_number'])) {

            if (empty($params['card_number'])) {
            return array('code'=>300,'msg'=>'优惠券号为空');
            }
            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billSelectCard($params['card_number']);

            return $return_result;

        } elseif($params['is_ordername']==1) {
            if (empty($params['order_name'])) {
                return array('code'=>300,'msg'=>'订单号不能为空');
            }

            $this->ci->load->model('crm_model');

            $return_result = $this->ci->crm_model->billSelectCardb($params['order_name']);

            return $return_result;
        } else {
            return array('code'=>300,'msg'=>'参数错误');
        }

    }

    //置充值赠品为已领取
    public function resetPro($params){

        if (empty($params['active_id'])) {
            return array('code'=>300,'msg'=>'赠品不存在');
        }

        $this->ci->load->model('crm_model');

        $return_result = $this->ci->crm_model->billResetPro($params);

        return $return_result;
    }

    //置充值赠品为未领取
    public function resetProUnused($params){
        if (empty($params['active_id'])) {
            return array('code'=>300,'msg'=>'赠品不存在');
        }

        $this->ci->load->model('crm_model');

        $return_result = $this->ci->crm_model->billResetProUnused($params);

        return $return_result;

    }

    //手动同步失败订单      add by dengjm 2015-10-29
    public function selfOmsOrder($params){
        if (!$params['order_name']) return array('code'=>300,'msg'=>'空订单号');

        $params['order_name'] = array_filter(explode(',', $params['order_name'] ));

        $this->ci->load->bll('pool/order');

        $return_result = $this->ci->bll_pool_order->pushoms($params);

        return $return_result;
    }

    //推送申述订单      addby dengjm 2015-11-09
    public function orderComplaints($params){

        $this->ci->load->model('crm_model');

        $return_result = $this->ci->crm_model->billOrderComplaints($params);

        if ($return_result['code'] == 300 || empty($return_result['data'])) {
            return ' ';
        }


        $this->ci->load->library('cryaes');
        $this->ci->cryaes->set_key(CRM_DATA_SECRET);
        $this->ci->cryaes->require_pkcs5();
        $decString = $this->ci->cryaes->encrypt(json_encode($return_result));


        $this->ci->load->library('curl',null,'http_curl');

       $rs = $this->ci->http_curl->request('http://call-center.internal.fruitday.com/appcomp/pushAppComp',array('data'=>$decString),'POST',array('timeout' => 180));
      //测试 $rs = $this->ci->http_curl->request('http://122.144.167.54:48180/callcenter/appcomp/pushAppComp',array('data'=>$decString),'POST',array('timeout' => 180));

        if ($rs['errorNumber'] || $rs['errorMessage']) {
            //return 'transfer fail--'.date('Y-m-d H:i:s',time())."\n";
            $this->ci->load->model('jobs_model');
            $this->ci->jobs_model->add(array('email'=>'songtao@fruitday.com','text'=>$rs['errorMessage'],'title'=>"申诉订单推送失败"), "email");
            return ' ';
        } else {
            $this->ci->load->library('cryaes');
            $this->ci->cryaes->set_key(CRM_DATA_SECRET);
            $this->ci->cryaes->require_pkcs5();
            $dec = $this->ci->cryaes->decrypt($rs['response']);
            $data = json_decode($dec,true);
            $data_str = implode(',', $data['success']);
            $ftime = date('Y-m-d H:i:s',time());

            $fsql ="INSERT INTO ttgy_crmsyn_log (quality_id,create_time) VALUES('".$data_str."','".$ftime."')";
            $fres = $this->ci->db_master->query($fsql);

            $sql = "UPDATE ttgy_quality_complaints SET crmsyn=1 WHERE id IN (".$data_str.")";
            $res = $this->ci->db_master->query($sql);
            // if ($res) {
            //     return 'update success--'.date('Y-m-d H:i:s',time())."\n";
            // } else {
            //     return 'update fail--'.date('Y-m-d H:i:s',time())."\n";
            // }

        }
    }


    //同步申诉的回调接口
    function crmsyc($params){
        $data = json_decode($params['data'],true);
        $data_str = implode(',', $data['success']);
        $sql = "UPDATE ttgy_quality_complaints SET crmsyn=1 WHERE id IN (".$data_str.")";
        $res = $this->ci->db_master->query($sql);
    }

    //crm推送处理的申诉订单过来
    function receiveComplaints($params){
        $data = json_decode($params['data'],true);

        foreach ($data as $key => $value) {
            if ($value['is_final']==0) {
                $sql2 = "UPDATE ttgy_quality_complaints SET status=2 WHERE id=".$value['quality_complaints_id'];
                $res2 = $this->ci->db_master->query($sql2);
                unset($data[$key]['is_final']);
            } elseif ($value['is_final']==1) {
                //service_status:0表示不可以评价，1表示可以评价，2表示评价完成
                //status：0表示未处理，2表示处理中，1，表示客服处理完成
                $sql3 = "UPDATE ttgy_quality_complaints SET status=1,service_status=1 WHERE id=".$value['quality_complaints_id'];
                $res3 = $this->ci->db_master->query($sql3);
                unset($data[$key]['is_final']);
            } else {
                //todo...
            }

            $sql1 = "SELECT id FROM ttgy_deal_complaints WHERE process_id=".$value['process_id'];
            $res1 = $this->ci->db_master->query($sql1)->row_array();
            if ($res1) {
                $data1 = array(
                   'log' => $value['log']
                    );
                $this->ci->db_master->where('process_id', $value['process_id']);
                $this->ci->db_master->update('deal_complaints', $data1);
                unset($data[$key]);
            }
        }

        $res = $this->ci->db_master->insert_batch('deal_complaints',$data);
        if ($res) {
            return array('code'=>200,'msg'=>'推送成功');
        } else {
            return array('code'=>300,'msg'=>'推送失败');
        }
    }


    //推送申诉反馈
    function receiveFeedback(){
        $sql = "select quality_complaints_id,stars,time,mobile,user_id from ttgy_complaints_feedback where is_send=0";
        $res = $this->ci->db_master->query($sql)->result_array();

        if (empty($res)) return '';


        $this->ci->load->library('cryaes');
        $this->ci->cryaes->set_key(CRM_DATA_SECRET);
        $this->ci->cryaes->require_pkcs5();
        $decString = $this->ci->cryaes->encrypt(json_encode($res));


        $this->ci->load->library('curl',null,'http_curl');

       //测试 $rs = $this->ci->http_curl->request('http://122.144.167.54:48180/callcenter/appcomp/pushAppCompEvaluation',array('data'=>$decString),'POST',array('timeout' => 180));
         $rs = $this->ci->http_curl->request('http://call-center.internal.fruitday.com/appcomp/pushAppCompEvaluation',array('data'=>$decString),'POST',array('timeout' => 180));
         if ($rs['errorNumber'] || $rs['errorMessage']) {
            $this->ci->load->model('jobs_model');
            $this->ci->jobs_model->add(array('email'=>'songtao@fruitday.com','text'=>$rs['errorMessage'],'title'=>"评价申诉反馈"), "email");
            return '';
        } else {
            $this->ci->load->library('cryaes');
            $this->ci->cryaes->set_key(CRM_DATA_SECRET);
            $this->ci->cryaes->require_pkcs5();
            $dec = $this->ci->cryaes->decrypt($rs['response']);
            $data = json_decode($dec,true);
            $data_str = implode(',', $data['success']);

            //记录推送成功的log
            $time = date('Y-m-d H:i:s',time());
            $data1 = array('quality_complaints_id'=>$data_str,'time'=>$time);
            $this->ci->db_master->insert('feedback_log',$data1);

            //更新推送状态
            $sql1 = "UPDATE ttgy_complaints_feedback SET is_send=1 WHERE quality_complaints_id IN (".$data_str.")";
            $this->ci->db_master->query($sql1);
        }

    }


    //CRM取消团购挂起的订单      add by dengjm 2015-11-18
    function cancelOrder($params){
        if (empty($params['id']) || !is_numeric($params['id'])) {
           return array('code'=>300,'msg'=>'订单不存在1');
        }

        $sql = "SELECT `status` FROM ttgy_group_member WHERE order_id=".$params['id']." LIMIT 1";

        $res = $this->ci->db_master->query($sql)->row_array();

        if (empty($res)) {
            return array('code'=>300,'msg'=>'订单不存在2');
        }

        if ($res['status']==3) {
            return array('code'=>300,'msg'=>'您的订单已经成团，请联系OMS取消订单');
        } else {
            $this->ci->load->bll('order');
            $result = $this->ci->bll_order->cancel($params['id'],$msg,false);
            return array('code' => $result ? 200 : 300,'msg' => $msg);
        }
    }

    //通过id找手机号码
    public function getMobile($params){
        if (!is_numeric($params['customerId']) || empty($params['customerId'])) {
            return array('code'=>300,'msg'=>'传参错误');
        }

        $fsql = "SELECT mobile FROM ttgy_user WHERE id=".$params['customerId'];
        $fres = $this->ci->db_master->query($fsql)->row_array();
        $mobile = $fres['mobile'];

        if (preg_match('/^1[34578]\d{9}$/', $mobile)) {
            return array('code'=>200,'msg'=>'获取电话号码成功','data'=>$mobile);
        } else {
            return array('code'=>200,'msg'=>'此用户未绑定手机号码');
        }
    }

    //提货券恢复
    public function resetProCard($params){

        if (empty($params['card_number'])) {
               return array('code'=>300,'msg'=>'卡号为空');
        }

        $sql = "select id,is_used,is_sent,active_time from ttgy_pro_card where card_number='".$params['card_number']."' limit 1";
        $res = $this->ci->db_master->query($sql)->row_array();


        if (empty($res)) {
             return array('code'=>300,'msg'=>'卡号不存在');
        }elseif ($res['is_used']==0) {
            return array('code'=>300,'msg'=>'已经恢复啦');
        }elseif ($res['is_sent']==0 || $res['active_time']=='0000-00-00 00:00:00') {
            return array('code'=>300,'msg'=>'卡号未激活');
        } else {
            $sql1 = "update ttgy_pro_card set is_used=0 where card_number='".$params['card_number']."'";
            $res1 = $this->ci->db_master->query($sql1);
            if ($res1) {
                return array('code'=>200,'msg'=>'提货券恢复成功');
            } else {
                return array('code'=>300,'msg'=>'服务器忙忙，请稍后再试');
            }
        }

    }

    //获取运费
    public function getPostFree($params){
        $this->ci->load->model('region_model');
        if (!is_numeric($params['weight']) || !is_numeric($params['goods_money']) || empty($params['city_name'])) {
            return array('code'=>300,'msg'=>'传参类型错误');
        }

        $weight = $params['weight'];
        $goods_money = $params['goods_money'];
        $city = $this->ci->region_model->getId($params['city_name']);
        if (empty($city)) {
            return array('code'=>300,'msg'=>'传入的城市不存在');
        }
        $area_freight_info = $this->ci->region_model->get_area_info($city);

        if(empty($area_freight_info['send_role'])) {
            $send_role = unserialize('a:4:{i:0;a:5:{s:3:"low";i:0;s:5:"hight";s:5:"49.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:20;s:19:"follow_weight_money";i:0;}i:1;a:5:{s:3:"low";i:50;s:5:"hight";s:5:"99.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:10;s:19:"follow_weight_money";i:0;}i:2;a:5:{s:3:"low";i:100;s:5:"hight";s:6:"199.99";s:12:"first_weight";i:8;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:2;}i:3;a:5:{s:3:"low";i:200;s:5:"hight";i:9999;s:12:"first_weight";i:9999;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:0;}}');
        }else{
            $send_role = unserialize($area_freight_info['send_role']);
        }

        foreach ($send_role as $key => $value) {
            if($value['hight']==9999){//运费规则上限,每多x元，首重增加ykg
                // $first_weight_tmp = $value['first_weight'] + floor(($goods_money-$value['low'])/$value['increase'])*$value['add_first_weight'];
                // if($weight <= $first_weight_tmp) {
                //     $method_money = $value['first_weight_money'];
                // }else {
                //     $method_money = $value['first_weight_money'] + ceil(($weight - $first_weight_tmp))*$value['follow_weight_money'];
                // }
                return array("code"=>200, "data"=>0);
            }else if($goods_money >= $value['low'] && $goods_money <= $value['hight']){

                if($weight <= $value['first_weight']) {
                    $method_money = $value['first_weight_money'];
                }else {
                    $method_money = $value['first_weight_money'] + ceil(($weight - $value['first_weight']))*$value['follow_weight_money'];
                }
                return array("code"=>200, "data"=>$method_money);
            }
        }

        return array("code"=>200, "data"=>0);
    }

    public function checkUserRank($params){
        if(empty($params['customerId']))  return array('code'=>300,'msg'=>'customerId为空');
        $this->ci->load->model('user_model');
        $this->ci->user_model->upgrade_rank($params['customerId']);
        return array("code"=>200, "data"=>0);
    }

    /**
     * 会员解冻
     * @param $params
     * @return array
     */
    public function userThaw($params)
    {
        if (empty($params['mobile']) || !preg_match('/^1\d{10}$/', $params['mobile'])) {
            return array('code' => 300, 'msg' => '您输入的手机格式错误');
        }

        $user = $this->ci->db->query('select id from ttgy_user where mobile = ?', array($params['mobile']))->result_array();
        if (empty($user)) {
            return array('code' => 300, 'msg' => '会员不存在');
        }

        if (count($user) > 1) {
            return array('code' => 300, 'msg' => '手机号对应多个会员, 请联系技术部');
        }
        $uid = $user[0]['id'];
        $this->ci->db->update('ttgy_user', array('freeze' => 0), array('id' => $uid));
        $this->ci->db->update('ttgy_login_error', array('num' => 0), array('uid' => $uid));
        return array('code' => 200, 'msg' => '会员解冻成功');
    }

    /**
     * 搜索热门关键字
     * @return array
     */
    public function hotSearchKey()
    {
        $this->ci->load->model('product_model');
        $searchKey = $this->ci->product_model->get_search_key();
        return array('code' => 200, 'data' => $searchKey);
    }

    /**
     * 商品查询
     * @param $params
     * @return array
     */
    public function productSearch($params)
    {
        if (empty($params['keyword'])) {
            return array('code' => 300, 'msg' => 'key不能为空');
        }
        if (empty($params['region'])) {
            $params['region'] = 106092;
        }
        if (!is_numeric($params['region'])) {
            $area = $this->ci->db->query('select id from ttgy_area where pid = 0 and name = ?', array(trim($params['region'])))->row_array();
            if (empty($area)) {
                return array('code' => 300, 'msg' => $params['region'] . ' 该省不存在');
            }
            $region = $area['id'];
        } else {
            $region = (int) $params['region'];
        }

        $this->ci->load->bll('product');
        $products = $this->ci->bll_product->search(array(
            'keyword' => trim($params['keyword']),
            'region' => $region,
        ));
        if (isset($params['code'])) {
            return $products;
        }
        return array('code' => 200, 'data' => $products['list']);
    }

    /**
     * 商品详情
     * @param $params
     * @return array
     */
    public function productInfo($params)
    {
        if (empty($params['id'])) {
            return array('code' => 300, 'msg' => 'id不能为空');
        }
        $this->ci->load->bll('product');
        $product = $this->ci->bll_product->productInfo(array(
            'id' => (int)$params['id'],
        ));
        if (isset($params['code'])) {
            return $product;
        }
        return array('code' => 200, 'data' => $product);
    }

    public function add_black_list($filter){
        if (!$filter['black_list']){
            return array('code'=> 300,'300'=>'参数错误');
        }
        $this->ci->load->model('user_model');
        $mobiles = array();
        $credit_rank = array();
        $filter['black_list'] = json_decode($filter['black_list'],true);
        foreach ($filter['black_list'] as $key => $value) {
            $mobiles[] = $value['mobile'];
            $credit_rank[$value['mobile']] = isset($value['credit_rank'])?intval($value['credit_rank']):1;
        }
        $where = array();
        $where['mobile'] = $mobiles;
        $user_list = $this->ci->user_model->getList('id,mobile',$where);
        $black_list = array();
        foreach ($user_list as $value) {
            $black_user['uid'] = $value['id'];
            $black_user['credit_rank'] = $credit_rank[$value['mobile']];
            $black_list[] = $black_user;
        }
        $result = $this->ci->user_model->addBlackList($black_list);
        //$this->rpc_log = array('rpc_desc' => '会员黑名单添加','obj_type'=>'addBlackList');
        if($result) return  array('code'=> 200,'msg'=>'操作成功');
        else return array('code'=> 300,'msg'=>'操作失败');
    }

    public function remove_black_list($filter){
        if (!$filter['black_list']){
            return array('code'=> 300,'msg'=>'参数错误');
        }
        $mobiles = array();
        $filter['black_list'] = json_decode($filter['black_list'],true);
        foreach ($filter['black_list'] as $key => $value) {
            $mobiles[] = $value['mobile'];
        }
        $this->ci->load->model('user_model');
        $where = array();
        $where['mobile'] = $mobiles;
        $user_list = $this->ci->user_model->getList('id',$where);
        $uids = array();
        foreach ($user_list as $value) {
            $uids['id'] = $value['id'];
        }
        $result = $this->ci->user_model->removeBlackList($uids);
        //$this->rpc_log = array('rpc_desc' => '会员黑名单移除','obj_type'=>'removeBlackList');
        if($result) return  array('code'=> 200,'msg'=>'操作成功');
        else return array('code'=> 300,'msg'=>'操作失败');
    }

    /**
     * 周期购订单详情列表
     * @param $params
     * @return array
     */
    public function sOrderList($params){
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmOrderList($params);
    }

    /**
     * 周期购订单取消
     * @param $params
     * @return array
     */
    public function sOrderCancel($params)
    {
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmOrderCancel($params);
    }

    /**
     * 周期购配送订单取消
     * @param $params
     * @return array
     */
    public function sDeliveryOrderCancel($params)
    {
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmDeliveryOrderDoCancel($params, 0);
    }

    /**
     * 周期购配送订单恢复
     * @param $params
     * @return array
     */
    public function sDeliveryOrderRollback($params)
    {
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmDeliveryOrderRollback($params);
    }

    /**
     * 周期购配送订单退换
     * @param $params
     * @return array
     */
    public function sDeliveryOrderExchange($params){
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmDeliveryOrderDoCancel($params, 1);
    }

    /**
     * crm周期购商品信息
     * @param $params
     * @return array
     */
    public function sOrderProduct($params)
    {
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->crmOrderProduct($params);
    }


    /*
    * 客服离线消息 － oms
    */
    public function msgService($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('msg');
        $params = array(
            'uid'=>$params['uid'],
            'content'=>$params['content']
        );
        $this->ci->bll_msg->addMsgService($params);

        return array("code" => "200", "msg" => "succ");
    }

    public function getRankLog($params){
        $mobile = trim($params['mobile']);
        $uid = $params['customerId'];
        if(empty($mobile) && empty($uid)) return array("code" => "300", "msg" => "缺少手机号或客户ID");
        $this->ci->load->model('user_model');
        if($uid){

        }else{
            if(preg_match('/^1\d{10}$/', $mobile)) {
                $u_info = $this->ci->user_model->selectUsers('id',array('mobile'=>$mobile));
                if(empty($u_info)){
                    return array('code' => 300, 'msg' => '此手机号无对应帐号');
                }
                if(count($u_info)>1){
                    return array('code' => 300, 'msg' => '此手机号对应多个帐号，请联系宋涛！');
                }
                $uid = $u_info[0]['id'];
            }else{
                return array('code' => 300, 'msg' => '您输入的手机格式错误');
            }
        }
        $user_rank_log = $this->ci->user_model->getUserRankLog($uid);
        $msg = 'succ';
        if(empty($user_rank_log)){
            $msg = '该用户暂无升级信息';
        }
        return array('code' => 200, 'msg' => $msg,'data'=>$user_rank_log);
    }

    public function getInvite($params){
        $mobile = trim($params['mobile']);
        $uid = $params['customerId'];
        if(empty($mobile) && empty($uid)) return array("code" => "300", "msg" => "缺少手机号或客户ID");
        $this->ci->load->model('user_model');
        if($uid){

        }else{
            if(preg_match('/^1\d{10}$/', $mobile)) {
                $u_info = $this->ci->user_model->selectUsers('id',array('mobile'=>$mobile));
                if(empty($u_info)){
                    return array('code' => 300, 'msg' => '此手机号无对应帐号');
                }
                if(count($u_info)>1){
                    return array('code' => 300, 'msg' => '此手机号对应多个帐号，请联系宋涛！');
                }
                $uid = $u_info[0]['id'];
            }else{
                return array('code' => 300, 'msg' => '您输入的手机格式错误');
            }
        }
        $user_invite = $this->ci->user_model->get_user_invite($uid);
        $msg = 'succ';
        if(empty($user_invite)){
            $msg = '该用户暂无邀请好友信息';
        }
        return array('code' => 200, 'msg' => $msg,'data'=>$user_invite);
    }
}
