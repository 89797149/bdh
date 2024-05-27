$(function(){
	getVerify();
	$('#chkRememberMe').change(function(){
		if($('#chkRememberMe:checked').val()){
			$("#rememberPwd").val(1);
		}else{
			$("#rememberPwd").val(0);
		}
	});
	$(document).keypress(function(e) { 
		   if(e.which == 13) {  
			   login();  
		   } 
	});
})
function login(){

		if(typeof(isClient)!=='undefined'){
			M('shopLogin').insert({'passWord':$.trim($('#loginPwd').val()),'loginName':"$.trim($('#loginName').val())"},t=>{
				if(t){
					console.log('数据添加成功'+t)
				}else{
					console.log('抽象层数据添加失败'+t)
				}
			})
			
		}else{
			
			
		}
	   var params = {};
	   params.loginName = $.trim($('#loginName').val());
	   params.loginPwd = $.trim($('#loginPwd').val());
	   params.verify = $.trim($('#verify').val());
	   var rememberPwd = $("#rememberPwd").val();
	   if(params.loginName==''){
		   $("#errmsg").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;请输入用户名!");
		   $('#loginName').focus();
		   return;
	   }
	   if(params.loginPwd==''){
		   $("#errmsg").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;请输入密码!");
		   $('#loginPwd').focus();
		   return;
	   }
	   if(params.verify==""){
			$("#errmsg").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;验证码不能为空");
			return false;
	   }
	   $.post(Think.U('Home/Shops/checkLogin'),params,function(data,textStatus){
			var json = WST.toJson(data);
			
			console.log(json)
			if(json.status=='1'){
				//location.href= Think.U('Home/Shops/index');
			}else if(json.status==-2){
				$("#errmsg").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;验证码错误");
				getVerify();
			}else{
				$("#errmsg").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;账号或密码错误!");
				getVerify();
			}
	   });
}