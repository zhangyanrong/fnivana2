<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Fruit extends wap{

	function __construct(){
		$this->ci = &get_instance();
	}
	
}