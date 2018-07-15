<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Bake extends Pc{

	function __construct(){
		$this->ci = &get_instance();
	}

}