<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Group extends pc{

	function __construct($params=array()){
		$this->ci = &get_instance();
	}

}