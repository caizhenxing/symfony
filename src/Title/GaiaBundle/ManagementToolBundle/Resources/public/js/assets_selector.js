musi.addLimitEvent(window, "load", function(){
	var data = musi.fromJson(musi.parasite("#assets_selector").text());
	if(data == null || data.length == 0){
		alert("twigのパラメータにassets_selectorのデータがありません\n以下の様な行を追加して、パラメータをtwigに渡してください\n\n $param['assets_selector'] = $this->get('title.mng_tool.service.assets_selector')->create();");
		return;
	}
	var resetForm = function(type,item,type_id,item_id){
		type_id = Number(type_id);
		item_id = Number(item_id);
		set_iid = null;
		if(type_id != type.id){
			item.releaseChild();

			var tdata = data[0];
			for(var j=1,jen=data.length;j<jen;++j){
				if(data[j].id == type_id){
					tdata = data[j]; 
					break;
				}
			}
			type.id = tdata.id;
			for(var i=0,len=tdata.list.length;i<len;++i){
				var d = tdata.list[i];
				var opt = new musi.Parasite("option");
				opt.attr("value",d.id+"");
				opt.text(d.name);
				if(set_iid === null)
					set_iid = d.id;
				if(d.id == item_id){
					opt.attr("selected",null);
					set_iid = d.id;
				}
				item.appendChild(opt);
			}
		}
		item.id = set_iid;
	};
	var aform = musi.parasites(".assets-form");
	var itype = 0;
	var iitem = 0;
	var buf = null;
	for(var i=0,len=aform.length;i<len;++i){
		(new function(){
			var type = musi.parasite(".assets-type", aform[i]);
			var item = musi.parasite(".assets-item", aform[i]);
			itype = type.attr("init");
			itype = musi.isNumber(itype)?Number(itype):0;
			type.id = -1;
			iitem = item.attr("init");
			iitem = musi.isNumber(iitem)?Number(iitem):0;
			item.id = -1;
			
			
			for(var j=0,jen=data.length;j<jen;++j){
				var d = data[j];
				var opt = new musi.Parasite("option");
				opt.addClass("tac");
				opt.attr("value",d.id+"");
				opt.text(d.name);
				if(d.id == itype)
					opt.attr("selected",null);
				type.appendChild(opt);
			}
			
			resetForm(type,item,itype,iitem);
			type.addEvent("change",function(){
				resetForm(type,item,type.value(),0);
			});
			item.addEvent("change",function(){
				resetForm(type,item,type.id,item.value());
			});
		});
	}
},1);