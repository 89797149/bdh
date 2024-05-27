function getCommunitys(obj){
	var vid = $(obj).attr("id");
	
	$("#scommunitys").find("li").removeClass("searched");
	$(obj).addClass("searched");
	var params = {};
	params.areaId = vid;
	var html = [];
	$.post(Think.U('Home/Communitys/queryByList'),params,function(data,textStatus){
	    html.push('<li class="searched">全部</li>');
		var json = WST.toJson(data);
		console.log("全部");
		console.log(json)
		if(json.status=='1' && json.list.length>0){
			var opts = null;
			for(var i=0;i<json.list.length;i++){
				opts = json.list[i];
				html.push("<li id="+opts.communityId+">"+opts.communityName+"</li>")
				
			}
		}
		$('#psareas').html(html.join(''));
   });
	
}

function tohide(obj,id){
		
	if($("#"+id).height()<=28){
		$("#"+id).height('auto');
		$("#"+id).css("overflow","");
		$("#bs").val(1)
		$("#"+id+"-tp").html("&nbsp;隐藏&nbsp;");
	}else{
		$("#"+id).height(28);
		$("#"+id).css("overflow","hidden");
		$("#bs").val(0)
		$("#"+id+"-tp").html("&nbsp;显示&nbsp;");
	}
}

function queryGoods(obj,mark){
	var params = [];
	var communityId,brandId,prices,areaId3,c1Id,c2Id,c3Id,msort;
	keyWords = $.trim($("#keyword").val());
	c1Id = $("#c1Id").val();
	c2Id = $("#c2Id").val();
	c3Id = $("#c3Id").val();
	msort = 1;
	if(mark==1){
		areaId3 = $(obj).attr("data")?$(obj).attr("data"):'';
		communityId = $("#wst-communitys").find(".searched").attr("data");
		brandId = $("#wst-brand").find(".searched").attr("data");
		prices = $("#wst-price").find(".searched").attr("data");
	}else if(mark==2){
		areaId3 = $("#wst-areas").find(".searched").attr("data");
		brandId = $("#wst-brand").find(".searched").attr("data");
		prices = $("#wst-price").find(".searched").attr("data");
		communityId = $(obj).attr("data");
	}else if(mark==3){
		areaId3 = $("#wst-areas").find(".searched").attr("data");
		communityId = $("#wst-communitys").find(".searched").attr("data");
		brandId = $(obj).attr("data");
		prices = $("#wst-price").find(".searched").attr("data");
	}else if(mark==4){
		areaId3 = $("#wst-areas").find(".searched").attr("data");
		communityId = $("#wst-communitys").find(".searched").attr("data");
		brandId = $("#wst-brand").find(".searched").attr("data");
		prices = $(obj).attr("data");	
	}else{
		areaId3 = $("#wst-areas").find(".searched").attr("data");
		communityId = $("#wst-communitys").find(".searched").attr("data");
		brandId = $("#wst-brand").find(".searched").attr("data");
		if(mark==12){
			prices = $("#sprice").val()+"_"+$("#eprice").val();
		}else{
			prices = $("#wst-price").find(".searched").attr("data");
		}
		msort = $('#msort').val();
		params.push("msort="+((msort=='0')?1:0));
		params.push("mark="+mark);
	}
	if(keyWords!="")params.push("keyWords="+keyWords);
	if(c1Id && c1Id!='0')params.push("c1Id="+c1Id);
	if(c2Id && c2Id!='0')params.push("c2Id="+$("#c2Id").val());
	if(c3Id && c3Id!='0')params.push("c3Id="+$("#c3Id").val());
	if(areaId3 && areaId3!='0')params.push("areaId3="+areaId3);
	if(communityId && communityId!='0')params.push("communityId="+communityId);
	if(brandId && brandId!='0')params.push("brandId="+brandId);
	if(prices)params.push("prices="+prices);
	window.location = Think.U('Home/Goods/getGoodsList',params.join('&'));
}

/**
 * 加入购物车
 */
function addCart(goodsId,type,goodsThums){
	if(WST.IS_LOGIN==0){
		loginWin();
		return;
	}
	var params = {};
	params.goodsId = goodsId;
	params.gcount = parseInt($("#buy-num").val(),10);
	params.rnd = Math.random();
	params.goodsAttrId = $('#shopGoodsPrice_'+goodsId).attr('dataId');
	$("#flyItem img").attr("src",WST.DOMAIN  +"/"+ goodsThums)
	jQuery.post(Think.U('Home/Cart/addToCartAjax') ,params,function(data) {
		if(type==1){
			location.href= Think.U('Home/Cart/toCart');
		}
	});
}
//修改商品购买数量
function changebuynum(flag){
	var num = parseInt($("#buy-num").val(),10);
	var num = num?num:1;
	if(flag==1){
		if(num>1)num = num-1;
	}else if(flag==2){
		num = num+1;
	}
	var maxVal = parseInt($("#buy-num").attr('maxVal'),10);
	if(maxVal<=num)num=maxVal;
	$("#buy-num").val(num);
}

//获取属性价格
function getPriceAttrInfo(id){

	var goodsId = $("#goodsId").val();
	jQuery.post( Think.U('Home/Goods/getPriceAttrInfo') ,{goodsId:goodsId,id:id},function(data) {
		var json = WST.toJson(data);
		console.log("属性价格");
		console.log(json)
		if(json.id){
			$('#shopGoodsPrice_'+goodsId).html("￥"+json.attrPrice);
			var buyNum = parseInt($("#buy-num").val());
			$("#buy-num").attr('maxVal',json.attrStock);
			$("#goodsStock").html(json.attrStock);
			if(buyNum>json.attrStock){
				$("#buy-num").val(json.attrStock);
			}
			$('#shopGoodsPrice_'+goodsId).attr('dataId',id);
		}
	});
}
function checkStock(obj){
	$(obj).addClass('wst-goods-attrs-on').siblings().removeClass('wst-goods-attrs-on');
	getPriceAttrInfo($(obj).attr('dataId'));
}

function getGoodsappraises(goodsId,p){
	var params = {}; 
	params.goodsId = goodsId;
	params.p = p;
	//加载商品评价
	jQuery.post(Think.U("Home/GoodsAppraises/getGoodsappraises") ,params,function(data) {
		var json = WST.toJson(data);

		
		if(json.root && json.root.length){
			var html = new Array();		    	
			for(var j=0;j<json.root.length;j++){
			    var appraises = json.root[j];	
			    html.push('<tr height="75" style="border:1px dotted #eeeeee;">');
				    html.push('<td width="150" style="padding-left:6px;"><div>'+(appraises.userName?appraises.userName:"匿名")+'</div></td>');
				    html.push('<td width="*"><div>'+appraises.content+'</div></td>');
				    html.push('<td width="180">');
				    html.push('<div>商品评分：');
					for(var i=0;i<appraises.goodsScore;i++){
						html.push('<img src="'+WST.DOMAIN +'/Apps/Home/View/default/images/icon_score_yes.png"/>');
					}
					html.push('</div>');
					html.push('<div>时效评分：');
					for(var i=0;i<appraises.timeScore;i++){
						html.push('<img src="'+WST.DOMAIN +'/Apps/Home/View/default/images/icon_score_yes.png"/>');
					}
					html.push('</div>');
					html.push('<div>服务评分：');
					for(var i=0;i<appraises.serviceScore;i++){
						html.push('<img src="'+WST.DOMAIN +'/Apps/Home/View/default/images/icon_score_yes.png"/>');
					}
					html.push('</div>');
					html.push('</td>');
					
			    html.push('</tr>');	
			}
			$("#appraiseTab").html(html.join(""));
			if(json.totalPage>1){
				laypage({
				    cont: 'wst-page-items',
				    pages: json.totalPage,
				    curr: json.currPage,
				    skip: true,
				    skin: '#e23e3d',
				    groups: 3,
				    jump: function(e, first){
				        if(!first){
				        	getGoodsappraises(goodsId,e.curr);
				        }
				    }
				});
			}
		}else{
			$("#appraiseTab").html("<tr><td><div style='font-size:15px;text-align:center;'>没有评价信息</div></td></tr>");
		}	
	});
}

function favoriteGoods(id){
	if($('#f0_txt').attr('f')=='0'){
		jQuery.post(Think.U("Home/Favorites/favoriteGoods") ,{id:id},function(data) {
			var json = WST.toJson(data,1);
			if(json.status==1){
				$('#f0_txt').html('已关注');
				$('#f0_txt').attr('f',json.id);
			}else if(json.status==-999){
				WST.msg('关注失败，请先登录!',{offset: '200px'});
			}else{
				WST.msg('关注失败!',{offset: '200px'});
			}
		});
	}else{
		id = $('#f0_txt').attr('f');
		cancelFavorites(id,0);
	}
}
function favoriteShops(id){
	if($('#f1_txt').attr('f')=='0'){
		jQuery.post(Think.U("Home/Favorites/favoriteShops") ,{id:id},function(data) {
			var json = WST.toJson(data,1);
			if(json.status==1){
				$('#f1_txt').html('已关注');
				$('#f1_txt').attr('f',json.id);
			}else if(json.status==-999){
				WST.msg('关注失败，请先登录!',{offset: '200px'});
			}else{
				WST.msg('关注失败!',{offset: '200px'});
			}
		});
	}else{
		id = $('#f1_txt').attr('f');
		cancelFavorites(id,1);
	}
}
function cancelFavorites(id,type){
	jQuery.post(Think.U("Home/Favorites/cancelFavorite") ,{id:id,type:type},function(data) {
		var json = WST.toJson(data,1);
		if(json.status==1){
			$('#f'+type+'_txt').html('关注'+((type==1)?'店铺':'商品'));
			$('#f'+type+'_txt').attr('f',0);
		}else{
			WST.msg('取消关注失败!',{offset: '100px'});
		}
	});
}

		// $(function() {
			
			// //Jq初始化样式
			// var dianTiHeight=$('.dianTi').height();
			// $('.dianTi').css('margin-top', -dianTiHeight/2);


			// var f1Top=$('.wst-floor:nth-child(1)').offset().top-100;
			// var f2Top=$('.wst-floor:nth-child(2)').offset().top-100;
			// var f3Top=$('.wst-floor:nth-child(3)').offset().top-100;
			// var f4Top=$('.wst-floor:nth-child(4)').offset().top-100;
			// var f5Top=$('.wst-floor:nth-child(5)').offset().top-100;
			// var f6Top=$('.wst-floor:nth-child(6)').offset().top-100;

			// //检测楼层功能
			// function scrollFn(event) {
				
				// var windowEatHeight=$(window).scrollTop();
				// if(windowEatHeight>=f6Top){

					// console.log('到达6');
					// $('.dianTi li').eq(5).addClass('current').siblings().removeClass('current');
					// $('.dianTi').show();

				// }else if(windowEatHeight>=f5Top){

					// console.log('到达5');
					// $('.dianTi li').eq(4).addClass('current').siblings().removeClass('current');
					// $('.dianTi').show();

				// }
				// else if(windowEatHeight>=f4Top){

					// console.log('到达4');
					// $('.dianTi li').eq(3).addClass('current').siblings().removeClass('current');
					// $('.dianTi').show();

				// }else if(windowEatHeight>=f3Top){

					// console.log('到达3');
					// $('.dianTi li').eq(2).addClass('current').siblings().removeClass('current');
					// $('.dianTi').show();

				// }else if(windowEatHeight>=f2Top){

					// console.log('到达2');
					// $('.dianTi li').eq(1).addClass('current').siblings().removeClass('current');	
					// $('.dianTi').show();

				// }else if(windowEatHeight>=f1Top){

					// console.log('到达1');
					// $('.dianTi li').eq(0).addClass('current').siblings().removeClass('current');	
					// $('.dianTi').show();

				// }else{

					// console.log('页面前半部分');
					// $('.dianTi').hide();

				// }


			// }

			// //监测楼层
			// $(window).scroll(scrollFn);

			
			// //单击跳转
			// $('.dianTi li').click(function(event) {
				
				// $(this).addClass('current').siblings().removeClass('current');
 
				// var i=$(this).index();
				// if(i==5){

					
					// $(window).off('scroll');

					
					// $('html,body').stop().animate({

						// 'scrollTop':f6Top+100

					// }, 500,function(){

						
						// $(window).scroll(scrollFn);


					// });

				// }else if(i==4){
						
					// $(window).off('scroll');
					// $('html,body').stop().animate({
						// 'scrollTop':f5Top+100
					// }, 500,function(){

						// $(window).scroll(scrollFn);


					// });

				// }
				// else if(i==3){
						
					// $(window).off('scroll');
					// $('html,body').stop().animate({
						// 'scrollTop':f4Top+100
					// }, 500,function(){

						// $(window).scroll(scrollFn);


					// });

				// }else if(i==2){
					
					// $(window).off('scroll');
					// $('html,body').stop().animate({
						// 'scrollTop':f3Top+100
					// }, 500,function(){

						// $(window).scroll(scrollFn);


					// });

				// }else if(i==1){
					// $(window).off('scroll');
					// $('html,body').stop().animate({
						// 'scrollTop':f2Top+100
					// }, 500,function(){

						// $(window).scroll(scrollFn);


					// });
				// }else if(i==0){
					// $(window).off('scroll');
					// $('html,body').stop().animate({
						// 'scrollTop':f1Top+100
					// }, 500,function(){

						// $(window).scroll(scrollFn);


					// });
				// }

			// });

		// });






//点击称重
			function toElectronic(e){ 

				if(typeof(isClient)=='undefined'){
	
					WST.msg('请到客户端进行称重', {icon: 6});	
					
				}else{
					RunPHP = { 
						'phpFunc':'__php_getWeigh',
						'param':['192.168.0.115',8080] 
					};
					getElectScal(RunPHP,function(e){  
						console.log('调用状态2222：');
						console.log(e);
						/* if(e==true){ 
							layer.close(ll); 
						} */
					})

					var findHtml = $(e).parent();
					
					var onTimeS = setInterval(function(){
						//console.log('定时器：')
						//console.log(localStorage.elecNumber)
						if(localStorage.elecNumber !== '-1'){  
							
							layer.close(ll);
							var a =WST.msg('正在称重，请稍候', {icon: 6});
							console.log(localStorage.elecNumber)	
							findHtml.find(".electricNumber").html(localStorage.elecNumber);
				
							findHtml.find(".electricTd").attr("hasEleced","1");
							localStorage.elecNumber=-1;	
							clearInterval(onTimeS)							
						}else if(localStorage.elecNumber == '-1'){ 
							var goodname=$(e).attr('name');
							var goodcount=$(e).attr('count');
							
							//var ll = layer.load('请将'+goodname+'放到电子秤上面！'); 
							var ll =WST.msg('请将'+'<span class="countLayer">'+goodcount+'件</span>'+goodname+'放到电子秤上面！<p class="toCloseBtn"><span>关闭</span></p>', {icon: 5});
							layer.close(a);
							$(".toCloseBtn").click(function(){
								
								layer.close(ll);
								 
								clearInterval(onTimeS); 
							})
						}
						
					},1000);
				}	

			}
			

			//过秤选择好之后发货配送

			function checkedElectronic(id){ 
			
				var arrs=[],arrs11=[],arrs2=[];

				for(var i=0;i<$(".electricTd").length;i++){
					console.log("heheheh")
					console.log($(".electricTd").eq(i).attr("hasEleced"))
					if($(".electricTd").eq(i).attr("hasEleced")=="1"){
						$(".electricTd").eq(i).find("span").css({"background":"orange"});
						
						arrs.push($(".electricTd").eq(i).find("span").attr("hasEleced"));
						
						var goodid=String($(".electricTd").eq(i).attr("goodid"));
						console.log("goodid打印======")
						console.log(goodid)
						console.log("value打印")
						console.log($(".electricNumber").eq(i).html());
						if($(".electricNumber").eq(i).html()!==undefined){


							arrs2.push({'goodid':goodid,'goodWeight':$(".electricNumber").eq(i).html()});
						}
						
					}else{
						$(".electricTd").eq(i).find("span").css({"background":"red"});
						
					}
				}
				
				console.log("arr2打印")
				console.log(arrs2)
				
				//var arr3=[];
				//for(var i=0;i<arr2.length;i++){
				//	console.log(arr2)
				//	if(arr2[i]!==undefined){
				//		arr3.push(arr2[i])
				//	}
				//}
				
				if(arrs.length==$(".electricTd").length){
					
				//条件判断-全部过秤选中则发货配送
				console.log("转json")
				console.log(JSON.stringify(arrs2).toString())
					layer.confirm('确定发货吗？',{icon: 3, title:'系统提示'}, function(tips){
						
						var ll =WST.msg('数据处理中，请稍候...');
	//jQuery.post(Think.U("Home/Favorites/cancelFavorite") ,{id:id,type:type},function(data) {

						jQuery.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id,weightGJson:JSON.stringify(arrs2)},function(data){
							layer.close(ll);
							layer.close(tips);
							var json = WST.toJson(data);
							console.log("称重打印11111")
							console.log(json)
							if(json.status>0){
								$(".wst-tab-nav").find("li").eq(statusMark).click();
							}else if(json.status==-1){
								//WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
							}else{
								//WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
							}
					   });
					});
				}else{
					//否则提示用户去选择未选中的过秤商品 
					var ll =WST.msg('请选择未过秤商品...');
					
				}
				
			}

//测试称重数据===================================================================