<?php

/**
 * 短信发送的例子
 *
 * apikey请在用户平台中的“服务密钥”中获得
 *
 * @version 1.0
 * @author lookclouds
 */
include("LookCloudsClient.php"); 

$obj = new LookCloudsClient("请输入您的apikey");
$obj->decode=false;//默认为true，如果是true，则返回的结果会序列化为对象

//$sendSmsResult = $obj->sendSms("15986600622","1001001","test【看云科技】");
//echo '短信发送结果:<br/>';
//echo $sendSmsResult;
//echo '<hr/>';

//$sendSmsResult = $obj->sendSmsByTemplate("15986600622","1","123456");
//echo '短信发送结果:<br/>';
//echo $sendSmsResult;
//echo '<hr/>';

$matchTemplateResult = $obj->getMatchTemplate("test【看云科技】");
echo '匹配短信模板结果：<br/>';
echo $matchTemplateResult;
echo '<hr/>';

$calcSmsResult = $obj->calcSms("这是一条短信正文【看云科技】");
echo '计算短信条数和字数：<br/>';
echo $calcSmsResult;
echo '<hr/>';

$validMobilesResult=$obj->getValidMobiles("15987799877,123,1000,12378888999,10000000000");
echo '过滤并返回有效的手机号：<br/>';
echo $validMobilesResult;
echo '<hr/>';

$sensitiveWordsResult=$obj->sensitiveWord("法轮功是世界上最棒的功夫【看云科技】","2001001");
echo '检测敏感词结果：';
echo $sensitiveWordsResult;
echo '<hr/>';

$mobileInfo=$obj->checkMobileInfo("15986600622");
echo '检测手机号信息：';
echo $mobileInfo;
echo '<hr/>';

$sentHistoryByMobileResult=$obj->getSentHistoryByMobile("15986600622",4);
echo '检测手机号发送历史纪录：';
echo $sentHistoryByMobileResult;
echo '<hr/>';

$sentHistoryByBatchIdResult=$obj->getSentHistoryByBatchIds("201612285e3e644b18d048a3b4fd1a2fb636cd8d,2016122182d0e9c0843f4d49af795aac9d0dc11e");
echo '检测批次号发送历史纪录：';
echo $sentHistoryByBatchIdResult;
echo '<hr/>';

?>
<html>
<head>
    <meta charset="utf-8" />
</head>
<body>
    
</body>
</html>