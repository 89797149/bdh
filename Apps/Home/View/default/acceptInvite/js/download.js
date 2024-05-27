$(document).ready(function () {
  $.ajax({
    url: "//"+window.location.host+'/v3/index/mallDetail',
    type: 'post',
    data: {},
    dataType: 'json',
    beforeSend: function () {},
    complete: function () {},
    success: function (res) {
        
        console.log('download 打印')
        console.log(res)
      var android_qrcode = new QRCode(document.getElementById("android_qrcode"), {
        width: 130, //设置宽高
        height: 130
      });

      var ios_qrcode = new QRCode(document.getElementById("IOS_qrcode"), {
        width: 130, //设置宽高
        height: 130
      });
      //安卓二维码
      android_qrcode.makeCode(res.data.appDownAndroid);
      //苹果二维码
      ios_qrcode.makeCode(res.data.appDownIos);
    if(res.data.wxSmallImgSrc.indexOf('qiniu://')>-1){
        $('.miniprogram_qrcode').attr('src',res.data.qiniuDomain+res.data.wxSmallImgSrc.split('qiniu://')[1]);    
        
        
    }else{
    
        $('.miniprogram_qrcode').attr('src',"//"+window.location.host+"/"+res.data.wxSmallImgSrc)
    }


    },
    error: function (xhr, ajaxOptions, thrownError) {}
  });
  
 $('.androidBtn').click(function () { 
     
    window.location.href ='http://app.appurl.me/79815846296';
 })
 
  $('.iosBtn').click(function () { 
    window.location.href ='http://app.appurl.me/79815846296';
 }) 
 
})