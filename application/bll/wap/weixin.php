<?php
namespace bll\wap;
include_once("wap.php");

class Weixin extends wap
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('weixin');
		return $this->ci->bll_weixin->$method($aParams[0]);
	}
}

# end of this file
