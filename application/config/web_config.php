<?php
    $web_config =  array(
        "user_score" => array(
            "register" => array(
                "jf" => 500,
                "reason" => "绑定手机获得积分", 
            ),
            "put_birthday"=>array(
                "jf" => 500,
                "reason" => "完善生日信息获得积分",
                )
        ),
        "user_sms" => array(
            "register" => array(
                "message" => "感谢您注册天天果园会员，赠送您500积分，下单即可使用。", 
            ),
            "changepwd" => array(
                "message" => "用户您好，您已经成功重置，新的密码为{changepwd}。", 
            ),
        ),
        "web_var" => array(
            "{username}" => "user.username",
            "{mobile}" => "user.mobile",
            "{register_verification_code}" => "session.register_verification_code",
            "{order_num}" => "order.order_num",
            "{changepwd}" => "changepwd.password",
        ),
    );
?>
