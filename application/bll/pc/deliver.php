<?php
namespace bll\pc;
include_once("pc.php");

class Deliver extends Pc
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('deliver');
		return $this->ci->bll_deliver->$method($aParams[0]);
	}
}

# end of this file
