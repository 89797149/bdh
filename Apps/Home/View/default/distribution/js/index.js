$(document).ready(function () {
	console.log(window.location.href)
	// 获取带来的用户ID
	var href = window.location.href

	var url = href.split("?");
	var userId,url_1;
	if (url && url.length > 1) {
	  if(url[1]!==undefined){
	    url_1 = url[1].split("=");
	    userId=url_1[1].split("&date")[0];
	  }
		
	}else{
	    
	}


    $.ajax({
    url: "//"+window.location.host+'/v3/index/mallDetail',
    type: 'post',
    data: {},
    dataType: 'json',
    beforeSend: function () {},
    complete: function () {},
    success: function (res) {
    if(res.data.indexButtionIcon1.indexOf('qiniu://')>-1){
        $('.banner img').attr('src',res.data.qiniuDomain+res.data.indexButtionIcon1.split('qiniu://')[1]);    
    }else{
        $('.banner img').attr('src',"//"+window.location.host+"/"+res.data.indexButtionIcon1);  
    }
   
     

    },
    error: function (xhr, ajaxOptions, thrownError) {}
  });  
	$('.submit').click(function () {

		var phoneVal = $("[name='phone']").val();
		var regPhone =/^1\d{10}$/;
		var phoneTest = regPhone.test(phoneVal);

		var verifyVal = $('.verify').val();

		if (!phoneTest) {
			layer.open({
				content: '请输入手机号',
				skin: 'msg',
				time: 3
			});
			return false;
		}

		if (verifyVal.length == 0) {
			layer.open({
				content: '请输入验证码',
				skin: 'msg',
				time: 3
			});
			return false;
		}

		$.ajax({
            url:"//"+window.location.host+'/v3/index/distributionLog',
			type: 'post',
			data: {
				'userId': userId,
				'userPhone': phoneVal,
				'code': verifyVal
			},
			dataType: 'json',
			beforeSend: function () {},
			complete: function () {},
			success: function (res) {
				if (res.code == 0 || res.code == 1) {
					layer.open({
						content: res.msg + '三秒后跳转至下载页面',
						skin: 'msg',
						time: 3 //2秒后自动关闭
					});
				// 	setTimeout(() => {
				// 		window.location.href = res.data.downAppUrl;
				// 	}, 3000);
				window.location.href ="//"+window.location.host+'/Apps/Home/View/default/distribution/download.html';//测试需注释

				} else {
					layer.open({
						content: res.msg,
						skin: 'msg',
						time: 3 //2秒后自动关闭
					});
				}
			},
			error: function (xhr, ajaxOptions, thrownError) {}
		});
	});


	// 获取验证码
	$('.getVerificationCode').click(function () {

		var phoneVal = $("[name='phone']").val();
		var regPhone = /^1\d{10}$/;
		var phoneTest = regPhone.test(phoneVal);

		if (!phoneTest) {
			$('.phone').siblings('.errTip').show();
			return false;
		}
		

		$.ajax({
            url: "//"+window.location.host+'/v3/index/distributionPhoneVerify',
			type: 'post',
			data: {
				'userPhone': phoneVal
			},
			dataType: 'json',
			beforeSend: function () {},
			complete: function () {},
			success: function (res) {
                //alert(res.msg)
				layer.open({
					content: res.msg,
					skin: 'msg',
					time: 3 //2秒后自动关闭
				});
				if(res.code==0){
				  countDown()  
				}else{
				    
				}
			},
			error: function (xhr, ajaxOptions, thrownError) {}
		});
	})

function countDown() {
        clearInterval(window.timer)
        window.times = 60
        window.timer = setInterval(function() {
          window.times = window.times - 1
          if (window.times < 0 || window.times == 0) {
            //this.showSMSBtn = true
            clearInterval(window.timer)
            window.times = 60
            $('.getVerificationCode').html('重新获取') 
          }else{
              $('.getVerificationCode').html(window.times)
          }
          
          
        }, 1000)
      }


	$('input').focus(function () {
		$(this).siblings('.errTip').hide();
	});
	$('.codeImg').click(function () {
		var random = Math.floor((Math.random() * 10000));
		var url = 'http://baidu.com/?' + random;
		$(this).attr('src', url);
	});

});