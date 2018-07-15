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
		.peson{width:100%;  margin:0 auto;}
		.peson li{height:48; line-height:50px; padding-left:22px; font-family:"微软雅黑"; font-size:22px; border-bottom:1px  solid #a1a1a1;}
		.peson .li2{height:auto; line-height:50px; padding-left:22px; font-family:"微软雅黑"; font-size:22px; border-bottom:1px  solid #a1a1a1;}
		</style>
		<script src="/assets/jquery-1.6.2.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function(){
				$("#check_send").click(function(){
					var active_id = "<?=$active_id?>";
				    var uid = "<?=$uid?>";
				    var order_name = "<?=$order_info['order_name']?>";
					if(confirm('是否确认发货')){	
						$.post("/appMarketing/accept_offline_active",{id:active_id,uid:uid,order_name:order_name},function(data){
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
		<h1 style="color:red;"><?=$msg?></h1>
		<?else:?>
				<div id="check_send" style="width:100%;height:60px;background-color:#669933;"><span style="line-height:50px;font-size:30px;color:white;padding:10px 100px;">确认发货</span></div>
		<?endif;?>
		<?if(!empty($order_info)):?>
				<ul class="peson">
					<li><span>订单号：<?=$order_info['order_name']?></span></li>
					<li><span>下单时间：<?=$order_info['time']?></span></li>
					<li><span>支付方式：<?=$order_info['pay_name']?></span></li>
					<li><span>订单金额：<?=$order_info['money']?></span></li>
					<li><span>支付状态：<?=$pay_config[$order_info['pay_status']]?></span></li>
					<li><span>订单状态：<?=$operation_config[$order_info['operation_id']]?></span></li>
					<li class="li2">
						<span>订单商品：</br>
							<?foreach($order_info['order_product'] as $k=>$v):?>
								<?=$v['product_name']?><?=$v['gg_name']?></br>
								数量：<?=$v['qty']?></br>
								价格：<?=$v['price']?></br>
								小计：<?=$v['price']*$v['qty']?></br>
							<?endforeach;?>
						</span>
					</li>
				</ul>
		<?endif;?>
	</body>
</html>
