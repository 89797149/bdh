/**
 * CFPrint打印辅助类
 * ver 1.3.8
 * 康虎软件工作室
 * Email: wdmsyf@sina.com
 * QQ: 360026606
 * 微信: 360026606
 * 官网：www.cfsoft.cf
 *
 * 一、用法：
 * 启动康虎云打印服务器后，在应用系统中，通过服务端代码，生成打印所需要的报表数据Json字符，并命名为_reportData，即可自动打印。
 * 通过重设 _delay_send 和 _delay_close 两个参数，可以调整发送打印以及打印完毕后关闭报表页面的延时时长。
 *
 *  其中：_reportData 可以是json对象，也可以是json字符串
 *
 * 二、连接状态
 * cfprint.CONNECTING: 正在连接
 * cfprint.OPEN: 已连接
 * cfprint.CLOSING: 连接正在关闭
 * cfprint.CLOSED: 连接已关闭
 *
 * 检测当前连接状态：
 * if ( cfprint.state() === cfprint.OPEN)  {...}
 */

/*
//示例数据：
var _reportData = '{"template":"waybill_huaxia3.fr3","Cols":[{"type":"str","size":255,"name":"HAWB#","required":false},{"type":"int","size":0,"name":"NO","required":false},{"type":"float","size":0,"name":"报关公司面单号","required":false},{"type":"integer","size":0,"name":"公司内部单号","required":false},{"type":"str","size":255,"name":"发货国家","required":false},{"type":"float","size":0,"name":"单价1（JPY）","required":false},{"type":"float","size":0,"name":"申报总价2（CNY）","required":false},{"type":"float","size":0,"name":"单价4（JPY）","required":false},{"type":"AutoInc","size":0,"name":"ID","required":false}],"Data":[{"公司内部单号":730293,"发货国家":"日本","单价1（JPY）":null,"申报总价2（JPY）":null,"单价4（JPY）":null}]}';
*/
var _reportData = _reportData || '';
var _delay_send = 1000;            //发送打印服务器前延时时长,-1表示不自动发送
var _delay_close = 1000;           //打印完成后关闭窗口的延时时长, -1则表示不关闭
var cfprint_addr = "127.0.0.1";    //打印服务器的地址
var cfprint_port = 54321;          //打印服务器监听端口

//设置对象并连接打印服务器
//如果这个方法不能满足需要，您可以参考该方法自定义
function setup(_host, _port, _protocol){
  var _cfprint = new ws(_host, _port, {
    automaticOpen: false,    //是否自动连接标志(true|false)，默认为true
    reconnectDecay: 1.5,    //自动重连延迟重连速度，数字，默认为1.5
    output:"output",        //指定调试输出div
    protocol: _protocol || "ws"          //指定通讯协议(ws|wss)，默认为"ws"
  });

  _cfprint.onconnecting = function(evn){
    _cfprint.log('正与服务器建立连接...', evn);
  }
  _cfprint.onopen = function(evn){
    _cfprint.log('与服务器连接成功。', evn);
  }
  _cfprint.onclose = function(evn){
    _cfprint.log('与打印服务器的连接已关闭', evn);
  }

  /**
  * 接收到打印服务器消息
  * 通过该事件，可以获取到打印是否成功
  * 参数：
  * evn: 包含服务器返回信息的事件对象
  *      evn.data:  服务器返回的信息，是一个json字符串，其中：
  */
  _cfprint.onmessage = function(evn){
    _cfprint.log('收到消息！"'+evn.data+'"', evn);
    var resp = JSON && JSON.parse(evn.data) || $.parseJSON(evn.data);   //解析服务器返回数据
    if(resp.result == 1){
      //if(_delay_close>0)

				CfprintCutting(function(e){
				
				console.log(e)

				})
				CfprintCutting(function(e){
				
				console.log(e)

				})
        //setTimeout(function(){open(location, '_self').close();}, _delay_close); //延时后关闭报表窗口
		
		//进行受理
		
		//$.post(Think.U('Home/Orders/shopOrderAccept'),{orderId:id},function(data){
			//alert("hhhhhhhhhh")
		//	layer.close(ll);
		//	layer.close(tips);
		//	var json = WST.toJson(data);
		//	console.log("打印受理")
		//	console.log(json)
		//	if(json.status>0){
		//		$(".wst-tab-nav").find("li").eq(statusMark).click();
		//	}else if(json.status==-1){
		//			WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5}); 
		//	}else{
		//		WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
		//	}
		//});
		
		
    }else{
      alert("Print failed: "+resp.message);
    }
  }

  /**
   * 捕获到错误事件
   * 通过该事件在出错时可以判断出错原因
   * 参数：
   * evn: 包含错误信息的事件对象
   *      evn.detail: 是包含错误明细的对象
   *      evn.detail.message：是错误信息
   */
  _cfprint.onerror = function(evn){
    if(typeof(evn.detail)==="object"){
      if(typeof(evn.detail.message)==="string")
        _cfprint.log('遇到一个错误: '+evn.detail.message, evn);
      if(typeof(evn.detail.data)==="string")
        _cfprint.log('遇到一个错误: '+evn.detail.message, evn);
    }
    else if(typeof(evn.data)==="string")
      _cfprint.log('遇到一个错误: '+evn.data, evn);
    else
      _cfprint.log('遇到一个错误', evn);
  }

  //cfprint.open();  //连接到打印服务器，automaticOpen=false时需要这一行
  return _cfprint;
}

//初始化cfprint对象
var cfprint = setup(cfprint_addr, cfprint_port);

/**
 * 发送打印数据到打印服务器
 * 参数：
 *   str: 发送给打印服务器的数据，支持json字符串和json对象
 */
function sendMsg(json, _cfprint) {
	var _connect = function(){
	  //重设监听地址和端口
	  if(_cfprint.protocol=="ws"){
	  	if(cfprint_addr) _cfprint.setHost(cfprint_addr);
	  	if(cfprint_port) _cfprint.setPort(cfprint_port);

	  }	
	  if(_cfprint.protocol=="wss"){
	  	if(cfprint_addr_ssl) _cfprint.setHost(cfprint_addr_ssl);
	  	if(cfprint_port_ssl) _cfprint.setPort(cfprint_port_ssl);	
	  }	
		
		//打开连接
  	_cfprint.open();  //连接到打印服务器，automaticOpen=false时需要这一行
	};

  var _send = function(_msg){
    _cfprint.log("SENT: <br/>" +  _msg);
    _cfprint.log('正在发送消息...');
    _cfprint.send(_msg);
  };

	_cfprint = _cfprint || cfprint;

  if(typeof(_cfprint) === undefined) {
  	setup();
  }
  	
  if(_cfprint.state()!==_cfprint.OPEN) {
    _cfprint.log("连接已断开，正在重新连接。");
    _cfprint.onopen = function(evn){
      _cfprint.log('与服务器连接成功。', evn);
      _send(json);
    }
    _connect();
  }else{
    _send(json);
  }
}

/*******************************/
//以下是自动打印的代码，如果不是自动打印则不需要

/*无JS框架调用示例*/
var __doPrint = function(){
  if(typeof(_reportData) != "undefined" && _reportData != ""){
    if(_delay_send>0){
      setTimeout(function () {
          sendMsg(_reportData);
		  
      }, _delay_send);
    }
  }else {
    cfprint.log("要打印的报表内容为空，取消打印。");
  }
}

if (window.addEventListener)
  window.addEventListener("load", __doPrint, false);
else if (window.attachEvent)
  window.attachEvent("onload", __doPrint);
else window.onload = __doPrint;


/*******无JS框架调用示例结束**********/
/** JQuery 调用示例
$(document).ready(function(){

  //初始化cfprint对象
  setup();

  //打开连接
  connect();
  //打开连接      //连接到打印服务器，automaticOpen=false时需要这一行

  if(typeof(_reportData) != "undefined" && _reportData != ""){
    setTimeout(function () {
      sendMsg(_reportData);
    }, _delay_send);
  }else {
    cfprint.log("要打印的报表内容为空，取消打印。");
  }
});

 **/


