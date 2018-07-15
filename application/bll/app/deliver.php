<?php
namespace bll\app;
include_once("app.php");

class Deliver extends App
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('deliver');
		return $this->ci->bll_deliver->$method($aParams[0]);
	}
}

# end of this file
