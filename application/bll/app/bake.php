<?php
namespace bll\app;
include_once("app.php");
/**
* 商品相关接口
*/
class Bake extends app{

	function __construct(){
		$this->ci = &get_instance();
	}
	
}