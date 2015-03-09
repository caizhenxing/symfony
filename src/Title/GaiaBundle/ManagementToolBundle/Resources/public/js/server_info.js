(new function(){
	
	/**
	 * @type object
	 * input型のmusi.Parasiteを保管する
	 */
	var links = {
		"apv" : null,
		"abs" : null,
		"abv" : null,
		"mtc" : []
	};
	
	/**
	 * 文字列がnullか空文字風かどうか判定
	 */
	var isNullorEnpty = function(str){
		if(str == null) return true;
		str = (str+"").replace(/\s/g,"");
		return str == "";
	};
	
	/**
	 * Inputの内容からオブジェクト型のデータを生成する
	 */
	var createData = function(){
		var ret = {};
		if(links.apv != null)
			ret["apv"] = links.apv.value();
		if(links.abs != null)
			ret["abs"] = links.abs.value();
		if(links.abv != null)
			ret["abv"] = links.abv.value();
		var mtc = [];
		for(var i=0,len=links.mtc.length;i<len;++i){
			mtc.push(links.mtc[i].value());
		}
		ret["mtc"] = mtc;
		return ret;
	};

	/**
	 * マッチングサーバーの入力欄をチェックして、入力欄を整理する
	 */
	var matchCheck = function(){
		var data = createData();
		var mtc = [];
		var len=data.mtc.length;
		var update = false;
		for(var i=0;i<len-1;++i){
			var v = data.mtc[i];
			if(!isNullorEnpty(v)){
				mtc.push(v);
			}else{
				update = true;
			}
		}
		var v = data.mtc[len-1];
		if(!isNullorEnpty(v)){
			update = true;
			mtc.push(v);
		}
		if(update){
			data.mtc = mtc;
			reload(data);
		}
	};
	
	/**
	 * 画面のリロード
	 * @param {object} data {"apv":{string}アプリバージョン,"abs":{string}アセットバンドルサーバー,"abv":{string}アセットバンドルバージョン,"mtc":{array<string>}マッチングサーバーリスト}型のオブジェクト
	 */
	var reload = function(data){
		var box = musi.parasite("#titlecontents");
		box.removeChild();
		
		var line = null;
		var input = null;
		var label = null;
		line = gutil.makeLine(false);
		label = gutil.makeLabel("アプリバージョン");
		input = gutil.makeInput(gutil.DATA_STR, data.apv);
		line.appendChild(label);
		line.appendChild(input);
		box.appendChild(line);
		links.apv = input;

		line = gutil.makeLine(false);
		label = gutil.makeLabel("AssetBundleサーバー");
		input = gutil.makeInput(gutil.DATA_STR, data.abs);
		line.appendChild(label);
		line.appendChild(input);
		box.appendChild(line);
		links.abs = input;

		line = gutil.makeLine(false);
		label = gutil.makeLabel("AssetBundleバージョン");
		input = gutil.makeInput(gutil.DATA_STR, data.abv);
		line.appendChild(label);
		line.appendChild(input);
		box.appendChild(line);
		links.abv = input;

		if(data.mtc == null || !musi.isArray(data.mtc)){
			data.mtc = [];
		}
		var mtc = [];
		line = gutil.makeLine(false);
		label = gutil.makeLabel("マッチングサーバー");
		line.appendChild(label);
		for(var i=0,len=data.mtc.length;i<len;++i){
			var v = data.mtc[i];
			if(!isNullorEnpty(v)){
				input = gutil.makeInput(gutil.DATA_STR, v);
				input.addEvent("change", matchCheck);
				line.appendChild(input);
				mtc.push(input);
			}
		}

		if(mtc.length < 1){
			line = gutil.makeLine(false);
			label = gutil.makeLabel("マッチングサーバー");
			line.appendChild(label);
		}
		input = gutil.makeInput(gutil.DATA_STR, "");
		input.addEvent("change", matchCheck);
		mtc.push(input);
		line.appendChild(input);
		box.appendChild(line);
		links.mtc = mtc;
		
		// ボタン
		line = gutil.make2BtnLine(["e634","e61b"], ["再読込","保存"], [function(){
			if(confirm("データを再読み込みします")){
				gutil.ajax(JUMP_URL.server_info_data, createData(), function(rpc,code){
					if(rpc == null){
						gutil.Log.e("ネットワークエラー:"+code);
					}else if(rpc.code == gutil.Rpc.CODE_SUCCESS){
						gutil.Log.i("再読込成功");
						reload(rpc.data);
					}else{
						gutil.Log.e("再読込:"+rpc.code+"<br>"+rpc.code);
					}
				});
				/*var data = musi.parasite("#initdata");
				if(data == null){
					data = {};
				}else{
					data = musi.fromJson(data.text());
				}
				reload(data);*/
			}
		},function(){
			gutil.ajax(JUMP_URL.server_info_save, createData(), function(rpc,code){
				if(rpc == null){
					gutil.Log.e("ネットワークエラー:"+code);
				}else if(rpc.code == gutil.Rpc.CODE_SUCCESS){
					gutil.Log.i("保存成功");
				}else{
					gutil.Log.e("保存:"+rpc.code+"<br>"+rpc.code);
				}
			});
		}]);
		box.appendChild(line);
	};
	
	
	/**
	 * 初期化
	 */
	musi.addLimitEvent(window, "load", function(){
		var data = musi.parasite("#initdata");
		if(data == null){
			data = {};
		}else{
			data = musi.fromJson(data.text());
		}
		reload(data);
	},1);
});


