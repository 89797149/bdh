$(document).ready(function () {
	console.log(window.location.href)
	// 获取带来的用户ID
	var href = window.location.href

	var url = href.split("?");
	var userId;
	if (url && url.length > 1) {
		var url_1 = url[1].split("=");
	}
	console.log(url_1);
	if (url_1 && url_1.length > 1) {
		userId = url_1[4];
	}


	$('.submit').click(function () {
		console.log(userId)
		var phoneVal = $("[name='phone']").val();
		var regPhone = /^[0]?(13[0-9]{1}|14[57]{1}|15[012356789]{1}|17[0678]{1}|18[0124356789]{1})(\d{8})$/;
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

		// var href = window.location.href;

		$.ajax({
            url: window.location.protocol+"//"+window.location.host+'/Weimendian/Index/distributionLog',
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
				if (res.apiCode == 1) {
					layer.open({
						content: res.apiInfo + '三秒后跳转至下载页面',
						skin: 'msg',
						time: 3 //2秒后自动关闭
					});
					setTimeout(() => {
						window.location.href = res.apiData.downAppUrl;
					}, 3000);

				} else {
					layer.open({
						content: res.apiInfo,
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
		var regPhone = /^[0]?(13[0-9]{1}|14[57]{1}|15[012356789]{1}|17[0678]{1}|18[0124356789]{1})(\d{8})$/;
		var phoneTest = regPhone.test(phoneVal);

		if (!phoneTest) {
			$('.phone').siblings('.errTip').show();
			return false;
		}

		$.ajax({
            url: window.location.protocol+"//"+window.location.host+'/Weimendian/Index/distributionPhoneVerify',
			type: 'post',
			data: {
				'userPhone': phoneVal
			},
			dataType: 'json',
			beforeSend: function () {},
			complete: function () {},
			success: function (res) {

				layer.open({
					content: res.msg,
					skin: 'msg',
					time: 3 //2秒后自动关闭
				});
			},
			error: function (xhr, ajaxOptions, thrownError) {}
		});
	})




	$('input').focus(function () {
		$(this).siblings('.errTip').hide();
	});
	$('.codeImg').click(function () {
		var random = Math.floor((Math.random() * 10000));
		var url = 'http://baidu.com/?' + random;
		$(this).attr('src', url);
	});

});