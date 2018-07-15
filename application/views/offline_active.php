<!DOCTYPE html>
<html>
	<head>
		<title>天天果园线下活动</title>
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
					var active_id = $(this).attr('active_id');
				    var uid = "<?=$uid?>";
					if(active_type=='2'){
						window.location.href="/appMarketing/offline_active_detail/"+uid+"/"+"<?=$sign?>"+"/"+active_id;
						return false;
					}
					if(confirm('是否确认参与活动')){	
						$.post("/appMarketing/accept_offline_active",{id:active_id,uid:uid},function(data){
					 		var result = eval('('+data+')');
					 		alert(result['msg']);
					 		window.location.reload();
					    });
					}
				});
			});
		</script>
	</head>
	<body id="activity-detail">
		<?if($msg!=''):?>
		<h1><?=$msg?></h1>
		<?endif;?>
		<?if(!empty($active_list)):?>
			<?foreach($active_list as $key=>$value):?>
				<ul id="peson">
					<li class="li_1" active_id="<?=$value['id']?>" active_type="<?=$value['active_type']?>"><span><?=$value['title']?></span></li>
				</ul>
			<?endforeach;?>
		<?endif;?>
	</body>
</html>
