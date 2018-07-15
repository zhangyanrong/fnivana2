<?php
namespace bll\risk;


/**
* @支付宝蚁盾风控接口
* @手机号Rain评分服务
*/
class Mobile {

    private $ci;
    
    
	public function __construct()
    {
        $this->ci = & get_instance();   
        
		$this->ci->load->library('ebuckler');
	}
    
    
    public function  batch()
    {
        set_time_limit(0);
        
        $handle = fopen( dirname(__FILE__) . '\data\20151214.txt', "r" );
        
        if ($handle) {
            $params['source'] = 'batch';
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                $params['mobile'] = trim($buffer);

                if(strlen($params['mobile']) == 11){
                    $this->ci->ebuckler->score( $params, false );
                }
            }
            fclose($handle);
        }
    }
    
    
    /***
     *@desc 手机号Rain评分服务
     * mobile 手机号【Y】
     * from 请求来源【Y】
     * order_id 订单号【N】
     * return array
     **/
	public function score( $params )
	{
        //$params['mobile'] = '18606022223';
        //$params['from'] = 'reg';
        //$params['refresh'] = 'true';
        
        $refresh = $params['refresh'] == 'true' ? true : false;
        
        return $this->ci->ebuckler->score( $params, $refresh );
	}
    
}