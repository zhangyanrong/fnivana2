<?php
namespace bll\pc;
include_once("pc.php");

class Weixin extends Pc
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('weixin');
		return $this->ci->bll_weixin->$method($aParams[0]);
	}
}

# end of this file
