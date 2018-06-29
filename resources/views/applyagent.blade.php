<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>花色代理申请</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="wap-font-scale" content="no">
    <meta name="renderer" content="webkit">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="screen-orientation" content="portrait">    <!--uc强制竖屏-->
    <meta name="x5-orientation" content="portrait">   <!--uc强制竖屏-->
    <link rel="stylesheet" href="/css/reset.css">
    <link rel="stylesheet" href="/css/extension.css">
</head>
<body>
<h3 class="ex_title">推广代理申请</h3>
<div class="main">
    <div class="ex_word">
        <b>当前满足以下条件后，可以填写以下信息或者联系我们的客服，来咨询相关事宜。</b>
        <p>1、拥有固定的牛牛圈子或者微信，QQ群。</p>
        <p>2、在担任推广员期间，不进行任何违法行为。</p>
    </div>
    <div class="ex_word"><b>客服微信：sdfsdif</b></div>
    <form name="" class="form" action="" method="">
        {{csrf_field()}}
        <div class="form-group">
            <p class="icon-name">手机号码：</p>
            <input type="text" placeholder="请输入手机号码" class="Telephone" name="telephone" maxlength="11">
        </div>
        <div class="form-group">
            <p class="icon-name">游戏ID：</p>
            <input type="text" placeholder="请输入游戏ID" class="UserId" name="gameId" maxlength="11">
        </div>
        {{--<div class="form-group">--}}
            {{--<p class="icon-name">微信群截图：</p>--}}
            {{--<a href="javascript:;" class="upload_btn"> <input type="file" class="uploadimg" name="uploadimg"><span>选择图片</span></a>--}}
            {{--<span class="red">*图片大小不能超过2M</span>--}}
        {{--</div>--}}
        {{--<div class="form-group">--}}
            {{--<p class="icon-name">20名群成员游戏ID：</p>--}}
            {{--<textarea name="members" rows="3" class="members" cols="60" id="textarea1" maxlength="300"--}}
                      {{--placeholder="要求：未绑定代理的玩家编号（格式：逗号断开）"></textarea>--}}
        {{--</div>--}}
        <div class="form-group">
            <p class="icon-name">您有什么资源&优势：</p>
            <textarea name="description" rows="3" cols="60" id="textarea2" maxlength="300" placeholder="选填"></textarea>
        </div>
        <div class="form-group">
            <a href="javascript:;" class="submit_btn">提交申请</a>
        </div>
    </form>
</div>
</body>
<script type="text/javascript" src="/js/jquery-3.2.1.min.js"></script>
<script type="text/javascript">

    if (/Android [4-6]/.test(navigator.appVersion)) {
        window.addEventListener("resize", function () {
            if (document.activeElement.tagName == "INPUT" || document.activeElement.tagName == "TEXTAREA") {
                window.setTimeout(function () {
                    document.activeElement.scrollIntoViewIfNeeded();
                }, 0);
            }
        })
    }

    $('.uploadimg').on('change',function(){
        if($(this).val()){
            $(this).parent().find('span').text('已选择');
        }
    });

    $('.submit_btn').click(function(){
        $('.form').submit()
    });
    $('.form').submit(function(){
        var formData = new FormData(this);
        if($('.Telephone').val()==''){
            alert('请填写手机号');
            return false;
        }
        if($('.UserId').val()==''){
            alert('请填写游戏ID');
            return false;
        }
//        if($('.members').val()==''){
//            alert('请输入成员游戏ID');
//            return false;
//        }
        $.ajax({
            url: '/applyagent/submit',
            type: 'POST',
            data: formData,
            async: false,
            success: function (data) {
                if(data.status==200){
                    alert('提交申请成功，请等候回复');
                }else if(data.status==500){
                    alert('请选择图片');
                }else if(data.status==501){
                    alert('填写的群成员游戏编号格式错误，请使用逗号分隔');
                }else if(data.status==502){
                    alert('请填写正确的游戏ID格式');
                }
            },
            error:function(response,textStatus){
                if (response.status==413){
                    alert('图片体积太大');
                }else{
                    alert('请求失败，请填写正确。');
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });

        return false;
    });
</script>
</html>
