<?php
namespace bll\wap;
include_once("wap.php");

class Alipay extends wap
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('alipay');
        
		return $this->ci->bll_alipay->$method($aParams[0]);
	}
}
