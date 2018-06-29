$(function(){
    var browser = {
        versions: function() {
            var u = navigator.userAgent, app = navigator.appVersion;
            return {//移动终端浏览器版本信息
                trident: u.indexOf('Trident') > -1, //IE内核
                presto: u.indexOf('Presto') > -1, //opera内核
                webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
                gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
                mobile: !!u.match(/AppleWebKit.*Mobile.*/) || !!u.match(/AppleWebKit/), //是否为移动终端
                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
                iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
                iPad: u.indexOf('iPad') > -1, //是否iPad
                webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
            };
        }(),
        language: (navigator.browserLanguage || navigator.language).toLowerCase()
    }

    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    }

    function testAnim(x) {
        $('#but').removeClass().addClass(x + ' animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
            $(this).removeClass();
        });
    };
    testAnim('bounceInRight');
    var tip = document.getElementById('weixin-tip');
    var close = document.getElementById('close');
    
    //广告
    var tid = getQueryString('clickid');
    if (browser.versions.ios || browser.versions.iPhone || browser.versions.iPad) {
        var loadDateTime = new Date();
        window.setTimeout(function() {
            var timeOutDateTime = new Date();
            if (timeOutDateTime - loadDateTime < 5000) {
                var ua = window.navigator.userAgent.toLowerCase();
                if(ua.match(/MicroMessenger/i) == 'micromessenger' || (ua.match(/uiwebview/i)=="uiwebview"&&ua.match(/qq/i)=="qq")){
//                            $("#but").click(function(){
                    tip.style.display = 'block';
                    return false;
//                            });
                }else{
                	$.get("http://tracking.oway.mobi/pb?tid="+tid);
                	alert("http://tracking.oway.mobi/pb?tid="+tid);
                	$("#but").attr("onclick","_czc.push(['_trackEvent', 'installios', 'click', 'psz','','but']);");
                	$("#but").attr("href","https://dlapp.lixuanjie.com");
                }
            } else {
                window.close();
            }
        }, 1000);
        var room=getQueryString('room');
        var gameId=getQueryString('gameId');
        if(room&&gameId){
        }else{
        }
    }else if (browser.versions.android) {
        var loadDateTime = new Date();
        window.setTimeout(function() {
            var timeOutDateTime = new Date();
            if (timeOutDateTime - loadDateTime < 5000) {
                var ua = window.navigator.userAgent.toLowerCase();
                if(ua.match(/MicroMessenger/i) == 'micromessenger'||(ua.match(/webp/i)=="webp"&&ua.match(/qq/i)=="qq")){
//                        $("#but").click(function(){
                    tip.style.display = 'block';
                    return false;
//                        });
                }else{
                	$.get("http://tracking.oway.mobi/pb?tid="+tid);
                	$("#but").attr("onclick","_czc.push(['_trackEvent', 'installandroid', 'click', 'psz','','but']);");
                    $("#but").attr("href","http://res.lixuanjie.com/musky/apks/oczjh0508-guanwang.apk");                	
                }
            } else {
                window.close();
            }
        }, 1000);
        var room=getQueryString('room');
        var gameId=getQueryString('gameId');
        if(room&&gameId){
        }else{
        }
    }

    $("#weixin-tip").click(function(){
        tip.style.display = 'none';
    });

});
