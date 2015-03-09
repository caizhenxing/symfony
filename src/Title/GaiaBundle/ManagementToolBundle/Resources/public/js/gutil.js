var gutil = {};

gutil.BTN_ADD = 0;
gutil.BTN_DEL = 1;

gutil.DATA_STR = 0;
gutil.DATA_DATE = 1;
gutil.DATA_TIME = 2;

/**
 * インプットグループ型のラインを作成
 * @param {boolean} is_half 半分のサイズにするかどうか
 * @returns {musi.Parasite} div型のオブジェクト
 */
gutil.makeLine = function(is_half){
	var ret = new musi.Parasite("div");
	ret.addClass("input-group");
	if(is_half)
		ret.addClass("half-width");
	return ret;
};
/**
 * インプットグループ型のラベルを作成
 * @param {string} text ラベル文字列
 * @return {musi.Parasite} span型のオブジェクト
 */
gutil.makeLabel = function(text){
	text = text==null?"":text;
	var label = new musi.Parasite("span");
	label.addClass("input-group-addon");
	label.text(text);
	return label;
};
/**
 * アイコンを作成する
 * @param {string} icon e602など
 * @param {boolean} is_obj true: musi.Parasiteのspan型を作成 false: htmlテキスト型で作成
 * @returns {object|string}
 */
gutil.makeIcon = function(icon,is_obj){
	if(!is_obj)
		return "<span class='icon'>&#x"+icon+";</span>";
	var ret = new musi.Parasite("span");
	ret.addClass("icon");
	ret.html("&#x"+icon+";");
	return ret;
};
/**
 * インプットグループ型の入力領域を作成
 * @param {Number} data データ型 gutil.DATA_XXX
 * @param {any} value 入力領域に入れるデータ
 * @return {musi.Parasite} input型のオブジェクト
 */
gutil.makeInput = function(data, value){
	var ret = new musi.Parasite("input");
	ret.attr("type","text");
	switch(data){
	case gutil.DATA_DATE:
		ret.addClass("form-control");
		ret.attr("maxlength","10");
		$(ret._).datepicker({});
		break;
	case gutil.DATA_TIME:
		ret.addClass("form-control");
		ret.attr("maxlength","8");
		$(ret._).timepicker({});
		break;
	case gutil.DATA_STR:
	default:
		ret.addClass("form-control");
		break;
	}
	ret.value(value);
	return ret;
};
/**
 * 2ボタンラインを作成
 * @param {Array} icon [{string}左,{string}右]形式のアイコンリスト e602など
 * @param {Array} label [{string}左,{string}右]形式のラベルリスト
 * @param {Array} hdl [{function}左,{function}右]形式のハンドラリスト
 * @returns {musi.Parasite} div型のオブジェクト
 */
gutil.make2BtnLine = function(icon,label,hdl){
	var ret = new musi.Parasite("div");
	ret.addClass("submit");
	var div = new musi.Parasite("div");
	div.addClass("back");
	div.style.cursor ="pointer";
	var btn = new musi.Parasite("a");
	btn.addEvent("click",hdl[0]);
	btn.html(gutil.makeIcon(icon[0],false)+label[0]);
	div.appendChild(btn);
	ret.appendChild(div);
	
	div = new musi.Parasite("div");
	div.addClass("back");
	btn = new musi.Parasite("button");
	btn.attr("type","button");
	btn.addEvent("click",hdl[1]);
	btn.html(gutil.makeIcon(icon[1],false)+label[1]);
	div.appendChild(btn);
	ret.appendChild(div);
	return ret;
};
/**
 * ボタン作成
 * @param {Number} type ボタンタイプ gutil.BTN_XXX
 * @param {function} hdl クリック時のハンドラ
 * @returns {musi.Parasite} input型のオブジェクト
 */
gutil.makeButton = function(type,hdl){
	var ret = new musi.Parasite("input");
	ret.attr("type","button");
	switch(type){
	case gutil.BTN_ADD:
		ret.addClass("arpg_add_btn");
		break;
	case gutil.BTN_DEL:
		ret.addClass("arpg_del_btn");
		break;
	}
	ret.addEvent("click",hdl);
	ret.style.width = "20px";
	ret.style.height = "20px";
	ret.style.border = "0px solid black";
	return ret;
};
(new function(){
	var LOG_ERROR = 0;
	var LOG_WARNING = 1;
	var LOG_INFO = 2;
	var mVlist = [];
	var mView = null;
	var mWindow = null;
	var mIsOpen = false;
	var mAutoOpen = -1;
	var repos = function(){
		var top = 0;
		for(var i=0,len=mVlist.length;i<len;++i){
			var v = mVlist[i];
			if(v == null) continue;
			v.style.top = top+"px";
			top += v.outerHeight()+1;
		}
		if(top == 0){
			mWindow.style.display = "none";
			mAutoOpen = -1;
			mIsOpen = false;
			mWindow.style.height = null;
		}else{
			mWindow.style.display = "block";
		}
	};
	var wheight = function(){
		var top = 0;
		for(var i=0,len=mVlist.length;i<len;++i){
			var v = mVlist[i];
			if(v == null) continue;
			top += v.outerHeight()+1;
		}
		return top+5;
	};
	var makePlate = function(type,text){
		var cls = "";
		var ft = "";
		switch(type){
		case LOG_ERROR:
			cls = "error";
			ft = "e617";
			break;
		case LOG_WARNING:
			cls = "warning";
			ft = "e604";
			break;
		case LOG_INFO:
			cls = "info";
			ft = "e60e";
			break;
		default:
			return null;
		}
		var box = new musi.Parasite("div");
		box.addClass("gutil-log-plate "+cls);
		var txt = new musi.Parasite("div");
		txt.addClass("gutil-log-plate-text");
		box.appendChild(txt);
		txt.html(text);
		var icon = new musi.Parasite("div");
		icon.addClass("gutil-log-plate-icon");
		box.appendChild(icon);
		box.style.height = icon.style.height = txt.outerHeight()+"px";
		var img = new musi.Parasite("div");
		img.addClass("gutil-log-plate-icon-img");
		icon.appendChild(img);
		img.html("&#x"+ft+";");
		icon.addEvent("click",function(){
			for(var i=0,len=mVlist.length;i<len;++i){
				var v = mVlist[i];
				if(v != box) continue;
				mVlist.splice(i, 1);
				mView.releaseChild(box);
				break;
			}
			repos();
			risizer();
		});
		return box;
	};
	var risizer = null;
	gutil.__initLog = function(){
		delete(gutil.__initLog);
		var top_bar = musi.parasite(".navbar-fixed-top");
		var box = new musi.Parasite("div");
		musi.getRootParasite().appendChild(box);
		box.addClass("gutil-log-window");
		box.style.top = top_bar.outerHeight()+"px";
		mWindow = box;
		
		var scroller = new musi.Parasite("div");
		box.appendChild(scroller);
		scroller.addClass("gutil-log-content");
		mView = scroller;
		
		var under = new musi.Parasite("div");
		box.appendChild(under);
		under.addClass("gutil-log-window-under");
		var icon1 = gutil.makeIcon("e62d", true);
		icon1.style.fontSize="12px";
		icon1.style.height="12px";
		under.appendChild(icon1);
		var icon2 = gutil.makeIcon("e62c", true);
		icon2.style.fontSize="12px";
		icon2.style.height="12px";
		
		under.addEvent("click", function(){
			var open = false;
			if(mAutoOpen >= 0){
				var open = false;
			}else{
				open = !mIsOpen;
				mIsOpen = open;
			}
			if(open){
				scroller.style.overflowY = "auto";
				under.removeChild();
				under.appendChild(icon2);
				risizer();
			}else{
				scroller.style.overflowY = "hidden";
				under.removeChild();
				under.appendChild(icon1);
				box.style.height = null;
				mAutoOpen = -1;
				mIsOpen = false;
			}
		});
		risizer = function(){
			if(mIsOpen || mAutoOpen>=0){
				var wh = wheight();
				var sh = (musi.env.height()-top_bar.outerHeight()-20);
				under.removeChild();
				under.appendChild(icon2);
				box.style.height = (sh>wh?wh:sh)+"px";
			}
		};
		musi.addEvent(window, "resize", risizer);

		var work = new musi.AutoWork();
		work.run = function(){
			if(mAutoOpen >= 0)
				--mAutoOpen;
			if(mAutoOpen == 0){
				scroller.style.overflowY = "hidden";
				under.removeChild();
				under.appendChild(icon1);
				box.style.height = null;
				mAutoOpen = -1;
				mIsOpen = false;
			}
			return false;
		};
		work.start();
	};
	
	var output_log = function(type,text){
		if(!mIsOpen)
			mAutoOpen = 180;
		var plate = makePlate(type,text);
		mVlist.push(plate);
		mView.appendChild(plate);
		repos();
		risizer();
		mView.attr("scrollTop", mView.attr("scrollHeight"));
	};
	
	gutil.Log = {};
	/**
	 * エラーログ出力
	 * @param {string} txt 出力ログ
	 */
	gutil.Log.e = function(txt){
		output_log(LOG_ERROR,txt);
	};
	/**
	 * ワーニングログ出力
	 * @param {string} txt 出力ログ
	 */
	gutil.Log.w = function(txt){
		output_log(LOG_WARNING,txt);
	};
	/**
	 * インフォメーションログ出力
	 * @param {string} txt 出力ログ
	 */
	gutil.Log.i = function(txt){
		output_log(LOG_INFO,txt);
	};
});
/**
 * 受信データ
 */
gutil.Rpc = function(input){
	if(input == null)
		this.code = gutil.Rpc.CODE_INVALID;
	else if(musi.isObject(input)){
		this.code = input.code;
		this.data = input.data;
	}else{
		this.code = gutil.Rpc.CODE_INVALID;
	}
};
gutil.Rpc.CODE_SUCCESS = 0;
gutil.Rpc.CODE_INVALID = -1;
gutil.Rpc.CODE_DB_ERROR = -2;
gutil.Rpc.CODE_INVALID_FORMAT = -7;
gutil.Rpc.CODE_LOGOUT = -8;

gutil.Rpc.prototype.CODE_SUCCESS = 0;
gutil.Rpc.prototype.CODE_INVALID = -1;
gutil.Rpc.prototype.CODE_DB_ERROR = -2;
gutil.Rpc.prototype.CODE_INVALID_FORMAT = -7;
gutil.Rpc.prototype.CODE_LOGOUT = -8;
gutil.Rpc.prototype.code = null;
gutil.Rpc.prototype.data = null;


(new function(){
	var mLoginKey = null;
	var mLoginAccount = null;
	var mLoginPass = null;
	var mLoginError = null;

	var createLoginBox = function(){
		var scr = new musi.Parasite("div");
		scr.attr("id","login");
		scr.style.width = scr.style.height = "100%";
		
		var box = new musi.Parasite("div");
		scr.appendChild(box);
		box.addClass("form-group");
		box.style.top = "50%";
		box.style.position = "absolute";
		box.style.left = "50%";
		box.style.marginLeft = "-175px";
		box.style.marginTop = "-175px";

		var cnt = new musi.Parasite("div");
		box.appendChild(cnt);
		cnt.addClass("alert alert-danger");
		cnt.style.display = "none";
		cnt.style.textAlign = "center";
		mLoginError = cnt;
		
		cnt = new musi.Parasite("div");
		box.appendChild(cnt);
		cnt.addClass("form-group-inner");
		
		var line = new musi.Parasite("div");
		cnt.appendChild(line);
		line.addClass("input-group");
		
		var elem = new musi.Parasite("span");
		line.appendChild(elem);
		elem.addClass("input-group-addon");
		elem.html(gutil.makeIcon("e601",false));
		
		elem = new musi.Parasite("input");
		line.appendChild(elem);
		elem.addClass("form-control no_role_check");
		elem.attr("type","text");
		elem.attr("placeholder","ログインID");
		mLoginAccount = elem;
		
		line = new musi.Parasite("div");
		cnt.appendChild(line);
		line.addClass("input-group");
		
		elem = new musi.Parasite("span");
		line.appendChild(elem);
		elem.addClass("input-group-addon");
		elem.html(gutil.makeIcon("e61c",false));
		
		elem = new musi.Parasite("input");
		line.appendChild(elem);
		elem.addClass("form-control no_role_check");
		elem.attr("type","password");
		elem.attr("placeholder","パスワード");
		mLoginPass = elem;

		cnt = new musi.Parasite("div");
		box.appendChild(cnt);
		cnt.addClass("submit");
		
		line = new musi.Parasite("div");
		cnt.appendChild(line);
		line.addClass("button");
		
		elem = new musi.Parasite("button");
		line.appendChild(elem);
		elem.addClass("no_role_check");
		elem.html(gutil.makeIcon("e61d",false)+"LOGIN");
		elem.addEvent("click",function(){
			var lhdl = new musi.NetHandler();
			var data = {
				"id" : mLoginAccount.value(),
				"pass" : mLoginPass.value()
			};
			var count = 3;
			var recon = function(code){
				if(count > 0){
					musi.connect(JUMP_URL.dcs_hack_login, "JSON", "POST", {"data":musi.toJson(data)}, lhdl);
				}else{
					gutil.Log.e("ネットワークエラー<br>code:"+code);
					gutil.endLoading();
				}
				--count;
			};
			lhdl.onLoaded = function(in_data){
				var rpc = new gutil.Rpc(in_data);
				if(rpc.code != 0){
					mLoginError.style.display = "block";
					mLoginError.html(rpc.data);
				}else{
					// ログイン成功
					if(mACData != null){
						gutil.ajax(mACData.url, mACData.data, mACData.hdl);
					}
					musi.popMBox(mLoginKey);
					mLoginKey=null;
				}
				gutil.endLoading();
			};
			lhdl.onError = function(in_err_code){
				recon(in_err_code);
			};
			lhdl.header["AJAX"] = "1";
			lhdl.header["LOGIN"] = "1";
			gutil.startLoading();
			recon(0);
		});
		return scr;
	};
	var mACData = null;
	var startLogin = function(url,data,hdl){
		if(mLoginKey != null) return;
		mACData = {
			"url" : url,
			"data" : data,
			"hdl" : hdl
		};
		mLoginKey = musi.pushMBox(createLoginBox());
		mLoginError.style.display = "none";
		mLoginPass.value("");
	};
	/**
	 * AJAX接続
	 * @param {string} url 接続するURL
	 * @param {object} data 送信するデータ
	 * @param {function} hdl function({object} 受信データ,{number} ステータスコード)型の関数 受信データがNULLの場合、ネットワークエラーの可能性がある
	 */
	gutil.ajax = function(url,data,hdl){
		var input = {"data":musi.toJson(data)};
		var lhdl = new musi.NetHandler();
		var count = 3;
		var recon = function(code){
			if(count > 0){
				musi.connect(url, "JSON", "POST", input, lhdl);
			}else{
				hdl(null,code);
				gutil.endLoading();
			}
			--count;
		};
		lhdl.onLoaded = function(in_data){
			var rpc = new gutil.Rpc(in_data);
			if(rpc.code == gutil.Rpc.CODE_INVALID){
				gutil.Log.e("Invalid error");
			}else if(rpc.code == gutil.Rpc.CODE_LOGOUT){
				// ログインステート
				startLogin(url,data,hdl);
			}else{
				hdl(rpc,200);
			}
			gutil.endLoading();
		};
		lhdl.onError = function(in_err_code){
			recon(in_err_code);
		};
		lhdl.header["AJAX"] = "1";
		gutil.startLoading();
		recon(0);
	};
});

(new function(){
	var mLoadingCount = 0;
	var mLoadingBox = null;
	var mLoadingKey = null;
	/**
	 * ローディング画面を出す
	 */
	gutil.startLoading = function(){
		if(mLoadingCount == 0){
			var box = mLoadingBox.clone(true);
			$(box._).activity({
		        segments:12,
		        width:12,
		        space:6,
		        length:28,
		        color:'#fff',
		        speed:1.5
		    });
			mLoadingKey = musi.pushMBox(box);
		}
		++mLoadingCount;
	};
	/**
	 * ローディング画面を消す
	 */
	gutil.endLoading = function(){
		--mLoadingCount;
		if(mLoadingCount < 1){
			musi.popMBox(mLoadingKey);
			mLoadingKey = null;
			mLoadingCount = 0;
		}
	};
	(new function(){
		var box = new musi.Parasite("div");
		var css = box.style;
		css.position = "absolute";
		css.top = "50%";
		css.left = "50%";
		css.width = "50%";
		css.height = "50%";
		mLoadingBox = box;
	});
});
musi.addLimitEvent(window,"load",function(){
	musi.initMBox();
	musi.enableMBox(true);
	gutil.__initLog();
}),1;