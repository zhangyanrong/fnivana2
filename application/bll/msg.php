<?php
namespace bll;

class Msg
{
    //消息中心-展示
    private $msg_show = array(
        0=>array('title'=>'果园公告','type'=>'6','text'=>'最新果园实时公告','is_red'=>0),
        1=>array('title'=>'优惠促销','type'=>'7','text'=>'最新优惠促销信息','is_red'=>0),
        2=>array('title'=>'账户消息','type'=>'2','text'=>'最新账户消息提醒','is_red'=>0),
        3=>array('title'=>'交易消息','type'=>'1','text'=>'最新交易变更提醒','is_red'=>0),
        4=>array('title'=>'物流助手','type'=>'5','text'=>'最新物流订单消息','is_red'=>0),
        5=>array('title'=>'果园客服','type'=>'4','text'=>'果园金牌客服全天为您服务','is_red'=>0),
    );

    private $msg_show_v2 = array(
        0=>array('title'=>'果园公告','type'=>'6','text'=>'最新果园实时公告','is_red'=>0),
        1=>array('title'=>'优惠促销','type'=>'7','text'=>'最新优惠促销信息','is_red'=>0),
        2=>array('title'=>'账户消息','type'=>'2','text'=>'最新账户消息提醒','is_red'=>0),
        3=>array('title'=>'交易消息','type'=>'1','text'=>'最新交易变更提醒','is_red'=>0),
        4=>array('title'=>'物流助手','type'=>'5','text'=>'最新物流订单消息','is_red'=>0),
        5=>array('title'=>'评论与赞','type'=>'3','text'=>'最新发现消息提醒','is_red'=>0),
        6=>array('title'=>'果园客服','type'=>'4','text'=>'果园金牌客服全天为您服务','is_red'=>0),
        //7=>array('title'=>'订阅消息','type'=>'8','text'=>'最新订阅信息','is_red'=>0),
    );

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->model('msg_model');
        $this->ci->load->model('msg_notice_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_product_model');
        $this->ci->load->model('user_model');

        $this->ci->load->helper('public');
    }


    /*
     * 消息中心
     */
    public function center($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期');
        }

        $uid = $this->ci->login->get_uid();

        if(strcmp($params['version'], '4.3.0') >= 0)
        {
            $arr_msg = $this->msg_show_v2;
        }
        else
        {
            $arr_msg = $this->msg_show;
        }

        $arr_red = $this->ci->msg_notice_model->dump(array('uid'=>$uid));
        if(empty($arr_red))
        {
            $arr_red = array(
                'id'=>0,
                'uid'=>0,
                'last_cart_time'=>'0000-00-00 00:00:00',
                'last_order_time'=>'0000-00-00 00:00:00',
                'last_user_time'=>'0000-00-00 00:00:00',
                'last_comment_time'=>'0000-00-00 00:00:00',
                'last_custom_time'=>'0000-00-00 00:00:00',
                'last_notice_time'=>'0000-00-00 00:00:00',
                'last_trace_time'=>'0000-00-00 00:00:00',
                'last_subscribe_time'=>'0000-00-00 00:00:00',
            );
        }

        //构建结构
        $this->ci->load->bll('article');

        foreach($arr_msg as $key=>$val)
        {
            $time = '';
            $times = 0;

            if($val['type'] == 1)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,$val['type']);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_order_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 2)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,$val['type']);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_user_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 3)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,$val['type']);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_comment_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 4)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,$val['type']);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_custom_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
                $arr_msg[$key]['time'] = $time;
            }
            else if($val['type'] == 5)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,$val['type']);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_trace_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 6)
            {
                $msg_time = $this->ci->bll_article->getList(array('class_id'=>1,'curr_page'=>0,'page_size'=>1));
                if($msg_time['count'] >0)
                {
                    $time = $msg_time['articles'][0]['time'];
                    $times = strtotime($msg_time['articles'][0]['time']);
                    $arr_msg[$key]['text'] = $msg_time['articles'][0]['title'];

                    if($arr_red['last_notice_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 7)
            {
                $msg_time = $this->ci->bll_article->getPromotionMsg(array('curr_page'=>0,'page_size'=>1));
                if($msg_time['count'] >0)
                {
                    $time = $msg_time['articles'][0]['online_time'];
                    $times = strtotime($msg_time['articles'][0]['online_time']);
                    $arr_msg[$key]['text'] = $msg_time['articles'][0]['title'];

                    if($arr_red['last_cart_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            else if($val['type'] == 8)
            {
                $msg_time = $this->ci->msg_model->get_msgTime($uid,6);
                if(!empty($msg_time))
                {
                    $time = $msg_time['time'];
                    $times = strtotime($msg_time['time']);
                    $arr_msg[$key]['text'] = $msg_time['content'];

                    if($arr_red['last_subscribe_time'] < $time)
                    {
                        $arr_msg[$key]['is_red'] = 1;
                    }
                }
            }
            
            $arr_msg[$key]['time'] = $time;
            $arr_msg[$key]['times'] = $times;
        }

        //sort
        foreach ($arr_msg as $key => $row) {
            $sort_times[$key]  = $row['times'];
            unset($arr_msg[$key]['times']);
        }
        array_multisort($sort_times, SORT_DESC,$arr_msg);

        return array('code'=>200,'msg'=>'','data'=>$arr_msg);
    }

    /*
     * 消息中心 - 列表
     */
    public function msgList($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $pageSize = $params['page_size'] ? $params['page_size'] : 10;
        $currPage = $params['curr_page'] ? $params['curr_page'] : 0;

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期');
        }
        $uid = $this->ci->login->get_uid();
        $allow_class = array(1,2,3,5,6,7,8);

        if(!in_array($params['type'],$allow_class))
        {
            return array('code'=>'300','msg'=>'消息类型不存在');
        }

        $list = array();
        $this->ci->load->bll('article');

        $fields = 'id,uid,content,class,type,order_name,time,title,product_id,article_id';
        $offset = ($currPage - 1) * $pageSize;

        //类型
        $msg_type = $this->ci->config->item('msg_type');

        if($params['type'] == 1)  //交易消息
        {
            $filter = [
                '(time > "' . date('Y-m-d H:i:s', strtotime('-45 days')) . '")' => null,
                'class'=>1,
                'uid'=>$uid
            ];
            $list = $this->ci->msg_model->getList($fields, $filter, $offset, $pageSize, 'time DESC');

            foreach($list as $key=>$val)
            {
                $list[$key]['title'] = $msg_type[$val['type']];
            }
        }
        else if($params['type'] == 2)  //账户消息
        {
            $filter = [
                '(time > "' . date('Y-m-d H:i:s', strtotime('-45 days')) . '")' => null,
                'class'=>2,
                'uid'=>$uid
            ];
            $list = $this->ci->msg_model->getList($fields, $filter, $offset, $pageSize, 'time DESC');

            foreach($list as $key=>$val)
            {
                $list[$key]['title'] = $msg_type[$val['type']];
            }
        }
        else if($params['type'] == 3) //评论和赞
        {
            $filter = [
                '(time > "' . date('Y-m-d H:i:s', strtotime('-45 days')) . '")' => null,
                'class'=>3,
                'uid'=>$uid
            ];
            $list = $this->ci->msg_model->getList($fields, $filter, $offset, $pageSize, 'time DESC');
        }
        else if($params['type'] == 5)  //物流助手
        {
            $filter = [
                '(time > "' . date('Y-m-d H:i:s', strtotime('-45 days')) . '")' => null,
                'class'=>5,
                'uid'=>$uid
            ];
            $list = $this->ci->msg_model->getList($fields, $filter, $offset, $pageSize, 'time DESC');

            foreach($list as $key=>$val)
            {
                $list[$key]['title'] = '订单编号:'.$val['order_name'];
            }
        }
        else if($params['type'] == 6)  //果园公告
        {
            $article = $this->ci->bll_article->getList(array('class_id'=>1,'curr_page'=>$currPage,'page_size'=>$pageSize));
            $list = $article['articles'];
        }
        else if($params['type'] == 7)  //优惠促销
        {
            $proMsg = $this->ci->bll_article->getPromotionMsg(array('curr_page'=>$currPage,'page_size'=>$pageSize));
            $list = $proMsg['articles'];
        }
        else if($params['type'] == 8)  //订阅消息
        {
            $filter = [
                '(time > "' . date('Y-m-d H:i:s', strtotime('-45 days')) . '")' => null,
                'class'=>6,
                'uid'=>$uid
            ];
            $list = $this->ci->msg_model->getList($fields, $filter, $offset, $pageSize, 'time DESC');

            foreach($list as $key=>$val)
            {
                $list[$key]['title'] = $msg_type[$val['type']];;
            }
        }

        return array('code'=>200,'msg'=>'','data'=>$list);
    }

    /*
     * 消息中心 - 详情(果园公告)
     */
    public function msgDetail($params)
    {
        $require_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => 'id can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('article');
        $info = $this->ci->bll_article->getInfo(array('id'=>$params['id']));

        return array('code'=>200,'msg'=>'','data'=>$info);
    }


    /*
     * 消息中心 - 交易成功
     */
    public function addMsgPay($params)
    {
        $require_fields = array(
            'order_name'=>array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $orderInfo = $this->ci->order_model->dump(array('order_name' => $params['order_name']));
        if(!empty($orderInfo))
        {
            $product_name ='';
            $money = number_format($orderInfo['money'] + $orderInfo['use_money_deduction'],2,'.','');
            $products = $this->ci->order_product_model->get_products($orderInfo['id']);
            if(isset($products[0]['product_name']))
            {
                $product_name = $products[0]['product_name'];
            }

            $msg_text = '订单号：'.$params['order_name'].'，您购买的'.$product_name.'等商品已交易完成，共消费'.$money.'元';

            $msg_data = array(
                'uid'=>$orderInfo['uid'],
                'content'=>$msg_text,
                'class'=>'1',
                'type'=>'1',
                'order_name'=>$params['order_name'],
                'time'=>date('Y-m-d H:i:s')
            );

            $this->ci->msg_model->addMsg($msg_data);
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 退款成功
     */
    public function addMsgRefund($params)
    {
        $require_fields = array(
            'order_name'=>array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $orderInfo = $this->ci->order_model->dump(array('order_name' => $params['order_name']));
        if(!empty($orderInfo))
        {
            $msg_data = array(
                'uid'=>$orderInfo['uid'],
                'content'=>$params['content'],
                'class'=>'1',
                'type'=>'2',
                'order_name'=>$orderInfo['order_name'],
                'time'=>date('Y-m-d H:i:s')
            );
            $this->ci->msg_model->addMsg($msg_data);
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 物流信息
     */
    public function addMsgLogistic($params)
    {
        $require_fields = array(
            'order_name'=>array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $orderInfo = $this->ci->order_model->dump(array('order_name' => $params['order_name']));
        if(!empty($orderInfo))
        {
            $msg_data = array(
                'uid'=>$orderInfo['uid'],
                'content'=>$params['content'],
                'class'=>'5',
                'order_name'=>$orderInfo['order_name'],
                'time'=>date('Y-m-d H:i:s')
            );
            $this->ci->msg_model->addMsg($msg_data);
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 果园客服
     */
    public function addMsgService($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $msg_data = array(
            'uid'=>$params['uid'],
            'content'=>$params['content'],
            'class'=>'4',
            'time'=>date('Y-m-d H:i:s')
        );
        $this->ci->msg_model->addMsg($msg_data);

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 余额
     */
    public function addMsgBalance($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'money'=>array('required' => array('code' => '500', 'msg' => 'content can not be null')),
            'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $user = $this->ci->user_model->dump(array('id' => $params['uid']));
        if(empty($user))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        if($params['type'] == 1)  //余额支出
        {
            if(!empty($params['order_name']))
            {
                $msg_text = '余额支出涉及订单号'.$params['order_name'].'共'.$params['money'].'元,当前余额为'.$user['money'].'元';
                $msg_data = array(
                    'uid'=>$params['uid'],
                    'content'=>$msg_text,
                    'class'=>'2',
                    'type'=>'3',
                    'order_name'=>$params['order_name'],
                    'time'=>date('Y-m-d H:i:s')
                );
                $this->ci->msg_model->addMsg($msg_data);
            }
            else
            {
                return array('code'=>'300','msg'=>'fail');
            }
        }
        else if($params['type'] == 2)  //余额收入
        {
            $msg_text = '余额已充值'.$params['money'].'元,当前余额为'.$user['money'].'元';
            $msg_data = array(
                'uid'=>$params['uid'],
                'content'=>$msg_text,
                'class'=>'2',
                'type'=>'4',
                'time'=>date('Y-m-d H:i:s')
            );
            $this->ci->msg_model->addMsg($msg_data);
        }
        else
        {
            return array('code'=>'300','msg'=>'fail');
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 等级
     */
    public function addMsgRank($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'rank' => array('required' => array('code' => '500', 'msg' => 'type can not be null')),
            'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        if(!in_array($params['rank'],array(1,2,3,4,5,6)))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $params['rank'] = $params['rank'] - 1;

        if($params['type'] == 1)  //升级
        {
            $this->ci->msg_model->addMsgAccount($params['uid'],$params['rank'],1);
        }
        else if($params['type'] == 2) //降级
        {
            $this->ci->msg_model->addMsgAccount($params['uid'],$params['rank'],2);
        }
        else
        {
            return array('code'=>'300','msg'=>'fail');
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 积分
     */
    public function addMsgPoint($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $user = $this->ci->user_model->dump(array('id' => $params['uid']));
        if(empty($user))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $msg_text = '您'.$params['content'];
        $msg_data = array(
            'uid'=>$params['uid'],
            'content'=>$msg_text,
            'class'=>'2',
            'type'=>'7',
            'time'=>date('Y-m-d H:i:s')
        );
        $this->ci->msg_model->addMsg($msg_data);

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 首页红点提醒
     */
    public function msgCenterRed($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'type'=>array('required' => array('code' => '500', 'msg' => 'type can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期');
        }

        $uid = $this->ci->login->get_uid();

        $allow_class = array(1,2,3,4,5,6,7);

        if(!in_array($params['type'],$allow_class))
        {
            return array('code'=>'300','msg'=>'not allow type');
        }

        $msg = $this->ci->msg_notice_model->dump(array('uid'=>$uid));
        if(!empty($msg))
        {
            $this->ci->msg_notice_model->updateRedTime($uid,$params['type']);  //更新提醒
        }
        else
        {
            $this->ci->msg_notice_model->addRedTime($uid,$params['type']);   //新增提醒
        }

        return array('code'=>'200','msg'=>'','data'=>'');
    }

    /*
     * 消息中心 - 红点提醒
     */
    public function msgRed($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'msg_id'=>array('required' => array('code' => '500', 'msg' => 'type can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期');
        }

        $uid = $this->ci->login->get_uid();
        $msg = $this->ci->msg_model->dump(array('id' => $params['msg_id'],'uid'=>$uid));
        if(empty($msg))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $this->ci->msg_model->update_redTime($params['msg_id']);

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 评论
     */
    public function addMsgComment($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'article_id'=>array('required' => array('code' => '500', 'msg' => 'article_id can not be null')),
            'title'=>array('required' => array('code' => '500', 'msg' => 'title can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $user = $this->ci->user_model->dump(array('id' => $params['uid']));
        if(empty($user))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $msg_data = array(
            'uid'=>$params['uid'],
            'article_id'=>$params['article_id'],
            'title'=>$params['title'],
            'content'=>$params['content'],
            'class'=>'3',
            'type'=>'8',
            'time'=>date('Y-m-d H:i:s')
        );
        $this->ci->msg_model->addMsg($msg_data);

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 点赞
     */
    public function addMsgWorth($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'article_id'=>array('required' => array('code' => '500', 'msg' => 'article_id can not be null')),
            'title'=>array('required' => array('code' => '500', 'msg' => 'title can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $user = $this->ci->user_model->dump(array('id' => $params['uid']));
        if(empty($user))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $msg = $this->ci->msg_model->dump(array('article_id' => $params['article_id'],'uid'=>$params['uid'],'type'=>9));
        if(empty($msg))
        {
            $msg_data = array(
                'uid'=>$params['uid'],
                'article_id'=>$params['article_id'],
                'title'=>$params['title'],
                'content'=>$params['content'],
                'class'=>'3',
                'type'=>'9',
                'time'=>date('Y-m-d H:i:s')
            );
            $this->ci->msg_model->addMsg($msg_data);
        }
        else
        {
            $msg_data = array(
                'uid'=>$params['uid'],
                'article_id'=>$params['article_id'],
                'title'=>$params['title'],
                'content'=>$params['content'],
                'class'=>'3',
                'type'=>'9',
                'time'=>date('Y-m-d H:i:s')
            );
            $this->ci->msg_model->update_data($msg['id'],$msg_data);
        }

        return array('code'=>'200','msg'=>'succ');
    }

    /*
     * 消息中心 - 订阅通知(到货提醒)
     */
    public function addMsgTake($params)
    {
        $require_fields = array(
            'uid'=>array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
            'product_id'=>array('required' => array('code' => '500', 'msg' => 'product_id can not be null')),
            'content'=>array('required' => array('code' => '500', 'msg' => 'content can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $user = $this->ci->user_model->dump(array('id' => $params['uid']));
        if(empty($user))
        {
            return array('code'=>'300','msg'=>'fail');
        }

        $msg_data = array(
            'uid'=>$params['uid'],
            'product_id'=>$params['product_id'],
            'content'=>$params['content'],
            'class'=>'6',
            'type'=>'10',
            'time'=>date('Y-m-d H:i:s')
        );
        $this->ci->msg_model->addMsg($msg_data);

        return array('code'=>'200','msg'=>'succ');
    }

}
