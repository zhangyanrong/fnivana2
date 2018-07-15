<?php
/**
 * 消息中心
 *
 * PHP version 5.4
 * @category  PHP
 * @auth jack
 *
 **/    
namespace bll\pool;

class Msg
{
    
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    /*
     * 退款成功
     */
    public function msgRefund($filter)
    {
        $order_name = $filter['orderNo'];
        $content = $filter['content'];

        if (empty($order_name))
        {
            return array('result'=>0,'msg' => '订单号不能为空');
        }

        if (empty($content))
        {
            return array('result'=>0,'msg' => '退款明细内容不能为空');
        }

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name));

        if (empty($order))
        {
            return array('result' => 0,'msg' => '订单不存在');
        }

        $this->ci->load->model('msg_model');
        $msg = $this->ci->msg_model->get_msgInfo($order['uid'],1,2,$order_name);
        if(!empty($msg))
        {
            return array('result' => 0,'msg' => '退款消息已重复');
        }

        $this->ci->load->bll('msg');
        $params = array(
            'order_name'=>$order_name,
            'content'=>$content
        );
        $this->ci->bll_msg->addMsgRefund($params);

        $this->rpc_log = array('rpc_desc' => '消息:退款成功','obj_type'=>'msg','obj_name'=>$order_name);

        return array('result' => 1,'msg' => '退款消息成功');
    }

    /*
    * 客服消息
    */
    public function msgService($filter)
    {
        $uid = $filter['uid'];
        $content = $filter['content'];

        if (empty($uid))
        {
            return array('result'=>0,'msg' => '用户id不能为空');
        }

        if (empty($content))
        {
            return array('result'=>0,'msg' => '客服离线消息内容不能为空');
        }

        $this->ci->load->model('user_model');
        $user = $this->ci->user_model->dump(array('id' =>$uid));
        if(empty($user))
        {
            return array('result'=>0,'msg' => '用户不存在');
        }

        $this->ci->load->bll('msg');
        $params = array(
            'uid'=>$uid,
            'content'=>$content
        );
        $this->ci->bll_msg->addMsgService($params);

        $this->rpc_log = array('rpc_desc' => '消息:客服离线消息','obj_type'=>'msg');

        return array('result' => 1,'msg' => '客服消息成功');
    }

    /*
    * 物流消息
    */
    public function msgLogistic($filter)
    {
        $order_name = $filter['orderNo'];
        $content = $filter['content'];

        if (empty($order_name))
        {
            return array('result'=>0,'msg' => '订单号不能为空');
        }

        if (empty($content))
        {
            return array('result'=>0,'msg' => '物流配送内容不能为空');
        }

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name));

        if (empty($order))
        {
            return array('result' => 0,'msg' => '订单不存在');
        }

        $this->ci->load->bll('msg');
        $params = array(
            'order_name'=>$order_name,
            'content'=>$content
        );
        $this->ci->bll_msg->addMsgLogistic($params);

        $this->rpc_log = array('rpc_desc' => '消息:物流配送','obj_type'=>'msg','obj_name'=>$order_name);

        return array('result' => 1,'msg' => '物流消息成功');
    }

}