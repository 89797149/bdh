/****************************商品操作**************************/
					
localStorage.elecNumber=-1;
window.onElectScalRes=(e)=>{
						console.log('电子秤shopcom回调：'); 
						console.log(e);
						localStorage.elecNumber=e; 
}


function queryUnSaleByPage(){
	var shopCatId1 = $('#shopCatId1').val(); 
	var shopCatId2 = $('#shopCatId2').val(); 
	var goodsName = $('#goodsName').val();
	location.href= Think.U('Home/Goods/queryUnSaleByPage','goodsName='+goodsName+"&shopCatId1="+shopCatId1+"&shopCatId2="+shopCatId2); 
}
function queryOnSale(){
	var shopCatId1 = $('#shopCatId1').val();
	var shopCatId2 = $('#shopCatId2').val();
	var goodsName = $('#goodsName').val();
	location.href= Think.U('Home/Goods/queryOnSaleByPage','goodsName='+goodsName+"&shopCatId1="+shopCatId1+"&shopCatId2="+shopCatId2); 
}
function queryPendding(){
	var shopCatId1 = $('#shopCatId1').val();
	var shopCatId2 = $('#shopCatId2').val();
	var goodsName = $('#goodsName').val();
	location.href= Think.U('Home/Goods/queryPenddingByPage','goodsName='+goodsName+"&shopCatId1="+shopCatId1+"&shopCatId2="+shopCatId2); 
}
function toEditGoods(id,menuId){
	location.href= Think.U('Home/Goods/toEdit','umark='+menuId+"&id="+id); 
}
function toViewGoods(id){
	$.post(Think.U('Home/Goods/getGoodsVerify'),{id:id},function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status=='1'){
			var verifyCode = json.verifyCode;
			window.open(Think.U('Home/Goods/getGoodsDetails','goodsId='+id+"&kcode="+verifyCode));
		}
	});
	
}
function delGoods(id){
	layer.confirm("您确定要删除该商品？",{icon: 3, title:'系统提示'},function(tips){
		$.post(Think.U('Home/Goods/del'),{id:id},function(data,textStatus){
			layer.close(tips);
    		var json = WST.toJson(data);
    		if(json.status=='1'){
    			WST.msg('操作成功！', {icon: 1},function(){
    				location.reload();
    			});
    		}else{
    			WST.msg('操作失败', {icon: 5});
    		}
		});
	});
}
function batchDel(){
	layer.confirm("您确定要删除这些商品？",{icon: 3, title:'系统提示'},function(){
	      var ids = WST.getChks('.chk');
	      var loading = layer.load('正在处理，请稍后...', 3);
	      var params = {};
	      params.ids = ids;
	      $.post(Think.U('Home/Goods/batchDel'),params,function(data,textStatus){
	    		var json = WST.toJson(data);
	    		if(json.status=='1'){
	    			WST.msg('操作成功！', {icon: 1},function(){
	    				location.reload();
	    			});
	    		}else{
	    			layer.close(loading);
	    			WST.msg('操作失败', {icon: 5});
	    		}
	     });
	});
}
function sale(v){
	var ids = WST.getChks('.chk');
	if(ids==''){
		WST.msg('请先选择商品!', {icon: 5});
		return;
	}
	layer.confirm((v==1)?"您确定要上架这些商品？":"您确定要下架这些商品？",{icon: 3, title:'系统提示'},function(tips){
	    var loading = layer.load('正在处理，请稍后...', 3);
	    layer.close(tips);
	    var params = {};
	    params.ids = ids;
	    params.isSale= v;
	    $.post(Think.U('Home/Goods/sale'),params,function(data,textStatus){
	    	layer.close(loading);
	    	var json = WST.toJson(data);
	    	if(json.status=='1'){
	    		WST.msg('操作成功！', {icon: 1},function(){
	    			location.reload();
	    		});
	    	}else if(json.status=='-2'){
	    		WST.msg('上架失败，请核对商品信息是否完整!', {icon: 5});
	    	}else if(json.status=='2'){
	    		WST.msg('已成功上架商品'+json.num+" 件，请核对未能上架的商品信息是否完整。", {icon: 5},function(){
	    			location.reload();
	    		});
	    	}else if(json.status=='-3'){
	    		WST.msg('上架商品失败!您的店铺权限不能出售商品，如有疑问请与商城管理员联系。', {icon: 5,time:3000});
	    	}else{
	    		WST.msg('操作失败!', {icon: 5});
	    	}
	    });
	});
}
function goodsSet(type,umark){
	var ids = WST.getChks('.chk');
	if(ids==''){
		WST.msg('请先选择商品!', {icon: 5});
		return;
	}

	layer.load('正在处理，请稍后...', 3);
	var params = {};
	params.ids = ids;
	params.code= type;
	$.post(Think.U('Home/Goods/goodsSet'),params,function(data,textStatus){
	    var json = WST.toJson(data);
	    if(json.status=='1'){
	    	WST.msg('操作成功！', {icon: 1},function(){
	    		location.reload();
	    	});
	    }else{
	    	WST.msg('操作失败!', {icon: 5});
	    }
	});
}

function getShopCatListForGoods(v,id){
	   var params = {};
	   params.id = v;
	   $('#shopCatId2').empty();
	   var html = [];
	   $.post(Think.U('Home/ShopsCats/queryByList'),params,function(data,textStatus){
		    html.push('<option value="">请选择</option>');
			var json = WST.toJson(data);
			if(json.status=='1' && json.list){
				var opts = null;
				for(var i=0;i<json.list.length;i++){
					opts = json.list[i];
					html.push('<option value="'+opts.catId+'" '+((id==opts.catId)?'selected':'')+'>'+opts.catName+'</option>');
				}
			}
			$('#shopCatId2').html(html.join(''));
	   });
}
$.fn.imagePreview = function(options){
	var defaults = {}; 
	var opts = $.extend(defaults, options);
	var t = this;
	xOffset = 5;
	yOffset = 20;
	if(!$('#preview')[0])$("body").append("<div id='preview'><img width='200' src=''/></div>");
	$(this).hover(function(e){
		   $('#preview img').attr('src',WST.DOMAIN+ "/" +$(this).attr('img'));      
		   $("#preview").css("top",(e.pageY - xOffset) + "px").css("left",(e.pageX + yOffset) + "px").show();      
	  },
	  function(){
		$("#preview").hide();
	}); 
	$(this).mousemove(function(e){
		   $("#preview").css("top",(e.pageY - xOffset) + "px").css("left",(e.pageX + yOffset) + "px");
	});
}
function getShopCatListForEdit(v,id){
	   var params = {};
	   params.id = v;
	   $('#shopCatId2').empty();
	   if(v==0){
		   $('#shopCatId2').html('<option value="">请选择</option>');
		   return;
	   }
	   var html = [];
	   $.post(Think.U('Home/ShopsCats/queryByList'),params,function(data,textStatus){
		    html.push('<option value="">请选择</option>');
			var json = WST.toJson(data);
			if(json.status=='1' && json.list){
				var opts = null;
				for(var i=0;i<json.list.length;i++){
					opts = json.list[i];
					html.push('<option value="'+opts.catId+'" '+((id==opts.catId)?'selected':'')+'>'+opts.catName+'</option>');
				}
			}
			$('#shopCatId2').html(html.join(''));
	   });
}
function getBrands(catId){
	var v = $('#brandId').attr('dataVal');
	var params = {};
	params.catId = catId;
	$('#brandId').empty();
	var html = [];
	$('#brandId').append('<option value="0">请选择</option>');
	$.post(Think.U('Home/Brands/queryBrandsByCat'),params,function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status=='1' && json.list){
			for(var i=0;i<json.list.length;i++){
				opts = json.list[i];
				$('#brandId').append('<option value="'+opts.brandId+'" '+((v==opts.brandId)?'selected':'')+'>'+opts.brandName+'</option>');
			}
		}
	})
}
function getCatListForEdit(objId,parentId,t,id){
	   var params = {};
	   params.id = parentId;
	   $('#'+objId).empty();
	   if(t<1){
		   $('#goodsCatId3').empty();
		   $('#goodsCatId3').html('<option value="">请选择</option>');
		   getBrands(parentId);
	   }
	   var html = [];
	   $.post(Think.U('Home/GoodsCats/queryByList'),params,function(data,textStatus){
		    html.push('<option value="">请选择</option>');
			var json = WST.toJson(data);
			if(json.status=='1' && json.list){
				var opts = null;
				for(var i=0;i<json.list.length;i++){
					opts = json.list[i];
					html.push('<option value="'+opts.catId+'" '+((id==opts.catId)?'selected':'')+'>'+opts.catName+'</option>');
				}
			}
			$('#'+objId).html(html.join(''));
	   });
}
function editGoods(menuId){
	var params = WST.fillForm('.wstipt');
	if(params.attrCatId!=0){
		params.priceAttrId = $('.hiddenPriceAttr').attr('dataId');
		params.goodsPriceNo = $('.hiddenPriceAttr').attr('dataNo');
		if(params.priceAttrId>0){
			 var isPriceRecomm = false;
			 for(var i=0;i<=params.goodsPriceNo;i++){
				 if(document.getElementById('price_name_'+params.priceAttrId+'_'+i)){
					  params['price_name_'+params.priceAttrId+'_'+i] = $.trim($('#price_name_'+params.priceAttrId+'_'+i).val());
					  params['price_price_'+params.priceAttrId+'_'+i] = $.trim($('#price_price_'+params.priceAttrId+'_'+i).val());
					  params['price_isRecomm_'+params.priceAttrId+'_'+i] = $('#price_isRecomm_'+params.priceAttrId+'_'+i).prop('checked')?1:0;
					  if(params['price_isRecomm_'+params.priceAttrId+'_'+i]==1)isPriceRecomm = true;
					  params['price_stock_'+params.priceAttrId+'_'+i] = $.trim($('#price_stock_'+params.priceAttrId+'_'+i).val());
					  if(params['price_name_'+params.priceAttrId+'_'+i]==''){
						  WST.msg('请输入商品规格！',{icon: 5});
						  $('#price_name_'+params.priceAttrId+'_'+i).focus();
						  return;
					  }
					  if(params['price_stock_'+params.priceAttrId+'_'+i]==''){
						  WST.msg('请输入商品库存！',{icon: 5});
						  $('#price_stock_'+params.priceAttrId+'_'+i).focus();
						  return;
					  }
				 }
			 }
			 if(!isPriceRecomm){
				   WST.msg('请选择一个推荐的价格，以便在商城展示！',{icon: 5,time:3000});
				   return;
			 }
		}
		$('.attrList').each(function(){
			   //多选项处理
			   if($(this).attr('dataType')==1){
				   var chk = [];
				   $('input[name="attrTxtChk_'+$(this).attr('dataId')+'"]:checked').each(function(){
					   chk.push($(this).val())
				   })
			       params['attr_name_'+$(this).attr('dataId')] = chk.join(',');
			   }else{
				   //普通下拉，文本
				   params['attr_name_'+$(this).attr('dataId')] = $.trim($(this).val());
			   }
		   });
	   }
	   
	   var gallery = [];
	   $('.gallery-img').each(function(){
		   gallery.push($(this).attr('v')+'@'+$(this).attr('iv'));
	   });
	   if(params.goodsDesc==''){
		   WST.msg('请输入商品描述!', {icon: 5});
		   return;
	   }
	   if(params.goodsImg==''){
		   WST.msg('请上传商品图片!', {icon: 5});
		   return;
	   }
	   params.gallery = gallery.join(',');
	   var loading = layer.load('正在提交商品信息，请稍后...', 3);
	   $.post(Think.U('Home/Goods/edit'),params,function(data,textStatus){
		   layer.close(loading);
			var json = WST.toJson(data);
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
					if((menuId=='toEditGoods')){
						location.href= Think.U('Home/Goods/toEdit','umark=toEditGoods');
					}else{
						location.href=Think.U('Home/Goods/'+menuId);
					}
				});
			}else if(json.status=='-2'){
				if(params.isSale==1){
				    WST.msg('您的店铺已被封，如有疑问请与商城管理员联系!', {icon: 5,time:4000},function(){
				    	if((menuId=='toEditGoods')){
							location.href= Think.U('Home/Goods/toEdit','umark=toEditGoods');
						}else{
							location.href=Think.U('Home/Goods/'+menuId);
						}
				    });
				}else{
					WST.msg('操作成功!', {icon: 1}, function(){
						if((menuId=='toEditGoods')){
							location.href= Think.U('Home/Goods/toEdit','umark=toEditGoods');
						}else{
							location.href=Think.U('Home/Goods/'+menuId);
						}
					});
				}
			}else if(json.status=='-3'){
				if(params.isSale==1){
					WST.msg('您的店铺权限不能上架商品，所编辑商品已被存放在仓库中，如有疑问请与商城管理员联系!', {icon: 5,time:4000},function(){
						if((menuId=='toEditGoods')){
							location.href= Think.U('Home/Goods/toEdit','umark=toEditGoods');
						}else{
							location.href=Think.U('Home/Goods/'+menuId);
						}
					});
				}else{
					WST.msg('操作成功!', {icon: 1}, function(){
						if((menuId=='toEditGoods')){
							location.href= Think.U('Home/Goods/toEdit','umark=toEditGoods');
						}else{
							location.href=Think.U('Home/Goods/'+menuId);
						}
					});
				}
			}else{
				WST.msg(json.msg, {icon: 5});
			}
	 });
}
function getAttrList(catId){
	$('#priceContainer').hide();
	$('#attrContainer').hide();
	$('#priceConent').empty();
	$('#attrConent').empty();
	$('.hiddenPriceAttr').attr('dataId',0);
	$('.hiddenPriceAttr').attr('dataNo',0);
	$('.hiddenPriceAttr').val('');
	$('#goodsStock').attr('disabled',false);
	$.post(Think.U('Home/Attributes/getAttributes'),{catId:catId},function(data,textStatus){
		 var json = WST.toJson(data);
		 var priceAttr = null;
		 if(json.status=='1' && json.list){
			var opts = null;
			for(var i=0;i<json.list.length;i++){
				opts = json.list[i];
				if(opts.isPriceAttr==1){
					priceAttr = opts;
				}else{
					addAttr(opts);
					$('#attrContainer').show();
				}
			}
		 }
		 if(priceAttr){
			 $('.hiddenPriceAttr').attr('dataId',priceAttr.attrId);
			 $('.hiddenPriceAttr').attr('dataNo',0);
			 $('.hiddenPriceAttr').val(priceAttr.attrName);
			 addPriceAttr();
			 $('#priceContainer').show();
			 $('#goodsStock').attr('disabled',true);
		 }
	});
}
function addPriceAttr(){
	var goodsPriceNo = $('.hiddenPriceAttr').attr('dataNo');
	goodsPriceNo++;
	var obj = {attrId:$('.hiddenPriceAttr').attr('dataId'),attrName:$('.hiddenPriceAttr').val()};
	var html = [];
	html.push('<tr id="attr_'+goodsPriceNo+'"><td style="text-align:right">'+obj.attrName+'：</td>');
	html.push('<td><input type="text" id="price_name_'+obj.attrId+'_'+goodsPriceNo+'" /></td>');
	html.push('<td><input type="text" id="price_price_'+obj.attrId+'_'+goodsPriceNo+'" value="0" onblur="checkAttPrice('+obj.attrId+','+goodsPriceNo+');" onkeypress="return WST.isNumberdoteKey(event)" onkeyup="javascript:WST.isChinese(this,1)" maxLength="10"/></td>');
	html.push('<td><input type="radio" onclick="checkAttPrice('+obj.attrId+','+goodsPriceNo+');" id="price_isRecomm_'+obj.attrId+'_'+goodsPriceNo+'" name="price_isRecomm"/></td>');
	html.push('<td><input type="text" id="price_stock_'+obj.attrId+'_'+goodsPriceNo+'" onblur="getTstock();" value="100" onkeypress="return WST.isNumberKey(event)" onblur="javascript:statGoodsStaock()" onkeyup="javascript:WST.isChinese(this,1)" maxLength="25"/></td>');
	if(goodsPriceNo==1){
		html.push('<td><a title="新增" class="add btn" href="javascript:addPriceAttr()"></a></td>');
	}else{
	    html.push('<td><a title="删除" class="del btn" href="javascript:delPriceAttr('+goodsPriceNo+')"></a></td></tr>');
	}
	$('.hiddenPriceAttr').attr('dataNo',goodsPriceNo);
	$('#priceConent').append(html.join(''));
	statGoodsStaock();
	getTstock();
}

function checkAttPrice(attrId,goodsPriceNo){
	if($("#price_isRecomm_"+attrId+"_"+goodsPriceNo).is(":checked")){
		$("#shopPrice").val($.trim($("#price_price_"+attrId+"_"+goodsPriceNo).val()));
	}
}

function delPriceAttr(v){
	$('#attr_'+v).remove();
	statGoodsStaock();
	getTstock();
}

function getTstock(){
	var tstock = 0;
	$("input[id^=price_stock_]").each(function(){
		tstock = tstock+parseInt($(this).val());
	});
	$("#goodsStock").val(tstock);
}

function statGoodsStaock(){
	var goodsPriceNo = $('.hiddenPriceAttr').attr('dataNo');
	var attrId = $('.hiddenPriceAttr').attr('dataId');
	var totalStock = 0;
	for(var i=0;i<=goodsPriceNo;i++){
		if(document.getElementById('price_stock_'+attrId+"_"+i)){
			totalStock = totalStock +parseInt($.trim($('#price_stock_'+attrId+'_'+i).val()),10);
		}
	}
	$('#goodsStock').val(totalStock);
}
function addAttr(obj){
	var html = [];
	html.push("<tr id='attr_"+obj.attrId+"'>");
	html.push("<th style='width:80px;text-align:right' nowrap>"+obj.attrName+"：</th><td>");
	if(obj.attrType==0){
		html.push("<input type='text' style='width:70%;' dataId='"+obj.attrId+"' class='attrList'/>");
	}else if(obj.attrType==1){
		if(obj.opts && obj.opts.txt){
			html.push("<input type='hidden' dataType='"+obj.attrType+"' dataId='"+obj.attrId+"' class='attrList'>");
	        for(var i=0;i<obj.opts.txt.length;i++){
	        	html.push("<label><input class='checkboxInput' type='checkbox' name='attrTxtChk_"+obj.attrId+"' value='"+obj.opts.txt[i]+"'/>"+obj.opts.txt[i]+"</label>&nbsp;&nbsp;");
	        }
		}
        html.push("</select>");
	}else if(obj.attrType==2){
		html.push("<select class='attrList' id='attr_name_"+obj.attrId+"' dataId='"+obj.attrId+"'>");
        if(obj.opts && obj.opts.txt){
			for(var i=0;i<obj.opts.txt.length;i++){
	        	html.push("<option value='"+obj.opts.txt[i]+"'>"+obj.opts.txt[i]+"</option>");
	        }
        }
        html.push("</select>");
	}
	html.push("</td></tr>");
	$('#attrConent').append(html.join(''));
}

/*****************************商品分类***********************************/
function editGoodsCat(){
	   var params = {};
	   params.id = $('#id').val();
	   params.parentId = $('#parentId').val();
	   params.catName = $('#catName').val();
	   params.isShow = $('input[name="isShow"]:checked').val();;
	   params.catSort = $('#catSort').val();
	   var loading = layer.load('正在提交商品分类信息，请稍后...', 3);
	   $.post(Think.U('Home/ShopsCats/edit'),params,function(data,textStatus){
		   layer.close(loading);
			var json = WST.toJson(data);
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
				   location.href= Think.U('Home/ShopsCats/index');
				});
			}else{
				layer.msg('操作失败!', {icon: 5});
			}
	   });
}
function delGoodsCat(id){
	layer.confirm("您确定要删除该商品分类吗？",{icon: 3, title:'系统提示'},function(tips){
		layer.load('正在处理，请稍后...', 3);
		layer.close(tips);
		$.post(Think.U('Home/ShopsCats/del'),{id:id},function(data,textStatus){
			var json = WST.toJson(data);
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
					location.reload();
				});
			}else{
				WST.msg('操作失败!', {icon: 5});
			}
		});
	})
}
function editGoodsCatName(obj){
	var name = $('#');
	$.post(Think.U('Home/ShopsCats/editName'),{id:$(obj).attr('dataId'),catName:obj.value},function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status=='1'){
			WST.msg('操作成功!',{icon: 1,time:500});
		}else{
			WST.msg('操作失败!', {icon: 5});
		}
	});
}
function editGoodsCatSort(obj){
	var name = $('#');
	$.post(Think.U('Home/ShopsCats/editSort'),{id:$(obj).attr('dataId'),catSort:obj.value},function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status=='1'){
			WST.msg('操作成功!',{icon: 1,time:500});
		}else{
			WST.msg('操作失败!', {icon: 5});
		}
	});
}

function loadGoodsCatChildTree(obj,pid,objId){
	    var showhtml = "<span class='wst-state_yes'></span>";
	    var hidehtml = "<span class='wst-state_no'></span>";
		var str = objId.split("_");
		level = (str.length-2);
		if($(obj).hasClass('wst-tree-open')){
			$(obj).removeClass('wst-tree-open').addClass('wst-tree-close');
			$('tr[class^="'+objId+'"]').hide();
		}else{
			$(obj).removeClass('wst-tree-close').addClass('wst-tree-open');
			$('tr[class^="'+objId+'"]').show();
		}
}
/*****************商品评价**************************/
function queryAppraises(){
	var shopCatId1 = $('#shopCatId1').val();
	var shopCatId2 = $('#shopCatId2').val();
	var goodsName = $('#goodsName').val();
	location.href= Think.U('Home/GoodsAppraises/index','umark=GoodsAppraises&goodsName='+goodsName+"&shopCatId1="+shopCatId1+"&shopCatId2="+shopCatId2);
}
function getShopCatListForAppraises(v,id){
	   var params = {};
	   params.id = v;
	   $('#shopCatId2').empty();
	   var html = [];
	   $.post(Think.U('Home/ShopsCats/queryByList'),params,function(data,textStatus){
		    html.push('<option value="">请选择</option>');
			var json = WST.toJson(data);
			if(json.status=='1' && json.list){
				var opts = null;
				for(var i=0;i<json.list.length;i++){
					opts = json.list[i];
					html.push('<option value="'+opts.catId+'" '+((id==opts.catId)?'selected':'')+'>'+opts.catName+'</option>');
				}
			}
			$('#shopCatId2').html(html.join(''));
	   });
}
/******************订单列表**********************/
//查看订单
function showOrder(id){
	layer.open({
	    type: 2,
	    title:"订单详情",
	    shade: [0.6, '#000'],
	    border: [0],
	    content: [Think.U('Home/Orders/getOrderDetails','orderId='+id)],
	    area: ['1020px', ($(window).height() - 50) +'px']
	});
}

//打印

var LODOP; //声明为全局变量
function shopOrderAccept(obj,id){ 

	//分配拣货员

	layer.confirm('您确定分配拣货员吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
			layer.close(ll);
	    	
	    $.post(Think.U('Home/sorting/getSorting'),{orderId:id,isJson:1},function(data){
			
			layer.close(tips);
			console.log("getSorting接口返回");
			console.log(data);
			
			if(data.code==1){
				//alert("分配成功")
				WST.msg('分配成功!',{icon: 1,time:500},function(){
					shopOrderAcceptNext(id);
				});

			}else{
				WST.msg('没有拣货员，请先添加拣货员!',{icon: 1,time:500});
			}
			
			//调用打印
			
	   });
	   
		
	   
	});	
	//var element=document.createElement('div');
	//var node=document.createTextNode("外卖订单");
	//element.appendChild(node);

	

	//orderAccept2(id);
}

function shopOrderAcceptNext(id){

		layer.confirm('您确定打印订单吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
			layer.close(ll);
	    	layer.close(tips);
	    $.post(Think.U('Home/Orders/getOrderDetails'),{orderId:id,isJson:1},function(data){
	    	//layer.close(ll);
	    	//layer.close(tips);
			
			console.log("打印数据关于小票打印")
			console.log(data);
			
			//判断是否是客户端并执行打印
			
			if(typeof(isClient)!=='undefined'){ 	
				var newarr=[];
			
				var totalmoney=Number(data.order.realTotalMoney)+Number(data.order.deliverMoney);
				
				for(var i=0;i<data.goodsList.length;i++){
					var totalPrice=Number(data.goodsList[i].shopPrice)*Number(data.goodsList[i].goodsNums);
				
					var optionss = {

							"订单号":data.order.orderNo,
							"订单明细号":data.goodsList[i].orderId,
							"产品":data.goodsList[i].goodsName,
							"数量":Number(data.goodsList[i].goodsNums),
							"单价":Number(data.goodsList[i].shopPrice),
							"总计":totalPrice
					};
					//console.log(i)
					newarr.push(optionss);
				}
				
				
								var _reportData1='{"template":"sale_order2.fr3","ver":4, "Tables":[{"Name":"SaleOrder", "Cols":[{ "type": "str", "size": 20, "name": "订单号", "required": false },{ "type": "datetime", "size": 255, "name": "订购日期", "required": false },{ "type": "float", "size": 255, "name": "配送费", "required": false },{ "type": "str", "size": 255, "name": "收件人地址", "required": false },{ "type": "str", "size": 255, "name": "收件人电话", "required": false },{ "type": "str", "size": 255, "name": "收件人", "required": false },{ "type": "str", "size": 255, "name": "合计", "required": false }],"Data":[{"订单号":"'+data.order.orderNo+'","订购日期":"'+data.order.createTime+'","配送费": "'+data.order.deliverMoney+'","收件人地址": "'+data.order.userAddress+'","收件人电话": "'+data.order.userPhone+'","收件人":"'+data.order.userName+'","合计": "'+totalmoney+'"}]},{"Name":"Table1", "Cols":[{ "type": "str", "size": 20, "name": "订单号", "required": false },{ "type": "str", "size": 20, "name": "订单明细号", "required": false },{ "type": "str", "size": 255, "name": "产品", "required": false },{ "type": "float", "size": 255, "name": "数量", "required": false },{ "type": "number", "size": 255, "name": "单价", "required": false },{ "type": "number", "size": 255, "name": "总计", "required": false }],"Data":'+JSON.stringify(newarr)+'}]}';
				//console.log(_reportData1)
				
				//开始打印小票===
				/**下面四个参数必须放在cfreport_custom.js脚本后面，以覆盖cfreport_custom.js中的默认值**/
				var _delay_send = -1;             //发送打印服务器前延时时长(毫秒)，-1表示不自动打印
				var _delay_close = -1;          //打印完成后关闭窗口的延时时长(毫秒), -1则表示不关闭
				var cfprint_addr = "127.0.0.1";   //打印服务器监听地址
				var cfprint_port = 54321;         //打印服务器监听端口
				_reportData = _reportData1;				//自动打印的数据变更名称是 _reportData ，所以这里重新赋值一下
				
				var getParam = function(){
				  _delay =500;
				  _times = 100;
				  cfprint_addr = "127.0.0.1";
				  cfprint_port = 54321;
				}
				
				//===========
				getParam();
				sendMsg(_reportData1);

				//===========小票打印js12345=====================================================
				
				
			//进行受理
			
			$.post(Think.U('Home/Orders/shopOrderAccept'),{orderId:id},function(data){
				//alert("hhhhhhhhhh")
				layer.close(ll);
				layer.close(tips);
				var json = WST.toJson(data);
				console.log("打印受理")
				console.log(json)
				if(json.status>0){
					$(".wst-tab-nav").find("li").eq(statusMark).click();
				}else if(json.status==-1){
						WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5}); 
				}else{
					WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
				}
			});


				
				//=============小票打印js12345=================================================
			
			}else{

			var pcLayer = layer.load('请转移到客户端进行打印...');
			var element = $("<div></div>");
			element.append('<p style="text-align:center;font-size:26px;">外卖订单</p>')
			if(data.order.isPay=="1"){
				element.append("<p style='text-align:center;font-size:26px;'>(已支付)</p>");
			}
			
			element.append("<p style='text-align:center;'>订单号："+data.order.orderNo+"</p>");
			element.append("<p style='text-align:center;'>下单时间："+data.order.createTime+"</p>");
			var dialog = "";



			for(var i=0;i<data.goodsList.length;i++){
				
				
				dialog+="<li style='text-align:left;height: 30px;line-height: 30px;width:100%;list-style:normal;'>";
					dialog+="<span style='width:50%;'>"+data.goodsList[i].goodsName+"</span>";
					dialog+="<span style='padding-left:5px;'>×"+data.goodsList[i].goodsNums+"</span>";
					dialog+="<span style='width:10%;padding-left:30px;'>"+data.goodsList[i].shopPrice+"</span>";
				dialog+="</li>";

				
			}	
			
			element.append(dialog);
			element.append("<p style='text-align:left;'>配送费："+data.order.deliverMoney+"</p>");
			element.append("<p style='text-align:right;'>原价："+data.order.totalMoney+"</p>");
			
			if(data.order.isSelf==1){
				element.append("<p style='text-align:right;'>配送方式：自提</p>");
				
			}else{
				
				if(data.order.deliverType==0){
				
					element.append("<p style='text-align:right;'>配送方式：商城配送</p>");
				}else if(data.order.deliverType==1){
				
					element.append("<p style='text-align:right;'>配送方式：门店配送</p>");
				}else if(data.order.deliverType==2){
				
					element.append("<p style='text-align:right;'>配送方式：达达配送</p>");
				}else if(data.order.deliverType==3){
				
					element.append("<p style='text-align:right;'>配送方式：蜂鸟配送</p>");
				}else{
					
					element.append("<p style='text-align:right;'>配送方式：无</p>");
				}
				
				
			}
			
			element.append("<p style='text-align:right;'>期望送达时间："+data.order.requireTime+"</p>");
			element.append("<p style='text-align:right;'>备注："+data.order.orderRemarks+"</p>");
			
			element.append("<p style='text-align:right;font-size:26px;font-weight:bold;'>总计："+data.order.realTotalMoney+"</p>");
			element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userName+"</p>");
			element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userPhone+"</p>");
			element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userAddress+"</p>");
		
		
			
			
			//LODOP=getLodop();
			
            //var strFormHtml ="<body>" + element.html() + "</body>";
			
            //LODOP.ADD_PRINT_HTM(40, 80, 257, 800, strFormHtml);
			//LODOP.SET_PRINT_STYLEA(0, "FontSize", 16);

			//if(LODOP.PRINTA()){

			//	$.post(Think.U('Home/Orders/shopOrderAcceptss'),{orderId:id},function(data){
			//		layer.close(ll);
			//		layer.close(tips);
			//		var json = WST.toJson(data);
			//		console.log("打印受理")
			//		console.log(json)
			//		if(json.status>0){
			//			$(".wst-tab-nav").find("li").eq(statusMark).click();
			//		}else if(json.status==-1){
			//			WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5}); 
			//		}else{
			//			WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			//		}
			//	});
			//}else{
			//	console.log("放弃打印");
			//}
			
			}

	   });
	   

	   
	});
	
}	
//打印完成受理订单
//function orderAccept2(id){	
//	layer.confirm('您确定受理该订单吗？', {icon: 3, title:'系统提示'}, function(tips){
//	    var ll = layer.load('数据处理中，请稍候...');
//	    $.post(Think.U('Home/Orders/shopOrderAccept'),{orderId:id},function(data){
//	    	layer.close(ll);
//	    	layer.close(tips);
//	    	var json = WST.toJson(data);
//			if(json.status>0){
//				$(".wst-tab-nav").find("li").eq(statusMark).click();
//			}else if(json.status==-1){
//				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
//			}else{
//				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
//			}
//	   });
//	});

//}

//受理
//function shopOrderAccept(id){
//	layer.confirm('您确定受理该订单吗？', {icon: 3, title:'系统提示'}, function(tips){
//	    var ll = layer.load('数据处理中，请稍候...');
//	    $.post(Think.U('Home/Orders/shopOrderAccept'),{orderId:id},function(data){
//	    	layer.close(ll);
//	    	layer.close(tips);
//	    	var json = WST.toJson(data);
//			if(json.status>0){
//				$(".wst-tab-nav").find("li").eq(statusMark).click();
//			}else if(json.status==-1){
//				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
//			}else{
//				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
//			}
//	   });
//	});
//}

//批量分配分拣员
function batchSorting(){
	
	var aids = WST.getChks('.chk_0');
	
	ids = aids.join(',');

	if(ids==''){
		WST.msg('请选择要受理的订单 !', {icon: 5});
		return;
	}
		
	layer.confirm('您确定分配拣货员吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
			layer.close(ll);
	    	
	    $.post(Think.U('Home/sorting/getSorting'),{orderId:ids,isJson:1},function(data){
			
			layer.close(tips);
			console.log("getSorting接口返回");
			console.log(data);
			
			if(data.code==1){
				//alert("分配成功")
				WST.msg('分配成功!',{icon: 1,time:500},function(){
					batchShopOrderAccept(ids);
				});

			}else{
				WST.msg('没有拣货员，请先添加拣货员!',{icon: 1,time:500});
			}
			
			//调用打印
			
	   });
	   
		
	   
	});	
}

//批量受理
function batchShopOrderAccept(){
	
	var aids = WST.getChks('.chk_0');
	
	ids = aids.join(',');

		if(ids==''){
			WST.msg('请选择要受理的订单 !', {icon: 5});
			return;
		}
		LODOP=getLodop();
		layer.confirm('您确定打印订单吗？',{icon: 3, title:'系统提示'}, function(tips){
			var ll = layer.load('数据处理中，请稍候...');
			var data=[];
			for(var i=0;i<aids.length;i++){
	
				var aidss=aids[i];
				
				$.post(Think.U('Home/Orders/getOrderDetails'),{orderId:aidss,isJson:1},function(data){
					layer.close(ll);
					layer.close(tips);

					
					var element = $("<div></div>");
					element.append('<p style="text-align:center;font-size:26px;">外卖订单</p>')
					if(data.order.isPay=="1"){
						element.append("<p style='text-align:center;font-size:26px;'>(已支付)</p>");
					}
					
					element.append("<p style='text-align:center;'>订单号："+data.order.orderNo+"</p>");
					element.append("<p style='text-align:center;'>下单时间："+data.order.createTime+"</p>");
					var dialog = "";
					for(var i=0;i<data.goodsList.length;i++){
						dialog+="<li style='text-align:left;height: 30px;line-height: 30px;width:100%;list-style:normal;'>";
							dialog+="<span style='width:50%;'>"+data.goodsList[i].goodsName+"</span>";
							dialog+="<span style='padding-left:5px;'>×"+data.goodsList[i].goodsNums+"</span>";
							dialog+="<span style='width:10%;padding-left:30px;'>"+data.goodsList[i].shopPrice+"</span>";
						dialog+="</li>";
					}
					element.append(dialog);
					element.append("<p style='text-align:left;'>配送费："+data.order.deliverMoney+"</p>");
					element.append("<p style='text-align:right;'>原价："+data.order.totalMoney+"</p>");
					element.append("<p style='text-align:right;font-size:26px;font-weight:bold;'>总计："+data.order.realTotalMoney+"</p>");
					element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userName+"</p>");
					element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userPhone+"</p>");
					element.append("<p style='text-align:left;font-size:26px;font-weight:bold;'>"+data.order.userAddress+"</p>");
					
					
					
					var strBodyStyle = "<link type='text/css' rel='stylesheet' href='/Content/Themes/Default/style.css' /><style><!--table { border:1;background-color: #CBCBCC } td {background-color:#FFFFFE;border: 1; } th { background-color:#F1F1F3;padding-left:5px;border:1}--></style>";
					var strFormHtml = strBodyStyle + "<body>" + element.html() + "</body>";
					LODOP.ADD_PRINT_HTM(40, 80, 257, 800, strFormHtml);
					LODOP.SET_PRINT_STYLEA(0, "FontSize", 16);
			
			   //LODOP.PREVIEW();
				//LODOP.PRINT(); 


			if(LODOP.PRINTA()){
			   $.post(Think.U('Home/Orders/batchShopOrderAccept'),{orderIds:ids},function(data){
					layer.close(ll);
					layer.close(tips);
					var json = WST.toJson(data);
					if(json.status>0){
						$(".wst-tab-nav").find("li").eq(statusMark).click();
					}else if(json.status==-1){
						WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
					}else if(json.status==-2){
						WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
							$(".wst-tab-nav").find("li").eq(statusMark).click();
						});
					}else{
						WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
					}
			   });
			}else{
				console.log("放弃打印2");
			}				
					
			   });

		   }


			   
				   
				//LODOP.PRINT(); 
		   
		   //$.post(Think.U('Home/Orders/batchShopOrderAccept'),{orderIds:ids},function(data){
			//	layer.close(ll);
			//	layer.close(tips);
			//	var json = WST.toJson(data);
			//	if(json.status>0){
			//		$(".wst-tab-nav").find("li").eq(statusMark).click();
			//	}else if(json.status==-1){
			//		WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			//	}else if(json.status==-2){
			//		WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
			//			$(".wst-tab-nav").find("li").eq(statusMark).click();
			//		});
			//	}else{
			//		WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			//	}
		   //});
		});
	

	
	
	//layer.confirm('您确定受理这些订单吗？', {icon: 3, title:'系统提示'}, function(tips){
	//    var ll = layer.load('数据处理中，请稍候...');
	//   $.post(Think.U('Home/Orders/batchShopOrderAccept'),{orderIds:ids},function(data){
	//    	layer.close(ll);
	//    	layer.close(tips);
	//    	var json = WST.toJson(data);
	//		if(json.status>0){
	//			$(".wst-tab-nav").find("li").eq(statusMark).click();
	//		}else if(json.status==-1){
	//			WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
	//		}else if(json.status==-2){
	//			WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
	//				$(".wst-tab-nav").find("li").eq(statusMark).click();
	//			});
	//		}else{
	//			WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
	//		}
	//   });
	//});
}
//打包
function shopOrderProduce(id){
	layer.confirm('您确定打包该商品吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/shopOrderProduce'),{orderId:id},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
			if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});
}
//批量打包
function batchShopOrderProduce(){
	var ids = WST.getChks('.chk_1');
	ids = ids.join(',');
	if(ids==''){
		WST.msg('请选择要打包的订单 !', {icon: 5});
		return;
	}
	layer.confirm('您确定打包这些商品吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/batchShopOrderProduce'),{orderIds:ids},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
	    	if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else if(json.status==-2){
				WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
					$(".wst-tab-nav").find("li").eq(statusMark).click();
				});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});
}
//发货配送 <label><input type='checkbox' name='catId' value='1'>自行配送</label><span>达达配送</span>


function shopOrderDelivery(id){
	console.log("获取页面")
	//判断是否称重
	
	$.post(Think.U('Home/Orders/getOrderElectronicScaleList'),{orderId:id,isJson:1},function(data){

	    	var json = WST.toJson(data);
			
			console.log("称重数据")
			console.log(json)

				if(json.goodsList.length>0){ 
					layer.open({
						type: 2,
						title:"发货配送-过秤列表",
						shade: [0.6, '#000'],
						border: [0],
						content: [Think.U('Home/Orders/getOrderElectronicScaleList','orderId='+id)],
						area: ['1020px', ($(window).height() - 250) +'px']
					});
					
				}else{
					lastshopOrderDelivery(id);
				}

	   });
	
	//var aDeliverName="2";
	
	//var w2 = layer.open({
	//	type: 1,
	//	title:"确定发货吗？",
	//	shade: [0.6, '#000'],
	//	border: [0],
		//content: '<ul class="uldeliver"><li class="deliver active" name="2"><a>达达配送</a></li><li class="deliver" name="1"><a>自行配送</a></li></ul>',
	//	area: ['500px', '260px'],
	//	btn: ['提交', '关闭窗口'],
	//	yes: function(){

	//		var ll = layer.load('数据处理中，请稍候...');
			
	//		$.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id,deliverType:aDeliverName},function(data){
	//			layer.close(w2);
	//			layer.close(ll);
	//			var json = WST.toJson(data);
	//			if(json.status>0){
	//				$(".wst-tab-nav").find("li").eq(statusMark).click();
	//			}else if(json.status==-1){
	//				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
	//			}else{
	//				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
	//			}
	//	   });
	//	}
	//});
	
	//$("ul li.deliver[name]").on('click',function(){
	//	$(this).addClass("active").siblings().removeClass("active");
	//		aDeliverName= $(this).attr("name");	
	//})


}






function lastshopOrderDelivery(id){
		
	layer.confirm('确定发货吗111？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
			console.log("发货1111")
			console.log(json)
			if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});
	
}




//自提订单 ztOrderDelivery

function ztOrderDelivery(id){
	
	
	layer.confirm('请到自提订单页面处理',{icon: 3, title:'系统提示'}, function(tips){
		
		location.href= Think.U('Home/OrderSettlements/coupons');

	    //var ll = layer.load('数据处理中，请稍候...');
	    //$.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id},function(data){
	    //	layer.close(ll);
	    //	layer.close(tips);
	    //	var json = WST.toJson(data);
		//	if(json.status>0){
		//		$(".wst-tab-nav").find("li").eq(statusMark).click();
		//	}else if(json.status==-1){
		//		WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
		//	}else{
		//		WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
		//	}
	   //});
	});
}


//批量发货配送
function batchShopOrderDelivery(id){
	var ids = WST.getChks('.chk_2');
	ids = ids.join(',');
	if(ids==''){
		WST.msg('请选择要发货的订单 !', {icon: 5});
		return;
	}
	layer.confirm('您确定这些订单正在发货吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/batchShopOrderDelivery'),{orderIds:ids},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
	    	if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else if(json.status==-2){
				WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
					$(".wst-tab-nav").find("li").eq(statusMark).click();
				});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});
}
//确认收货
function shopOrderReceipt(id){
	layer.confirm('确定已收货吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/shopOrderReceipt'),{orderId:id},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
			if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});
	
}
//同意拒收
function shopOrderRefund(id,type){
	if(type==1){
		layer.confirm('您同意拒收该订单吗？',{icon: 3, title:'系统提示'}, function(tips){
		    var ll = layer.load('数据处理中，请稍候...');
		    $.post(Think.U('Home/Orders/shopOrderRefund'),{orderId:id,type:type},function(data){
		    	layer.close(ll);
		    	layer.close(tips);
		    	var json = WST.toJson(data);
				if(json.status>0){
					$(".wst-tab-nav").find("li").eq(statusMark).click();
				}else if(json.status==-1){
					WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
				}else{
					WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
				}
		   });
		});
	}else{
		var w = layer.open({
		    type: 1,
		    title:"不同意原因",
		    shade: [0.6, '#000'],
		    border: [0],
		    content: '<textarea id="rejectionRemarks" rows="8" style="width:96%" maxLength="100"></textarea>',
		    area: ['500px', '260px'],
		    btn: ['提交', '关闭窗口'],
	        yes: function(index, layero){
	        	var rejectionRemarks = $.trim($('#rejectionRemarks').val());
	        	if(rejectionRemarks==''){
	        		WST.msg('请输入拒收原因 !', {icon: 5});
	        		return;
	        	}
	        	var ll = layer.load('数据处理中，请稍候...');
			    $.post(Think.U('Home/Orders/shopOrderRefund'),{orderId:id,type:type,rejectionRemarks:rejectionRemarks},function(data){
			    	layer.close(w);
			    	layer.close(ll);
			    	var json = WST.toJson(data);
					if(json.status>0){
						$(".wst-tab-nav").find("li").eq(statusMark).click();
					}else if(json.status==-1){
						WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
					}else{
						WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
					}
			   });
	        }
		});
	}
}


//重新配送
function shopOrderRefund2(id){


	//var w2 = layer.open({
	//	type: 1,
	//	title:"确定重新配送吗？",
	//	shade: [0.6, '#000'],
	//	border: [0],
	//	content: '<ul class="uldeliver"><li class="deliver active" name="1"><a>自行配送</a></li></ul>',
	//	area: ['500px', '260px'],
	//	btn: ['提交', '关闭窗口'],
	//	yes: function(){
	//		layer.close(w2);
	//		var ll = layer.load('数据处理中，请稍候...');
	//		
	//		$.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id,deliverType:aDeliverName},function(data){
	//			layer.close(w2);
	//			layer.close(ll);
	//			var json = WST.toJson(data);
	//			if(json.status>0){
	//				$(".wst-tab-nav").find("li").eq(statusMark).click();
	//			}else if(json.status==-1){
	//				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
	//			}else{
	//				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
	//			}
	//	   });
	//	}
	//});
	
	//$("ul li.deliver[name]").on('click',function(){

	//	$(this).addClass("active").siblings().removeClass("active");
		
		
	//})
	
	
	layer.confirm('确定重新发货配送吗？',{icon: 3, title:'系统提示'}, function(tips){
	    var ll = layer.load('数据处理中，请稍候...');
	    $.post(Think.U('Home/Orders/shopOrderDelivery'),{orderId:id,isShopGo:1},function(data){
	    	layer.close(ll);
	    	layer.close(tips);
	    	var json = WST.toJson(data);
			if(json.status>0){
				$(".wst-tab-nav").find("li").eq(statusMark).click();
			}else if(json.status==-1){
				WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
			}else{
				WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
			}
	   });
	});

}
//达达物流 取消订单
function shopOrderRefund3(id){
	layer.confirm('在订单待接单或待取货情况下，可取消订单。注意：接单后1－15分钟内取消订单，运费退回。同时扣除2元作为给配送员的违约金',{icon: 3, title:'系统提示'}, function(tips){
	    //var ll = layer.load('数据处理中，请稍候...');
	    //$.post(Think.U('Home/Orders/shopOrderProduce'),{orderId:id},function(data){
	    //	layer.close(ll);
	    //	layer.close(tips);
	    //	var json = WST.toJson(data);
		//	if(json.status>0){
		//		$(".wst-tab-nav").find("li").eq(statusMark).click();
		//	}else if(json.status==-1){
		//		WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
		//	}else{
		//		WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
		//	}
	   //});
	});
	//var ids = WST.getChks('.chk_2');
	//ids = ids.join(',');
	//if(ids==''){
	//	WST.msg('请选择要发货的订单 !', {icon: 5});
	//	return;
	//}
	//layer.confirm('您确定这些订单正在发货吗？',{icon: 3, title:'系统提示'}, function(tips){
	//    var ll = layer.load('数据处理中，请稍候...');
	//    $.post(Think.U('Home/Orders/batchShopOrderDelivery'),{orderIds:ids},function(data){
	//    	layer.close(ll);
	//    	layer.close(tips);
	//    	var json = WST.toJson(data);
	//    	if(json.status>0){
	//			$(".wst-tab-nav").find("li").eq(statusMark).click();
	//		}else if(json.status==-1){
	//			WST.msg('操作失败，订单状态已发生改变，请刷新后再重试 !', {icon: 5});
	//		}else if(json.status==-2){
	//			WST.msg('操作完成，部分订单状态已发生改变，请注意核对订单状态 !', {icon: 5},function(){
	//				$(".wst-tab-nav").find("li").eq(statusMark).click();
	//			});
	//		}else{
	//			WST.msg('操作失败，请与商城管理员联系 !', {icon: 5});
	//		}
	//   });
	//});
}

function queryOrderPager(statusMark,pcurr){ 

	var param = {};

		param.orderNo = $.trim($("#orderNo_"+statusMark).val());
		param.userName = $.trim($("#userName_"+statusMark).val());
		param.userAddress = $.trim($("#userAddress_"+statusMark).val());
		param.statusMark = statusMark;
		param.pcurr = pcurr;


	var ll = layer.load('数据加载中，请稍候...');
	$.post(Think.U('Home/Orders/queryShopOrders'),param,function(data,textStatus){
			
			var json = WST.toJson(data);
			//$(".wst-order-tips-box").html(json.root.length);
			console.log(json)
			var html = new Array();
			$("#otbody"+statusMark).empty();
			var tmpMsg = '';
				
		
			if(json.root.length>0){
				for(var i=0;i<json.root.length;i++){
					var order = json.root[i];

					html.push("<tr style='color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#0088ec":"#0088ec")+";'>");
					if(order.orderStatus==0 || order.orderStatus==1 || order.orderStatus==2 || order.orderStatus==12 || order.orderStatus==13){
						html.push("<td width='20'><input type='checkbox' class='chk_"+order.orderStatus+" checkboxInput' value='"+order.orderId+"'/></td>");
					}
					html.push("<td width='100'><a href='javascript:;' style='color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#0088ec":"#0088ec")+";font-weight:bold;' onclick=showOrder('"+order.orderId+"')>"+order.orderNo+"</a></td>");
					html.push("<td width='100'>"+order.userName+"</td>");
					if(order.orderStatus<=-3 && order.orderStatus>=-7){
						html.push("<td width='*' title='"+order.rejectionRemarks+"'>"+WST.cutStr(order.rejectionRemarks,40)+"</td>");
					}else if(order.orderStatus==7){
						html.push("");
					}else if(order.orderStatus==8){
						html.push("");
					}else if(order.orderStatus==10){
						html.push("");
					}else if(order.orderStatus==9){
						html.push("");
					}else if(order.orderStatus==11){
						html.push("");
					}else{
						
						html.push("<td width='*'>"+order.userAddress+"</td>");
					}
					if(order.orderStatus==7){
						html.push("<td width='140'>等待骑手接单</td>");
					}else if(order.orderStatus==8){
						html.push("<td width='140'>待取货</td>");
					}else if(order.orderStatus==9){
						html.push("<td width='140'>订单被取消</td>");
					}else if(order.orderStatus==10){
						html.push("<td width='140'>订单过期</td>");
					}else if(order.orderStatus==11){
						html.push("<td width='140'>投递异常</td>");
					}
					html.push("<td width='100' style='font-size:16px;'>"+order.totalMoney+"</td>");
					html.push("<td width='100' style='font-weight:bold;'>"+order.realTotalMoney+"</td>");
					html.push("<td width='100'><div style='line-height:20px;'>"+order.createTime+"</div></td>");
					html.push("<td width='260' style='text-align:center;'>");
					html.push("<a class='ckBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#85ce61":"#85ce61")+"' onclick=showOrder('"+order.orderId+"')>查看</a>");
					if(order.orderStatus==0){
						html.push("<a class='slBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#e6a23c":"#e6a23c")+";margin-left: 11px;' onclick=shopOrderAccept(this,'"+order.orderId+"')>受理</a>");
					}else if(order.orderStatus==1){
						html.push("<a class='dbBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#409eff":"#409eff")+";margin-left: 11px;' onclick=shopOrderProduce('"+order.orderId+"')>打包</a>");
					}else if(order.orderStatus==2){
						
						if(order.isSelf==1){
							html.push("<a class='ztddBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#f56c6c":"#f56c6c")+";margin-left: 11px;' onclick=ztOrderDelivery('"+order.orderId+"')>自提订单</a>");
						}else{
							html.push("<a class='fhpsBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#85ce61":"#85ce61")+";margin-left: 11px;' onclick=shopOrderDelivery('"+order.orderId+"')>发货配送</a>");
						}
					}else if(order.orderStatus==-3){
						html.push("<a class='tyjsBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#f56954":"#f56954")+";margin-left: 11px;' onclick=shopOrderRefund('"+order.orderId+"',1)>同意拒收</a>");
						html.push("<a class='btyjsBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#e6a23c":"#e6a23c")+";margin-left: 11px;' onclick=shopOrderRefund('"+order.orderId+"',-1)>不同意拒收</a>");
					}
					//else if(order.orderStatus==13){
					//	html.push(" | <a href='javascript:;' style='color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"red":"#0088ec")+"' onclick=shopOrderAccept(this,'"+order.orderId+"')>受理</a>");
					//}
					else if(order.orderStatus==7){
						
						//html.push(" | <a href='javascript:;' style='color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"red":"#0088ec")+"' onclick=shopOrderRefund3('"+order.orderId+"',1)>取消订单</a>");
						
					}else if(order.orderStatus==10){
						html.push("<a class='cxpsBtn orderBtns' href='javascript:;' style='background-color:"+((order.orderStatus==-6 || order.orderStatus==-3)?"#f56c6c":"#f56c6c")+";margin-left: 11px;' onclick=shopOrderRefund2('"+order.orderId+"')>重新配送</a>");
						
					}
					html.push("</td>");
				    html.push("</tr>");
				}
				
				$("#otbody"+statusMark).html(html.join(""));
			}
			layer.close(ll);
			if(json.totalPage>1){
	           laypage({
		        	 cont: "wst-page-"+statusMark, 
		        	 pages: json.totalPage, 
		        	 curr: json.currPage,
		        	 skin: '#409eff',
		        	 groups: 3,
		        	 jump: function(e, first){
		        		 if(!first){
		        			 queryOrderPager(statusMark,e.curr);
		        		 }   
		        	 } 
		        });
	       }else{
	    	   $('#wst-page-'+statusMark).remove();
	       }
		   
		});
}

function queryOrderPager2(statusMark,pcurr){
	var param = {};
	param.orderNo = $.trim($("#orderNo_"+statusMark).val());
	param.userName = $.trim($("#userName_"+statusMark).val());
	param.userAddress = $.trim($("#userAddress_"+statusMark).val());
	param.statusMark = 7;
	param.pcurr = pcurr;
	var ll = layer.load('数据加载中，请稍候...');
	$.post(Think.U('Home/Orders/queryShopOrders'),param,function(data,textStatus){
			
			var json = WST.toJson(data);
			var sum=0,sum2=0;
			for(var i=0;i<json.root.length;i++){
				var order = json.root[i];
				if(order.orderStatus==7){

					sum+=order.orderStatus.length;
					
				}else if(order.orderStatus==10){
					//console.log(json.root.length)
					sum2+=order.orderStatus.length;
					//alert(json.root.length-sum)
				}
				

			}
			$(".dqsjd").html(sum);
			$(".dqsjd2").html(json.root.length-sum);
			
			
			layer.close(ll);

		   
		});
}

function queryOrderPager3(statusMark,pcurr){
	var param = {};
	param.orderNo = $.trim($("#orderNo_"+statusMark).val());
	param.userName = $.trim($("#userName_"+statusMark).val());
	param.userAddress = $.trim($("#userAddress_"+statusMark).val());
	param.statusMark = 8;
	param.pcurr = pcurr;
	var ll = layer.load('数据加载中，请稍候...');
	$.post(Think.U('Home/Orders/queryShopOrders'),param,function(data,textStatus){
			
			var json = WST.toJson(data);
			
			console.log("数据打印预售")
			console.log(json)
			var sum3=0;
			
			if(json.root.length>0){
				

			console.log(json.root)

			$(".ysdd").html(json.root.length);
			
							
			}else if(json.root.length==0){
				$(".ysdd").html("0");
				
			}
			layer.close(ll);

		   
		});
}

/*******修改密码 ********************/
function editPass(){
	   var params = {};
	   params.oldPass = $('#oldPass').val();
	   params.newPass = $('#newPass').val();
	   params.reNewPass = $('#reNewPass').val();
	   $.post(Think.U('Home/Users/editPass'),params,function(data,textStatus){
			var json = WST.toJson(data);
			if(json.status=='1'){
				WST.msg('密码修改成功!', {icon: 1}, function(){
					location.reload();
				});
			}else{
				WST.msg('密码修改失败!', {icon: 5});
			}
	   });
}
/***************编辑店铺资料******************/
function getCommunitysForShopEdit(){

	  $.post(Think.U('Home/Areas/getAreaAndCommunitysByList'),{areaId:areaId},function(data,textStatus){
			var json = data;
			if(json.list){
				
				var html = [];
				json = json.list;
				for(var i=0;i<json.length;i++){
					var isAreaSelected = ($.inArray(json[i]['areaId'],relateArea)>-1)?" checked ":"";
					communitysCount = 0
					if (json[i].communitys) {
						for (var j =json[i].communitys.length - 1; j >= 0; j--) {
							if ($.inArray(json[i].communitys[j]['communityId'],relateCommunity) > -1 ) {communitysCount++;};
						};
					};
					html.push("<dl class='areaSelect' id='"+json[i]['areaId']+"'>");
					html.push("<dt class='ATRoot' id='node_"+json[i]['areaId']+"' isshow='0'>"+json[i]['areaName']+"：<span> <input "+((isSelf==1)?"disabled":"")+" type='checkbox' all='1' class='AreaNode checkboxInput' onclick='javascript:selectArea(this)' id='ck_"+json[i]['areaId']+"' "+isAreaSelected+" value='"+json[i]['areaId']+"'><label for='ck_"+json[i]['areaId']+"' "+isAreaSelected+" value='"+json[i]['areaId']+"'>全区配送</label></span> <small>(已选<span class='count'>"+communitysCount+"</span>个社区)</small></dt>");
					if(json[i].communitys && json[i].communitys.length){
						for(var j=0;j<json[i].communitys.length;j++){
							var isCommunitySelected = ($.inArray(json[i].communitys[j]['communityId'],relateCommunity)>-1)?" checked ":"";
							isCommunitySelected += (isAreaSelected!='')?" disabled ":"";
						    html.push("<dd id='node_"+json[i]['areaId']+"_"+json[i].communitys[j]['communityId']+"'><input "+((isSelf==1)?"disabled":"")+" type='checkbox' id='ck_"+json[i]['areaId']+"_"+json[i].communitys[j]['communityId']+"' all='0' class='AreaNode checkboxInput' "+isCommunitySelected+" onclick='javascript:selectArea(this)' value='"+json[i].communitys[j]['communityId']+"'><label for='ck_"+json[i]['areaId']+"_"+json[i].communitys[j]['communityId']+"'>"+json[i].communitys[j]['communityName']+"</label></dd>");
						}
					}
					html.push("</dl>");
				}
				$('#areaTree').html(html.join(''));
				$('#expendAll').parent().removeClass('Hide');
				$('#expendAll').attr('checked','checked');
			}
		});
	}
function selectArea(v){
	  var count = 0;
	  if($(v).attr('all')=='1'){
		$('input[id^="'+$(v).attr('id')+'_"]').each(function(){
			$(this)[0].checked = $(v)[0].checked;
			$(this)[0].disabled = $(v)[0].checked;
			if ($(v)[0].checked) {count++};
		});
	}else{
		$(v).closest('dl').find('input[type="checkbox"]').each(function(){
				if ($(this).prop('checked') == true) { count++};
		});
	}
	$(v).closest('dl').find('.count:first').html(count);
}
function initTime(objId,val){
	for(var i=0;i<24;i++){
		$('<option value="'+i+'" '+((val==i)?"selected":'')+'>'+i+':00</option>').appendTo($('#'+objId));
		$('<option value="'+(i+".5")+'" '+((val==(i+".5"))?"selected":'')+'>'+i+':30</option>').appendTo($('#'+objId));
	}
}
function isInvoce(v){
	if(v){
		$('#invoiceRemarkstr').show();
	}else{
		$('#invoiceRemarkstr').hide();
	}
}
function editShop(){
	   var params = {};
	   params.userName = $('#userName').val();
	   params.shopSn = $('#shopSn').val();
	   params.shopName = $('#shopName').val();
	   params.shopCompany = $('#shopCompany').val();
	   params.shopKeywords = $('#shopKeywords').val();
	   params.shopImg = $('#shopImg').val();
	   params.shopTel = $('#shopTel').val();
	   params.qqNo = $('#qqNo').val();
	   params.deliveryType=$("#deliveryType").val();
	   
	   params.shopAddress = $('#shopAddress').val();
	   params.deliveryCostTime = $('#deliveryCostTime').val();
	   params.deliveryStartMoney = $('#deliveryStartMoney').val();
	   params.deliveryMoney = $('#deliveryMoney').val();
	   params.deliveryFreeMoney = $('#deliveryFreeMoney').val();
	   params.avgeCostMoney = $('#avgeCostMoney').val();
	   params.isInvoice = $("input[name='isInvoice']:checked").val();
	   params.invoiceRemarks = $('#invoiceRemarks').val();
	   params.serviceStartTime = $('#serviceStartTime').val();
	   params.serviceEndTime = $('#serviceEndTime').val();
	   
	   params.latitude = $('#latitude').val();
	   params.longitude = $('#longitude').val();
	   params.mapLevel = $('#mapLevel').val();
	   
	   
	   params.bankId = $('#bankId').val();
	   params.bankNo = $('#bankNo').val();
	   params.bankUserName = $('#bankUserName').val();
	   params.shopAtive = $("input[name='shopAtive']:checked").val();
	   var relateArea = [0];
	   var relateCommunity = [0];
	   $('.AreaNode').each(function(){
			if($(this)[0].checked){
				if($(this).attr('all')==1){
					relateArea.push($(this).val());
				}else{
					relateCommunity.push($(this).val());
				}
			}
	   });
	   var shopAds = new Array();
	   var shopAdsUrl = new Array();
	   $('.gallery-img').each(function(){
		   shopAds.push($(this).attr("v"));
	   });
	   $('.gallery-img-url').each(function(){
		   shopAdsUrl.push($(this).val());
	   });
	   params.shopAds=shopAds.join('#@#');
	   params.shopAdsUrl=shopAdsUrl.join('#@#');
	   params.relateAreaId=relateArea.join(',');
	   params.relateCommunityId=relateCommunity.join(',');
	   var layerIdx = layer.load('正在处理，请稍后...', 3);
	   $.post(Think.U('Home/Shops/edit'),params,function(data,textStatus){
			var json = WST.toJson(data);
			layer.close(layerIdx);
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
					//location.href = location.href;
				});
			}else{
				WST.msg('操作失败!', {icon: 5});
			}
		});
}
/******************店铺设置************************/
function setShop(){
	   var params = {};
	   params.shopTitle = $('#shopTitle').val();
	   params.shopKeywords = $('#shopKeywords').val();
	   params.shopBanner = $('#shopBanner').val();
	   params.appMiaosha = $('#appMiaosha').val();
	   params.appYushou = $('#appYushou').val();
	   params.AppRenqi = $('#AppRenqi').val();
	   params.appMiaoshaSRC = $('#appMiaoshaSRC').val();
	   params.appYushouSRC = $('#appYushouSRC').val();
	   params.AppRenqiSRC = $('#AppRenqiSRC').val();

	   params.appTypeimg = $('#appTypeimgOneSrc').val();
	   params.appTypeimg2 = $('#appTypeimgTwoSrc').val();
	   params.appTypeimg3 = $('#appTypeimgThreeSrc').val();

	   var shopAds = new Array();
	   var shopAdsUrl = new Array();
	   $('.gallery-img').each(function(){
		   shopAds.push($(this).attr("v"));
	   });
	   $('.gallery-img-url').each(function(){
		   shopAdsUrl.push($(this).val());
	   });
	   params.shopAds=shopAds.join('#@#');
	   params.shopAdsUrl=shopAdsUrl.join('#@#');
	   params.shopDesc = $('#shopDesc').val();
	   
	   //2019-04-02 add params
	   params.isSorting = $("input[name='isSorting']:checked").val();
	   params.isReceipt = $("input[name='isReceipt']:checked").val();
	   //alert(params.isSorting)
	   //alert(params.isReceipt)
	   
	   layer.load('正在处理，请稍后...', 3);
	   
	   $.post(Think.U('Home/Shops/editShopCfg'),params,function(data,textStatus){
			var json = WST.toJson(data);
			console.log("保存接口============")
			console.log(json)
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
					location.href=location.href;
				});
			}else{
				WST.msg('操作失败!', {icon: 5});
			}
		});
}
function logout(){
	jQuery.post(Think.U('Home/Shops/logout'),{},function(rsp) {
		location.reload();
	});
}
function checkLogin(){
	jQuery.post(Think.U('Home/Shops/checkLoginStatus'),{},function(rsp) {
		var json = WST.toJson(rsp);
		if(json.status && json.status==-999)location.reload();
	});
}
/***************商品类型****************/
function editAttrCats(type,src){
	var catName = $.trim($('#catName').val());
	if(catName==''){
		WST.msg('请输入商品类型名称!', {icon: 5});
		return;
	}
	var loading = layer.load('正在处理，请稍后...', 3);
	$.post(Think.U('Home/AttributeCats/edit'),{umark:src,catName:catName,id:$('#id').val()},function(data,textStatus){
		layer.close(loading);
		var json = WST.toJson(data);
		if(json.status=='1'){
			WST.msg('操作成功!', {icon: 1}, function(){
				if(type==1){
					$('#myform')[0].reset();
				}else{
				    location.href=Think.U('Home/AttributeCats/index');
				}
			});
		}else{
			WST.msg('操作失败!', {icon: 5});
		}
	});
}
function delAttrCat(id){
	layer.confirm("您确定要删除该商品类型及其下的属性吗？",{icon: 3, title:'系统提示'},function(tips){
	    var loading = layer.load('正在处理，请稍后...', 3);
	    layer.close(tips);
	    var params = {};
	    $.post(Think.U('Home/AttributeCats/del'),{id:id},function(data,textStatus){
	    	layer.close(loading);
	    	var json = WST.toJson(data);
	    	if(json.status=='1'){
	    		WST.msg('操作成功！', {icon: 1},function(){
	    			location.reload();
	    		});
	    	}else{
	    		WST.msg('操作失败!', {icon: 5});
	    	}
	    });
	});
}
/***********商品属性************/
function getAttrsForAttr(){
	location.href=Think.U("Home/Attributes/index",'catId='+$('#catId').val());
}
function toAddAttr(){
	var attrNoForAttr = $('#catId').attr('dataNo');
	attrNoForAttr++
	var html = [];
	html.push("<tr id='tr_"+attrNoForAttr+"' dataId='0'><td>&nbsp;</td>");
	html.push("<td><input type='text' id='attrName_"+attrNoForAttr+"'/></td>");
	html.push("<td><input type='radio' name='isPriceAttr' id='isPriceAttr_"+attrNoForAttr+"' onclick='javascript:chkPriceAttrForAttr()' id='isPriceAttr_"+attrNoForAttr+"'></td>");
	html.push("<td><select id='attrType_"+attrNoForAttr+"' onchange='javascript:changeAttrTypeForAttr("+attrNoForAttr+")'><option value='0'>输入框</option/><option value='1'>多选项</option/><option value='2'>下拉项</option/></select>");
    html.push("</td>");
    html.push("<td><input type='text' id='attrContent_"+attrNoForAttr+"' style='width:300px;display:none'/></td>");
    html.push("<td><input type='text' id='attrSort_"+attrNoForAttr+"'/></td>");
    html.push("<td>");
    html.push("<a href='javascript:delAttrs("+attrNoForAttr+",0)' class='btn del' title='删除'></a>");
    html.push("</td>");
    html.push("</tr>");
    $('#tbody').append(html.join(''));
    $('#catId').attr('dataNo',attrNoForAttr);
    $('.wst-btn-query').show();
}
function changeAttrTypeForAttr(v){
	if($('#attrType_'+v).val()==0){
		$('#attrContent_'+v).hide();
	}else{
		$('#attrContent_'+v).show();
	}
}
function chkPriceAttrForAttr(){
	var attrNoForAttr = $('#catId').attr('dataNo');
	for(var i=0;i<attrNoForAttr;i++){
		if(!document.getElementById('tr_'+i))continue;
		if($('#isPriceAttr_'+i)[0].checked){
			$('#attrType_'+i).hide();
			$('#attrContent_'+i).hide();
		}else{
			$('#attrType_'+i).show();
			if($('#attrType_'+i).val()==1 || $('#attrType_'+i).val()==2){
				$('#attrContent_'+i).show();
			}
		}
	}
}
function editAttrs(){
	var attrNoForAttr = $('#catId').attr('dataNo');
	var params = {}
	params.catId = $('#catId').val();
	params.no = attrNoForAttr;
	for(var i=0;i<=attrNoForAttr;i++){
		if(!document.getElementById('tr_'+i))continue;
		params['id_'+i] = $('#tr_'+i).attr('dataId');
		var isPriceAttr = $('#isPriceAttr_'+i)[0].checked?1:0;
		params['isPriceAttr_'+i] = isPriceAttr;
		params['attrName_'+i] = $.trim($('#attrName_'+i).val());
		if(params['attrName_'+i]==''){
			WST.msg('请输入属性名称!', {icon: 5});
			$('#attrName_'+i).focus();
			return;
		}
		params['attrType_'+i] = $('#attrType_'+i).val();
		params['attrContent_'+i] = $.trim($('#attrContent_'+i).val());
		if((params['attrType_'+i]==1 || params['attrType_'+i]==2) && isPriceAttr==0  && params['attrContent_'+i]==''){
			WST.msg('请输入属性选项值!', {icon: 5});
			$('#attrContent_'+i).focus();
			return;
		}
		params['attrSort_'+i] = $.trim($('#attrSort_'+i).val());
	}
	var loading = layer.load('正在处理，请稍后...', 3);
	$.post(Think.U('Home/attributes/edit'),params,function(data,textStatus){
		layer.close(loading);
		var json = WST.toJson(data);
		if(json.status=='1'){
			WST.msg('操作成功!', {icon: 1}, function(){
				location.href=Think.U('Home/Attributes/index','catId='+$('#catId').val());
			});
		}else{
			WST.msg('操作失败!', {icon: 5});
		}
	});
}
function delAttrs(no,id){
	if(id>0){
		layer.confirm("您确定要删除该商品属性吗？",{icon: 3, title:'系统提示'},function(tips){
		    var loading = layer.load('正在处理，请稍后...', 3);
		    layer.close(tips);
		    var params = {};
		    $.post(Think.U('Home/Attributes/del'),{id:id},function(data,textStatus){
		    	layer.close(loading);
		    	var json = WST.toJson(data);
		    	if(json.status=='1'){
		    		WST.msg('操作成功！', {icon: 1},function(){
		    			location.reload();
		    		});
		    	}else{
		    		WST.msg('操作失败!', {icon: 5});
		    	}
		    });
		});
	}else{
		$('#tr_'+no).remove();
	}
}
function batchMessageDel(){
	  layer.confirm("您确定要删除这些消息？",function(){
	        var ids = WST.getChks('.chk');
	        layer.load('正在处理，请稍后...', 3);
	        var params = {};
	        params.ids = ids;
	        $.post(Think.U('Home/Messages/batchDel'),params,function(data,textStatus){
	          var json = WST.toJson(data);
	          if(json.status=='1'){
	        	  WST.msg('操作成功！', {icon: 1},function(){
	              location.reload();
	            });
	          }else{
	        	  WST.msg('操作失败', {icon: 5});
	          }
	       });
	  });
	}


function toEditGoodsBase(fv,goodsId,flag){	
	if((fv==2 || fv==3) && flag==1){
		WST.msg('该商品存在商品属性，不能直接修改，请进入编辑页修改', {icon: 5});
		return;
	}else{
		$("#ipt_"+fv+"_"+goodsId).show();
		$("#span_"+fv+"_"+goodsId).hide();
		$("#ipt_"+fv+"_"+goodsId).focus();
		$("#ipt_"+fv+"_"+goodsId).val($("#span_"+fv+"_"+goodsId).html());
	}
	
}

function endEditGoodsBase(fv,goodsId){
	$('#span_'+fv+'_'+goodsId).html($('#ipt_'+fv+'_'+goodsId).val());
	$('#span_'+fv+'_'+goodsId).show();
    $('#ipt_'+fv+'_'+goodsId).hide();
}

function editGoodsBase(fv,goodsId){
	var vtext = $('#ipt_'+fv+'_'+goodsId).val();
	if($.trim(vtext)==''){
		if(fv==1){
			WST.msg('商品编号不能为空', {icon: 5});
		}else if(fv==2){
			WST.msg('价格不能为空', {icon: 5});
		}else if(fv==3){
			WST.msg('库存不能为空', {icon: 5});
		}		
        return;
	}
	jQuery.post(Think.U('Home/Goods/editGoodsBase'),{vfield:fv,goodsId:goodsId,vtext:vtext},function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status>0){
			$('#img_'+fv+'_'+goodsId).fadeTo("fast",100);
			endEditGoodsBase(fv,goodsId);
			$('#img_'+fv+'_'+goodsId).fadeTo("slow",0);
		}else{
			WST.msg('修改失败!', {icon: 5}); 
		}
	});
}

function changeCatStatus(isShow,id,pid){
	var params = {};
	params.id = id;
	params.isShow = isShow;
	params.pid = pid;
	$.post(Think.U('Home/ShopsCats/changeCatStatus'),params,function(data,textStatus){
		location.reload();  
	});
	
}



function changSaleStatus(goodsId,flag){
	var tak = "";
	if(flag==1){
		tak = "isRecomm";
	}else if(flag==2){
		tak = "isBest";
	}else if(flag==3){
		tak = "isNew";
	}else if(flag==4){
		tak = "isHot";
	}else if(flag==5){
		tak = "isSale";
	}
	var tamk = $("#"+tak+"_"+goodsId).val();
	jQuery.post(Think.U('Home/Goods/changSaleStatus'),{goodsId:goodsId,tamk:tamk,flag:flag},function(data,textStatus){
		var json = WST.toJson(data);
		if(json.status>0){
			if(tamk == 0){
				tamk = 1;
				$("#"+tak+"_div_"+goodsId).html("<span class='wst-state_yes'></span>");
			}else{
				tamk = 0;
				$("#"+tak+"_div_"+goodsId).html("<span class='wst-state_no'></span>");
			}
			$("#"+tak+"_"+goodsId).val(tamk);
			
			
		}else{
			WST.msg('修改失败!', {icon: 5}); 
		}
	});
}
function addGoodsCat(obj,p,catNo){
	var html = new Array();
	if(typeof(obj)=="number"){
		html.push("<tbody class='tbody_new'>");
		html.push("<tr class='tr_new' isLoad='1'>");
		html.push("<td>");
		html.push("<span class='wst-tree-open'>&nbsp;</span>");
		html.push("<input class='catname' type='text' style='width:400px;height:22px;margin-left:6px;' dataId=''/>");
		html.push("</td>"); 
		html.push("<td><input class='catsort' type='text' style='width:35px;' value='0' onkeyup='javascript:WST.isChinese(this,1)' onkeypress='return WST.isNumberKey(event)'/></td>");
		html.push("<td style='cursor:pointer;'><input class='catshow checkboxInput' type='checkbox' checked/></td>");    
		html.push("<td>");
		html.push("<span onclick='addGoodsCat(this,0,0);' class='add btn' title='新增'></span>"); 
		html.push("<span class='del btn' title='删除' onclick='delGoodsCatObj(this,1)'></span>&nbsp;");
		html.push("</td>");  
		html.push("</tr>"); 
		html.push("</tbody>");
		$("#cat_list_tab").append(html.join(""));
	}else{
		var className = (p==0)?"tr_c_new":"tr_"+catNo+" tr_0";
		html.push("<tr class='"+className+"' isLoad='1' catId='"+p+"'>");
		html.push("<td>");
		html.push("<span class='wst-tree-second'>&nbsp;</span>&nbsp;");  
		html.push("<input class='catname' type='text' style='width:400px;height:22px;' dataId=''/>");
		html.push("</td>"); 
		html.push("<td><input class='catsort' type='text' style='width:35px;' value='0' onkeyup='javascript:WST.isChinese(this,1)' onkeypress='return WST.isNumberKey(event)'/></td>");
		html.push("<td style='cursor:pointer;' ><input class='catshow checkboxInput' type='checkbox' checked /></td>");    
		html.push("<td>");
		html.push("<span class='del btn' title='删除' onclick='delGoodsCatObj(this,2)'></span>&nbsp;");
		html.push("</td>");  
		html.push("</tr>");
		$(obj).parent().parent().parent().append(html.join(""))
	}
	$('.wst-btn-query').show();
}

function batchSaveShopCats(){
	var params = {};
	var fristNo = 0;
	var secondNo = 0;
	//params.icon = $("#icons").val()
	
	
	$(".tbody_new").each(function(){
		secondNo = 0;
		var pobj = $(this).find(".tr_new");
		params['catName_'+fristNo] = $.trim(pobj.find(".catname").val());
		if(params['catName_'+fristNo]==''){
			WST.msg('请输入商品分类名称!', {icon: 5});
			return;
		}
		params['catSort_'+fristNo] = pobj.find(".catsort").val();
		
		params['icon_'+fristNo] = pobj.find(".icon").attr("imgFileName");
		
		params['catShow_'+fristNo] = pobj.find(".catshow").prop("checked")?1:0
		$(this).find(".tr_c_new").each(function(){
			params['catId_'+fristNo+'_'+secondNo] = fristNo;
			params['catName_'+fristNo+'_'+secondNo] = $.trim($(this).find(".catname").val());
			if(params['catName_'+fristNo+'_'+secondNo]==''){
				WST.msg('请输入商品分类名称!', {icon: 5});
				return;
			}
			params['catSort_'+fristNo+'_'+secondNo] = $(this).find(".catsort").val();
			params['catShow_'+fristNo+'_'+secondNo] = $(this).find(".catshow").prop("checked")?1:0
			params['catSecondNo_'+fristNo] = ++secondNo;		
		});
		params['fristNo'] = ++fristNo;
	});
	var otherNo = 0;
	$(".tr_0").each(function(){
		params['catId_o_'+otherNo] = $(this).attr('catId');
		params['catName_o_'+otherNo] = $.trim($(this).find(".catname").val());
		if(params['catName_o_'+otherNo]==''){
			WST.msg('请输入商品分类名称!', {icon: 5});
			return;
		}
		params['catSort_o_'+otherNo] = $(this).find(".catsort").val();
		params['catShow_o_'+otherNo] = $(this).find(".catshow").prop("checked")?1:0;
		params['otherNo'] = ++otherNo;
	});

	$.post(Think.U('Home/ShopsCats/batchSaveShopCats'),params,function(data,textStatus){

		var json = WST.toJson(data);
		if(json.status==1){
			WST.msg('新增成功!', {icon: 1,time:500},function(){
				//location.reload();
			}); 
		}else{
			WST.msg('新增失败!', {icon: 5}); 
		}
	});
}

function delGoodsCatObj(obj,vk){
	if(vk==1){
		$(obj).parent().parent().parent().remove();
	}else{
		$(obj).parent().parent().remove();
	}
	if($(".tr_0").size()==0 && $(".tbody_new").size()==0)$('.wst-btn-query').hide();
}
function respondInit(){
	var uploading = null;
	var uploader = WebUploader.create({
	      auto: true,
	      swf: WST.PUBLIC +'/plugins/webuploader/Uploader.swf',
  	  server:Think.U('Home/OrderComplains/uploadPic'),
  	  pick:'#filePicker',
  	  accept: {
		    title: 'Images',
		    extensions: 'gif,jpg,jpeg,bmp,png',
		    mimeTypes: 'image/*'
	      },
	      fileNumLimit:5,
  	  formData: {dir:'complains'}
 });
 uploader.on('uploadSuccess', function( file,response ) {
	    var json = WST.toJson(response._raw);
	    layer.close(uploading);
	    if(json.status==1){
	    	var tdiv = $("<div style='width:100px;float:left;margin-right:5px;'>"+
		             "<img class='complain_pic' width='100' height='100' src='"+WST.DOMAIN+"/"+json.file.savepath+json.file.savename+"'></div>");
			var btn = $('<div style="position:relative;top:-100px;left:80px;cursor:pointer;" ><img src="'+WST.DOMAIN+'/Apps/Home/View/default/images/action_delete.gif"></div>');
			tdiv.append(btn);
			$('#picBox').append(tdiv);
			btn.on('click','img',function(){
				uploader.removeFile(file);
				$(this).parent().parent().remove();
				uploader.refresh();
			});
	    }
	});
	uploader.on('uploadError', function( file ) {
		WST.msg('上传图片失败!', {icon: 5});
	});
	uploader.on( 'uploadProgress', function( file, percentage ) {
		uploading = WST.msg('正在上传图片，请稍后...');
	});
}
function getComplainList(){
	location.href=Think.U('Home/OrderComplains/queryShopComplainByPage','orderNo='+$.trim($('#orderNo').val()));
}
function saveRespond(historyUrl){

	var params = WST.fillForm('.wstipt');
	var img = [];
	$('.complain_pic').each(function(){
		img.push($(this).attr('src').replace(WST.DOMAIN+"/",""));
	});
	params.respondAnnex = img.join(',');
	if(params.complainContent==''){ 
		WST.msg('应诉内容不能为空！', {icon: 5});
		return;
	}
	
	params.isPay=$("input[name='isPay']:checked").val();

	var ll = WST.msg('正在提交应诉信息，请稍候...', {icon: 16,shade: [0.5, '#B3B3B3']});
	jQuery.post(Think.U('Home/OrderComplains/saveRespond') ,params,function(data) {
		 layer.close(ll);
		 var json = WST.toJson(data);

		 if(json.status==1){
			 WST.msg('您的应诉已提交，请留意信息回复', {icon: 6},function(){
				 location.href=historyUrl;
			 });
		 }else{
			 WST.msg(json.msg, {icon: 5});
		 }
    });
}
function closeComplainBox(){
	var index = parent.layer.getFrameIndex(window.name);
	parent.layer.close(index);
}

function getShopMsgTips(){
	
	$.post(Think.U('Home/Orders/getShopMsgTips'),{},function(data,textStatus){
		var json = WST.toJson(data);
		console.log("jsonshuju")
		console.log(json)
		for(var i in json){
			if(json[i]>0){
				if(json['100']>0){
					$("#li_toShopOrdersList .wst-msg-tips-box").html(json['100']);
					$("#li_toShopOrdersList .wst-msg-tips-box").show();
				}
				if(json['100000']>0){
					$("#li_queryMessageByPage .wst-msg-tips-box").html(json['100000']);
					$("#li_queryMessageByPage .wst-msg-tips-box").show();
				}
				$("#wst-msg-li-"+i+" .wst-order-tips-box").show();
			}else{
				$("#wst-msg-li-"+i+" .wst-order-tips-box").hide();
			}
			if((i==0 && json[i]>0) || json['100']>0){
				playsound();
			}
			//$("#wst-msg-li-"+i+":not(:nth-child(3)) .wst-order-tips-box").html(json[i]);
			$("#wst-msg-li-"+i+" .wst-order-tips-box").html(json[i]);
			//$(".ysdd").html(json['100']);
		}
	});
}

/**
 * 获取提现帐号列表
 */
function getCashConfigsList(p){
	var tips = WST.msg('正在加载记录,请稍候...',{time:600000000});
	var params = {};
	params.p = p;
	$.post(Think.U('Home/CashConfigs/getCashConfigsList'),params,function(data,textStatus){
		layer.close(tips);
	    var json = WST.toJson(data);
	       	var gettpl = document.getElementById('tblist').innerHTML;
	       	laytpl(gettpl).render(json.root, function(html){
	       	    $('#wst-score-page').html(html);
	       	});
	       	if(json.totalPage>1){
	       		laypage({
		        	 cont: 'wst-page', 
		        	 pages:json.totalPage, 
		        	 curr: json.currPage,
		        	 skin: '#409eff',
		        	 groups: 3,
		        	 jump: function(e, first){
		        		    if(!first){
		        		    	getCashConfigsList(e.curr);
		        		    }
		        	    } 
		        });
	       	}else{
	       		$('#wst-page').empty();
	       	}
       
	});
}

$(function() {
	//loadAudio();
	getShopMsgTips();
	queryOrderPager2();
	queryOrderPager3();
	setInterval("getShopMsgTips()",30000);
	setInterval("queryOrderPager2()",30000);
	setInterval("queryOrderPager3()",30000);
});

