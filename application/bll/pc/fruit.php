<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Fruit extends pc{

	function __construct(){
		$this->ci = &get_instance();
	}
	
}