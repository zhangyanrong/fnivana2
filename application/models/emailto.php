<?php
class Emailto extends CI_model
{
    private $email;
    private $from;
    private $message;
    private $messageType;
    private $user;
    private $point;

    function __construct()
    {
        parent::__construct();

    }

    function setEmail($email)
    {
        if( ! $this->isEmail( $email ) )    
            return array("error"=>1,"msg"=>"邮箱错误");

        $this->email = $email;
    }

    function setUser(Users $user)
    {
        $this->user = $user; 
    }

    function setPoint(User_points $point)
    {
        $this->point = $point; 
    }

    function setMessageType($type)
    {
         $types = array("register","verify");

         if( ! in_array( $type, $types ) )
             return array('error'=>1,"msg"=>"错误请求");

         $this->messageType = $type;
    }

    function send()
    {
        $this->setMessage();

        $CI = & get_instance();
        $CI->load->library("email");
        $CI->email->set_newline("\r\n");
        $CI->email->from('noreply@fruitday.com','天天果园');
        $CI->email->to($this->email);
        $CI->email->subject($this->message['title']);
        $CI->email->message($this->message['message']);

        //echo $CI->email->print_debugger();exit;
        if( $CI->email->send() ){
            return array("code"=>200,"msg"=>"邮件已经发送");
        }else{
            return array("code"=>300,"msg"=>"邮件发送失败");
        }
    }

    private function setMessage()
    {
        if( $this->messageType == "verify" )
        {	
            $user = $this->user->getUser();

            $email_status_url = "http://www.fruitday.com/service/email/verify/".$user->randcode;
            $point =$this->point->getPoint("verify_email");
            $msg_sent_points  = $point['reason'];

            $this->message['title'] = "天天果园邮箱验证邮件";
            $this->message['message'] = "请点击下面链接验证您的邮箱：{$email_status_url}\n{$msg_sent_points}，如有疑问 ，请登录： http://www.fruitday.com ，\n或拨打400-720-0770客服电话。";
           //error_log(var_export($this->message, true),3,"c:/wamp/www/ff.txt");

        } else {
            //other todos 
        }
    }

	function isEmail($email) {
		if( ! trim( $email ) )
			return FALSE;

		preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*.\w+([-.]\w+)*/",$email,$matches);

		if( isset( $matches[0] ) && $matches[0] )
			return TRUE;
		else
			return FALSE;
	}
}
?>
