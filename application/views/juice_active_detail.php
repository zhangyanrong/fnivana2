<!DOCTYPE html>
<html>
	<head>
		<title>门店果汁活动</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
		<style type="text/css">
		html, body, div, h1, h2, h3, h4, h5, h6, ul, ol, dl, li, dt, dd, p, blockquote, pre, form, fieldset, table, th, td ,img
		{ margin: 0; padding: 0; border:0;font-weight:normal;list-style:none;} 
		body{width:100%;}
		#peson{width:100%;  margin:0 auto;}
		#peson li{height:80px; line-height:50px; padding-left:22px; font-family:"微软雅黑"; font-size:22px; border-bottom:1px  solid #a1a1a1;}
		#peson .li_1{background:#ececec; border-bottom:1px solid #a1a1a1;}
		</style>
		<script src="http://cdn.fruitday.com/assets/js/jquery.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function(){
				$(".li_1").click(function(){
					var active_type = $(this).attr('active_type');
				    var uid = "<?=$uid?>";
				    var erp_tag = "<?=$erp_tag?>";
				    var iNumber = Number(prompt("请输入1或者2，1表示果汁，2表示果杯", ""));
				    if(iNumber==1 || iNumber==2){
				    	$.post("/appMarketing/accept_juice_active",{active_type:active_type,uid:uid,erp_tag:erp_tag,product_type:iNumber},function(data){
					 		var result = eval('('+data+')');
					 		alert(result['msg']);
					 		window.location.reload();
					    });
				    }else{
				    	alert("输入错误，只能输入1或者2");
				    }
					
					
					
				});
			});
		</script>
	</head>
	<body id="activity-detail">
		<?if($msg!=''):?>
		<h1><?=$msg?></h1>
		<?endif;?>
		
				<ul id="peson">
					<li class="li_1" active_type="day"><span>每日果汁活动</span></li>
					<li class="li_1" active_type="week"><span>每周果汁活动(免费)</span></li>
				</ul>
			
	</body>
</html>
