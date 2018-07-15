<?php
namespace bll\wap;
include_once("wap.php");

class Exporter extends wap
{
	public function __call($method, $aParams)
	{
        $this->ci = & get_instance();
		$this->ci->load->bll('exporter');
		return $this->ci->bll_exporter->$method($aParams[0]);
	}
}

# end of this file
