$(function(){
	var title = $("title").text();
	$(".wechat-custom-header .custom-title").text(title);
	var getUrl = _GET().platform;
	if(getUrl && getUrl === "wechatapp"){
		// 存储paltform参数
		Storage.set('platform', getUrl, true);
		// 识别iPoneX
		if(_GET().systemModel === "iPhoneX"){
			$("body").addClass("iPhoneX");
			Storage.set('systemModel', 'iPhoneX', true);
		}
		// 显示
		$("body").addClass("has-wechat-header");
		$(".wechat-header-wrapper").css({'display': 'block'});
		// 显示首页icon
		if(_GET().linksource != 1 && _GET().goBack == "index") {
			$(".wechat-custom-header .custom-back-btn").css({
				'backgroundImage': 'url(./images/detailHome.png)',
				'webkitTransform': 'none'
			})
		}
	}else{
		// 获取paltform,systemModel参数
		var storageUrl = Storage.get('platform', true),
			storageSys = Storage.get('systemModel', true);
		if(storageUrl){
			if(storageSys){
				$("body").addClass("iPhoneX");
			}
			$("body").addClass("has-wechat-header");
			$(".wechat-header-wrapper").css({'display': 'block'});
		}
	}
	$(".custom-back-btn").on("click", function(){
		if(getUrl === "wechatapp" && _GET().linksource != 1){
			// 返回小程序首页
			if(_GET().goBack == "index"){
				wx.miniProgram.switchTab({
					url: '/pages/index/index'
				})
				return
			}
			wx.miniProgram.navigateBack();
		}else{
			window.history.go(-1);
		}
	});
});
// 获取url参数
function _GET() {
    var e = location.search,
        o = {};
    if ("" === e || void 0 === e) return o;
    e = e.substr(1).split("&");
    for (var n in e) {
        var t = e[n].split("=");
        o[t[0]] = t[1]
    }
    return o.from && delete o.code, o //o.from得到的是什么值(类型)
}
