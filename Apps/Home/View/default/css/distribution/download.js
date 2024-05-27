$(document).ready(function () {
  $.ajax({
    url: 'https://www.qinghucun.net/Weimendian/Index/mallDetail',
    type: 'post',
    data: {},
    dataType: 'json',
    beforeSend: function () {},
    complete: function () {},
    success: function (res) {


      var android_qrcode = new QRCode(document.getElementById("android_qrcode"), {
        width: 130, //设置宽高
        height: 130
      });

      var ios_qrcode = new QRCode(document.getElementById("IOS_qrcode"), {
        width: 130, //设置宽高
        height: 130
      });
      //安卓二维码
      android_qrcode.makeCode(res.apiData.appDownAndroid);
      //苹果二维码
      ios_qrcode.makeCode(res.apiData.appDownIos);


    },
    error: function (xhr, ajaxOptions, thrownError) {}
  });
})