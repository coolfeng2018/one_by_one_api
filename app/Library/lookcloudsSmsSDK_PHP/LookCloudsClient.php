<?php

/**
 * LookClouds 短信API Client
 *
 *
 * @version 1.0
 * @author lookclouds
 */
class LookCloudsClient
{
    protected $apiKey;
    public $decode; 

    function __construct($apikey='feb1721d7b4b439aa64d7d969ab7f241')
    {
        $this->apiKey=$apikey;
        $this->decode=true;
    }

    function http($url,$apikey,$param,$action = "POST"){
        $ch = curl_init();
        $config = array(CURLOPT_RETURNTRANSFER=>true);
        $headers = array(
         'apikey: '.$apikey,
        );
        switch($action)
        {
            case "POST":
                $config[CURLOPT_POST]=true;
                $config[CURLOPT_POSTFIELDS]=http_build_query($param);
                array_push($headers,'content-type: application/x-www-form-urlencoded; charset=UTF-8');
                break;
            case "GET":
                $url=$url.'?'.http_build_query($param);
                array_push($headers,"accept: application/json; charset=utf-8");
                break;

        }
        $config[CURLOPT_URL]=$url;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt_array($ch,$config);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result=curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 发送短信
     * @param mixed $mobiles 手机号，多手机号之间用英文逗号隔开
     * @param mixed $channel 短信通道号
     * @param mixed $content 短信内容，包括前面
     * @return mixed
     */
    function sendSms($mobiles,$channel,$content)
    {
        $result = $this->http("https://api.sms.lookclouds.com/send",$this->apiKey,array("mobile"=>$mobiles,"content"=>$content,"channel"=>$channel));
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    function sendSmsByTemplate($mobiles,$templateId,$data)
    {
       $result = $this->http("https://api.sms.lookclouds.com/sendByTemplate",$this->apiKey,array("mobile"=>$mobiles,"templateId"=>$templateId,"data"=>$data));
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    /**
     * 计算短信条数
     * @param string $content 短信内容，包括签名
     * @return {count:0,words:1}
     */
    function calcSms($content)
    {
        $result = $this->http("https://api.sms.lookclouds.com/count",$this->apiKey,array("content"=>$content),"GET");
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    /**
     * 检测手机号信息
     * @param mixed $phone 手机号码，如果多手机，请用phone=aaa&phone=bbb&phone=ccc格式
     * @return mixed
     */
    function checkMobileInfo($phone)
    {
        $result = $this->http("https://api.sms.lookclouds.com/check_mobile_info",$this->apiKey,array("phone"=>$phone),"GET");
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    /**
     * 检测短信内容中的敏感词
     * @param mixed $content 短信内容，包括签名
     * @param mixed $channel 短信通道号
     * @return mixed
     */
    function sensitiveWord($content,$channel)
    {
        $result = $this->http("https://api.sms.lookclouds.com/has_sensitives",$this->apiKey,array("channel"=>$channel,"content"=>$content));
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    /**
     * 获取有效的手机号
     * @param mixed $mobiles 手机号，多手机号之间用英文逗号隔开
     * @return mixed
     */
    function getValidMobiles($mobiles)
    {
        $result = $this->http("https://api.sms.lookclouds.com/validMobiles",$this->apiKey,array("mobiles"=>$mobiles));
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    /**
     * 获取匹配的短信模板
     * @param mixed $content 短信正文，包括签名
     * @return mixed
     */
    function getMatchTemplate($content)
    {
        $result = $this->http("https://api.sms.lookclouds.com/template",$this->apiKey,array("content"=>$content),"GET");
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    function getSentHistoryByMobile($mobile,$count)
    {
        $result = $this->http("https://api.sms.lookclouds.com/sent_history_mobile",$this->apiKey,array("mobile"=>$mobile,"count"=>$count),"GET");
        if($this->decode)
            return json_decode($result);
        return $result;
    }

    function getSentHistoryByBatchIds($batchIds)
    {
        $result = $this->http("https://api.sms.lookclouds.com/sent_history_batchIds",$this->apiKey,array("batchIds"=>$batchIds));
        if($this->decode)
            return json_decode($result);
        return $result;
    }
}
