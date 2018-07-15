<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>会员信息</title>
<style type="text/css">
html, body, div, h1, h2, h3, h4, h5, h6, ul, ol, dl, li, dt, dd, p, blockquote, pre, form, fieldset, table, th, td ,img
{ margin: 0; padding: 0; border:0;font-weight:normal;list-style:none;} 
body{width:100%;}
#peson{width:100%;  margin:0 auto;}
#peson li{height:120px; line-height:120px; padding-left:64px; font-family:"微软雅黑"; font-size:42px; border-bottom:1px  solid #a1a1a1;}
#peson .li_1{background:#ececec; border-bottom:1px solid #a1a1a1;}
</style>
</head>
<body>
<ul id="peson">
	<li class="li_1">用户名：<span><?=$user_info['username']?></span></li>
	<li>等级：<span><?=$user_info['user_rank']?></span></li>
	<li class="li_1">手机：<span><?=$user_info['mobile']?></span></li>
	<li>注册时间：<span><?=$user_info['reg_time']?></span></li>
</ul>
</body>
</html>
