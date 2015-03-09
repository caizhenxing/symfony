/**
 * @file core.js
 * 蟲ライブラリ
 * @version 0.0 Pupa
 * @author teteo
 */
/**
 * 基礎的な機能を集約
 * @namespace 蟲ライブラリ
 */
var musi={};
musi.__proto__ = null;
delete musi.__proto__;

/**
 * 文字列チェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} 文字列の場合true
 */
musi.isString = function(in_value){
	if(typeof(in_value) == "string"){
		return true;
	}
	return false;
};
/**
 * 数値チェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} 数値の場合true
 */
musi.isNumber = function(in_value){
	return in_value !== "" && in_value != null && !isNaN(Number(in_value));
};
/**
 * Bool値チェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} Bool値の場合true
 */
musi.isBool = function(in_value){
	if(typeof(in_value) == "boolean"){
		return true;
	}
	return false;
};
/**
 * 関数オブジェクトチェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} 関数オブジェクトの場合true
 */
musi.isFunction = function(in_value){
	return in_value instanceof Function;
};
/**
 * 配列チェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} 配列の場合true
 */
musi.isArray = function(in_value){
	return in_value instanceof Array;
};
/**
 * オブジェクトチェック
 * @param {Object} in_value チェックする値
 * @return {Boolean} オブジェクトの場合true
 */
musi.isObject = function(in_value){
	return typeof(in_value) == "object";
};
/**
 * オブジェクトで閉じた関数を生成
 * @param {Object} obj thisになるオブジェクト
 * @param {Function} func 関数
 * @returns {Function}
 */
musi.createCloseFunction = function(obj, func){
	return function(){
		var swap = obj._super;
		if(swap && swap._super) obj._super = swap._super;
		var ret = func.apply(obj, arguments);
		if(swap) obj._super = swap;
		return ret;
	};
};

/**
 * ローカル時間をUTCとして出力
 * @param {Date or Number} date Dateオブジェクトまたは、1970/1/1からのミリ秒
 * @returns {Date or Number} 入力値がDateオブジェクトの場合Dateで、ミリ秒の場合ミリ秒で返す
 */
musi.local2utc = function(date){
	var sec = false;
	if(!(date instanceof Date)){
		date = new Date();
		date.setTime(date);
		sec = true;
	}
	var ret = date.getTime() + date.getTimezoneOffset()*60*1000;

	return sec?ret:new Date(ret);
};
/**
 * UTCをローカル時間として出力
 * @param {Date or Number} date Dateオブジェクトまたは、1970/1/1からのミリ秒
 * @returns {Date or Number} 入力値がDateオブジェクトの場合Dateで、ミリ秒の場合ミリ秒で返す
 */
musi.utc2local = function(date){
	var sec = false;
	if(!(date instanceof Date)){
		date = new Date();
		date.setTime(date);
		sec = true;
	}
	var ret = date.getTime() - date.getTimezoneOffset()*60*1000;
	return sec?ret:new Date(ret);
};
/**
 * @class
 * Bookクラス<br>
 * instanceof が無効になるオブジェクト<br>
 * 継承してもいいけど意味がない<br>
 * プロトタイプチェーンを消してるので検索が速い<br>
 * for .. in構文が使えない
 */
musi.Book = function(){
	this.__proto__ = null;
	delete this.__proto__;
};
musi.Book.prototype = null;


(new function(){
	var agent = 0;// 0pc 1iphone 2ipad 3ipod 4android
	if(navigator.userAgent.indexOf("iPhone") > 0){
		agent = 1;
	}
	if(navigator.userAgent.indexOf("iPad") > 0){
		agent = 2;
	}
	if(navigator.userAgent.indexOf("iPod") > 0){
		agent = 3;
	}
	if(navigator.userAgent.indexOf("Android") > 0){
		agent = 4;
	}
	/**
	 * エージェント取得
	 * @return {Number} 0pc 1iphone 2ipad 3ipod 4android
	 */
	musi.getAgent = function(){
		return agent;
	};
	/**
	 * スマートフォン？
	 * @return {Boolean}
	 */
	musi.isSmartPhone = function(){
		return 1 <= agent && agent <= 4;
	};
	/**
	 * PC？
	 * @return {Boolean}
	 */
	musi.isPC = function(){
		return agent == 0;
	};
});

(new function(){
	var code=['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
	var IDdegitMax = code.length;
	/**
	 * @class ユニークIDを作るファクトリクラス
	 */
	musi.IDFactory = function(){};
	/** @ignore */
	musi.IDFactory.prototype.___currentID = [0];
	/** @ignore */
	musi.IDFactory.prototype.___dust = [];
	/**
	 * 新規IDを発行する
	 */
	musi.IDFactory.prototype.create = function(){
		var dust = this.___dust;
		if(dust.length > 0){
			return dust.shift();
		}

		var id = "";
		var isadd = true;
		var cur = this.___currentID;
		for(var i=0,len=cur.length;i<len;++i){
			var n = cur[i];
			if(isadd)++n;
			if(n < IDdegitMax) isadd = false;
			else{
				n=0;
				if(i==len-1){
					cur[len++] = 0;
				}
			}
			id += code[n];
			cur[i] = n;
		}
		return id;
	};
	/**
	 * IDを破棄する
	 * @param {String} id 破棄するID
	 */
	musi.IDFactory.prototype.release = function(id){
		this.___dust.push(id);
	};
	/**
	 * IDをクリアする
	 */
	musi.IDFactory.prototype.clear = function(){
		this.___currentID = [0];
		this.___dust = [];
	};

	var idfac = new musi.IDFactory();
	/**
	 * 入力オブジェクトにユニークなIDを付ける<br>
	 * DOMのidではないので注意<br>
	 * id用の要素をオブジェクトに追加するので、適切に処理を行うこと
	 * @param {Object} obj ユニークIDを付けるオブジェクト
	 * @returns {String} ユニークID すでにユニークIDがついている場合、そのIDを返す
	 */
	musi.bindID = function(obj){
		if(obj.___musi_obj_id == null){
			obj.___musi_obj_id = idfac.create();
		}
		return obj.___musi_obj_id;
	};
	musi.rebindID = function(obj){
		obj.___musi_obj_id = idfac.create();
		return obj.___musi_obj_id;
	};
});

//-----------------------------------------------------------------------------
//OOP functions
//オブジェクト指向補助関数
//-----------------------------------------------------------------------------
/**
 * クラスの継承<br>
 * this.super_constructor() を子コンストラクタ内で呼ぶこと
 * 最軽量の継承<br>
 * 親クラスのメソッドなどにアクセスしたい場合、{@link musi.extend}や{@link musi.implement}を使用すること
 */
musi.inherit = function(parentClass, childClass){
	var bufclass = new Function();
	bufclass.prototype = parentClass.prototype;
	childClass.prototype = new bufclass;
	childClass.prototype.constructor = childClass;
	parentClass.prototype.super_constructor = parentClass;
	return childClass;
};
/**
 * @ignore
 */
(new function(){
	var ___musi_implement_super_constructor = null;

	(new function(){
		var ___musi_default_release_function = function(){
			this._super && (this._super.release instanceof Function) && this._super.release();
		};
		var ____createSuperFunction = function(obj, func){
			return function(){
				var swap = obj._super;
				if(swap && swap._super) obj._super = swap._super;
				var ret = func.apply(obj, arguments);
				if(swap) obj._super = swap;
				return ret;
			};
		};
		___musi_implement_super_constructor = function(){
			var base_constructor = this.____MUSI_EXTEND_SUPER_CLASS____.constructor;
			var base_prototype=null;
			var base_instance = this;
			if(this.____MUSI_EXTEND_DEEP_COUNTER == null)
				this.____MUSI_EXTEND_DEEP_COUNTER = 0;
			else
				++this.____MUSI_EXTEND_DEEP_COUNTER;

			if(this.____MUSI_EXTEND_INIT_PROTOTYPE == null){
				base_prototype = this.____MUSI_EXTEND_INIT_PROTOTYPE = this.____MUSI_EXTEND_CHILD_CLASS____.prototype.____MUSI_EXTEND_SUPER_CLASS____;
			}else{
				base_prototype = this.____MUSI_EXTEND_INIT_PROTOTYPE = this.____MUSI_EXTEND_INIT_PROTOTYPE.____MUSI_EXTEND_SUPER_CLASS____;
			}
			if(this.____MUSI_EXTEND_INIT_FUNCTIONS == null){
				this.____MUSI_EXTEND_INIT_FUNCTIONS = new musi.Book();
			}
			while(true){
				if(base_instance._super == null){
					base_instance._super = new musi.Book();
					break;
				}
				base_instance = base_instance._super;
			}
			{
				var buf_sclass = this.____MUSI_EXTEND_SUPER_CLASS____;
				while(true){
					if(buf_sclass.____MUSI_EXTEND_SUPER_CLASS____ == null)break;

					buf_sclass.constructor = buf_sclass.____MUSI_EXTEND_SUPER_CLASS____.constructor;
					buf_sclass = buf_sclass.____MUSI_EXTEND_SUPER_CLASS____;
				}
			}
			if(this.____MUSI_EXTEND_PARENT_CLASS____.prototype.____MUSI_EXTEND_SUPER_CLASS____==null && this.____MUSI_EXTEND_PARENT_CLASS____.prototype.super_constructor!=null){
				var ob = this.super_constructor;

				this.super_constructor = this.____MUSI_EXTEND_PARENT_CLASS____.prototype.super_constructor;
				this.____MUSI_EXTEND_PARENT_CLASS____.apply(this, arguments);
				if( this.constructor == this.____MUSI_EXTEND_CHILD_CLASS____) {
					delete this.super_constructor;
				} else {
					this.super_constructor = ob;
				}
			}else{
				base_constructor.apply(this,arguments);
			}
			var exlist=null;
			{
				var dconst=this;
				for(var i=0,len=this.____MUSI_EXTEND_DEEP_COUNTER;i<len;++i){
					if(dconst.____MUSI_EXTEND_SUPER_CLASS____ == null) break;
					dconst = dconst.____MUSI_EXTEND_SUPER_CLASS____;
				}
				if(dconst && dconst.____MUSI_IMPLEMENT_SUPER_FUNCTIONS____ === true)
					exlist = true;
				else
					exlist = dconst && dconst.____MUSI_IMPLEMENT_SUPER_FUNCTIONS____ || new musi.Book();
			}
			// supermethod
			for(var index in this){
				var method = this[index];
				if(!musi.isFunction(method) || index == "super_constructor" || (exlist!==true && !exlist[index]) || index == "constructor"){
					continue;
				}
				if(base_prototype.____MUSI_EXTEND_SUPER_CLASS____!=null && base_prototype[index]!=null && base_prototype[index] == base_prototype.____MUSI_EXTEND_SUPER_CLASS____[index] &&  base_instance._super._super[index] instanceof Function){
					base_instance._super[index] = base_instance._super._super[index];
				}else if(musi.isFunction(base_prototype[index])){
					base_instance._super[index]= ____createSuperFunction(this, base_prototype[index]);
				}else{
					base_instance._super[index]= ____createSuperFunction(this, method);
				}
			}
			if(base_instance._super["release"] == null) base_instance._super["release"] = ____createSuperFunction(this, ___musi_default_release_function);
			this.____MUSI_EXTEND_INIT_FUNCTIONS =new musi.Book();
			for(var index in this){
				var method = this[index];
				if(!musi.isFunction(method) || index == "constructor"){
					continue;
				}
				this.____MUSI_EXTEND_INIT_FUNCTIONS[index] = method;
			}

			this.____MUSI_EXTEND_SUPER_CLASS____.constructor = this.____MUSI_EXTEND_PARENT_CLASS____;
			--this.____MUSI_EXTEND_DEEP_COUNTER;
			if(this.____MUSI_EXTEND_CHILD_CLASS____.prototype.____MUSI_EXTEND_SUPER_CLASS____ == base_prototype){
				delete this.____MUSI_EXTEND_INIT_PROTOTYPE;
				delete this.____MUSI_EXTEND_INIT_FUNCTIONS;
				delete this.____MUSI_EXTEND_DEEP_COUNTER;
				delete this.____MUSI_EXTEND_SUPER_CLASS____;
				delete this.____MUSI_IMPLEMENT_SUPER_FUNCTIONS____;
				delete this.____MUSI_EXTEND_CHILD_CLASS____;
				delete this.____MUSI_EXTEND_PARENT_CLASS____;
			}
		};	// childClass.prototype.super_constructor end
	});
	/**
	 * クラスの継承<br>
	 * 指定した親メソッドをthis._super.function　のように呼ぶことができる<br>
	 * <br>
	 * this.super_constructor() を子コンストラクタ内で呼ぶと、親のコンストラクタを実行し、設定していれば_superを作成する<br>
	 * 親コンストラクタの処理が存在せず、extendFunctionNameListを設定していなければ呼ぶ必要はない<br>
	 * <br>
	 * 軽量だが、指定していない親クラスのメソッドを個別に呼ぶことはできない<br>
	 * ほぼ完全なオブジェクト指向にしたい場合は、{@link musi.extend}を使用すること<br>
	 * 一切親クラスのメソッドを呼ばない場合{@link musi.inherit}を使用した方がいい
	 * @param		{Class}parentClass	親クラス
	 * @param		{Class}childClass	子クラスコンストラクタ
	 * @param 		{Array}extendFunctionNameList 継承する親クラスのメソッド名
	 * @return {Class}	メソッドなどを継承した子クラス
	 *
	 * @example
	 *
//基底クラス.
//@param {Number} x
//@param {Number} y
function Grant(x, y){
this.SAMPLE = "Grant";
var cal_count = 0;
this.get_count_string = function(){
	var ret = "disp " + cal_count + " ";
	cal_count++;
	return ret;
};

this.add_disp = function(){
	alert(this.get_count_string() + "add:" + (x + y));
};

this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (x - y));
};
}
Grant.prototype.alertSample = function(){
alert(this.SAMPLE);
}


//子クラス.
//@param {Number} x
//@param {Number} y
Parent = musi.implement(Grant,function(x, y){
this.super_constructor(x,y);
this.SAMPLE = "Parent";
var self = this;
//override
this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (y - x));
}
});

//孫クラス.
//@param {Number} x
//@param {Number} y
Child = musi.implement(Parent,function(x, y){
this.super_constructor(x,y*2);
this.SAMPLE = "Child";
var self = this;
//override
this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (x - y)*2);
}
},["sub_disp","alertSample"]);
Child.prototype.alertSample = function(){
alert(this.SAMPLE+":"+this.SAMPLE);
}

var hoge1 = new Grant(7, 3);
var hoge2 = new Parent(7, 3);
var hoge3 = new Child(7, 3);

hoge1.add_disp();    // disp 0 add:10
hoge1.sub_disp();    // disp 1 sub:4
hoge1.alertSample(); // Grant
hoge2.add_disp();    // disp 0 add:10  $ this method is Grant::add_disp
hoge2.sub_disp();    // disp 1 sub:-4
hoge2.alertSample(); // Parent         $ this method is Grant::alertSample
hoge3.add_disp();    // disp 0 add:13  $ this method is Grant::add_disp
hoge3.sub_disp();    // disp 1 sub:8
hoge3.alertSample(); // Child:Child

//hoge3._super._super.sub_disp(); // この様なことはできない
hoge3._super.sub_disp();        // disp 2 sub:-1  $ this method is Parent::add_disp
hoge3._super.alertSample();     // Child;         $ this method is Grant::add_disp
	 */
	musi.implement = function(parentClass, childClass, extendFunctionNameList){
		var bufclass = new Function();
		bufclass.prototype = parentClass.prototype;
		childClass.prototype = new bufclass;
		childClass.prototype.constructor = childClass;
		if(parentClass.prototype.super_constructor != ___musi_implement_super_constructor)
			childClass.prototype.super_constructor = ___musi_implement_super_constructor;
		var extendlist = null;
		if(extendFunctionNameList === true){
			extendlist = true;
		}else{
			extendlist = new musi.Book();
			extendlist.release = true;
			if(extendFunctionNameList instanceof Array){
				for(var i=0,len=extendFunctionNameList.length;i<len;++i){
					extendlist[extendFunctionNameList[i]] = true;
				}
			}
		}
		childClass.prototype.____MUSI_IMPLEMENT_SUPER_FUNCTIONS____ = extendlist;
		childClass.prototype.____MUSI_EXTEND_SUPER_CLASS____ = parentClass.prototype;
		childClass.prototype.____MUSI_EXTEND_SUPER_CLASS____.constructor = parentClass;
		childClass.prototype.____MUSI_EXTEND_CHILD_CLASS____ = childClass;
		childClass.prototype.____MUSI_EXTEND_PARENT_CLASS____ = parentClass;
		return childClass;
	};
});
/**
 * クラスの継承<br>
 * this._superで親クラスメソッドにアクセス可<br>
 * this.super_constructor()を子コンストラクタ内で呼ぶこと<br>
 * 基本的にどんなクラスでも継承できるけど、重いので注意<br>
 * 親クラスメソッドを個別に呼び出さない場合、{@link musi.implement}の方が軽量
 * @param		{Class}parentClass	親クラス
 * @param		{Class}childClass	子クラスコンストラクタ
 * @return {Class}	メソッドなどを継承した子クラス
 * @example
 *
//基底クラス.
//@param {Number} x
//@param {Number} y
function Grant(x, y){
this.SAMPLE = "Grant";
var cal_count = 0;
this.get_count_string = function(){
	var ret = "disp " + cal_count + " ";
	cal_count++;
	return ret;
};

this.add_disp = function(){
	alert(this.get_count_string() + "add:" + (x + y));
};

this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (x - y));
};
}
Grant.prototype.alertSample = function(){
alert(this.SAMPLE);
}


//子クラス.
//@param {Number} x
//@param {Number} y
Parent = musi.extend(Grant,function(x, y){
this.super_constructor(x,y);
this.SAMPLE = "Parent";
var self = this;
//override
this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (y - x));
}
});

//孫クラス.
//@param {Number} x
//@param {Number} y
Child = musi.extend(Parent,function(x, y){
this.super_constructor(x,y*2);
this.SAMPLE = "Child";
var self = this;
//override
this.sub_disp = function(){
	alert(this.get_count_string() + "sub:" + (x - y)*2);
}
});
Child.prototype.alertSample = function(){
alert(this.SAMPLE+":"+this.SAMPLE);
}

var hoge1 = new Grant(7, 3);
var hoge2 = new Parent(7, 3);
var hoge3 = new Child(7, 3);

hoge1.add_disp();    // disp 0 add:10
hoge1.sub_disp();    // disp 1 sub:4
hoge1.alertSample(); // Grant
hoge2.add_disp();    // disp 0 add:10  $ this method is Grant::add_disp
hoge2.sub_disp();    // disp 1 sub:-4
hoge2.alertSample(); // Parent         $ this method is Grant::alertSample
hoge3.add_disp();    // disp 0 add:13  $ this method is Grant::add_disp
hoge3.sub_disp();    // disp 1 sub:8
hoge3.alertSample(); // Child:Child

hoge3._super._super.sub_disp(); // disp 2 sub:1   $ this method is Grant::sub_disp
hoge3._super.sub_disp();        // disp 3 sub:-1  $ this method is Parent::add_disp
hoge3._super.alertSample();     // Child;         $ this method is Grant::add_disp
 */
musi.extend = function(parentClass,childClass){
	return musi.implement(parentClass,childClass, true);
};

/***
 * クラスのシングルトン化.
 * 入力したクラスに　instance() メソッドを追加し、シングルトンパターンとして使用できる。
 * @param {Class} in_class		シングルトン化するクラス
 * @return {StaticClass} シングルトン化されたクラスを返す
 * @example
testClass = musi.singleton(
function(){
	var count = 0;
	this.disp = function(){
		alert("シングルトンサンプル " + count);
		count++;
	};
}
);
//このやり方でもOK
//function testClass(){
//var count = 0;
//this.disp = function(){
//	alert("シングルトンサンプル " + count);
//	count++;
//};
//}
//testClass = musi.singleton(testClass);

function testFunction(){
testClass.instance().disp();
}

testFunction(); // シングルトンサンプル 0
testFunction(); // シングルトンサンプル 1
testFunction(); // シングルトンサンプル 2
var ts = new testClass(); // シングルトンエラー
 */
musi.singleton = function(in_class){
	var ret_class = function(){
		in_class.apply(this, arguments);
		if(arguments.callee.caller != arguments.callee.instance)
			throw "error:musi.singleton  singleton object cannot use newoperator.";
	};

	ret_class.entity = null;
	ret_class.instance = function(){
		if (ret_class.entity == null) {
			ret_class.entity = new ret_class;
		}
		return ret_class.entity;
	};
	return ret_class;
};






/* ********************************************** */
/* ************* Class Constructors ************* */
/* ********************************************** */
/**
 * @class 寄生Elementクラス<br>
 * 通常、DOMオブジェクトは、継承などができないため、DOMに寄生する形で継承を行えるようにしたクラス<br>
 * ホストとなっているDOMElementは、obj._に格納されている
 * @param {HTMLElement or String} in_host 寄生先、エレメントの場合そのまま寄生、文字列の場合、そのタグ名前のエレメントを生成して寄生
 * @example
 * // 枠付き用のDIVの完成
 * Div = musi.extend(musi.Parasite, function(){
 * 	this.super_constructor("div");
 * 	this.css("border", "1px #00ff00 solid");	// スタイルシートの変更
 * });
 * var v = new Div();
 * musi.getParasiteByID("test").appendChild(v);	// Parasite要素に追加
 * var tag = v._.tagName;	// 寄生先のDOM要素に直接アクセス tag = "div"
 */
musi.Parasite = function(in_host){
	this.___init(in_host);
	delete this.___init;
};
/* ********************************************** */
/* *********** Class Constructors END *********** */
/* ********************************************** */






/***********************************************/
/**
 * @class Json化インタフェース
 */
musi.JsonXchanger = function(){};
/**
 * Jsonへ符号化する
 * @return {String} Json文字列
 */
musi.JsonXchanger.prototype.encodeJson = function(){
	throw "error:musi.JsonXchanger.encodeJson is abstruct method.";
};
/**
 * Jsonから復元する
 * @param {Object} in_json jsonオブジェクト
 * @return {Boolean} 復元の成否
 */
musi.JsonXchanger.prototype.decodeJson = function(in_json){
	throw "error:musi.JsonXchanger.decodeJson is abstruct method.";
	return false;
};

/**
 * @function
 * @description
 * Jsonからオブジェクトに変換する
 * @param {String} in_json_str JSON文字列
 * @returns {Object} オブジェクト
 */
musi.fromJson = function(in_json_str){
	return eval("("+in_json_str+")");
};
/**
 * @function
 * @description
 * オブジェクトからJsonに変換する
 * @param {Object} in_obj 変換するオブジェクト
 * @returns {String} Json文字列
 */
musi.toJson = null;
musi.toJson = JSON.stringify || function(in_obj){
	if(in_obj === undefined) return "undefined";
	if(in_obj === null) return "null";
	if(in_obj instanceof musi.JsonXchanger){
		return in_obj.encodeJson();
	}
	if(in_obj instanceof Array){
		var ret = "[";
		var nf = false;
		for(var i in in_obj){
			if(nf){
				ret+=",";
			}
			if(!musi.isFunction(in_obj[i])){
				ret+=musi.toJson(in_obj[i]);
				nf = true;
			}
		}
		return ret+"]";
	}
	if(musi.isObject(in_obj)){
		var ret = "{";
		var nf = false;
		for(var i in in_obj){
			if(nf){
				ret+=",";
			}
			if(!musi.isFunction(in_obj[i])){
				ret+='"'+i+'":'+musi.toJson(in_obj[i]);
				nf = true;
			}
		}
		return ret+"}";
	}

	if(typeof(in_obj) == "string"){
		return '"'+in_obj.replace(/\"/g,'\\"')+'"';
	}
	return in_obj+"";
};
/**
 * オブジェクトにJsonオブジェクトの値を入れる<br>
 * なんでも上書きしちゃう
 * @param {Object or Array} out_obj 代入されるオブジェクト
 * @param {String} in_json Object型または、Array型のJson文字列
 */
musi.decorateJson = function(out_obj, in_json){
	var json = musi.fromJson(in_json);
	for(var i in json){
		out_obj[i] = json[i];
	}
	if(out_obj instanceof Array && json instanceof Array){
		out_obj.length = json.length;
	}
};

/**
 * Press用キーコード<br>
 * @constant
 * @example
 * var f2 = musi.KEY.F2; // ファンクションキー2のキーコード
 * var t2 = musi.KEY.T2; // テンキー2のキーコード
 */
musi.PRESSKEY = {
		"BS":8,"BackSpace":8,"\b":8,"TAB":9,"Tab":9,"\t":9,"ENTER":13,"Enter":13,"SPACE":32,"Space":32," ":32,
		"0":48,"1":49,"2":50,"3":51,"4":52,"5":53,"6":54,"7":55,"8":56,"9":57,
		"a":97,"b":98,"c":99,"d":100,"e":101,"f":102,"g":103,"h":104,"i":105,"j":106,"k":107,"l":108,"m":109,"n":110,"o":111,"p":112,"q":113,"r":114,"s":115,"t":116,"u":117,"v":118,"w":119,"x":120,"y":121,"z":122,
		"A":65,"B":66,"C":67,"D":68,"E":69,"F":70,"G":71,"H":72,"I":73,"J":74,"K":75,"L":76,"M":77,"N":78,"O":79,"P":80,"Q":81,"R":82,"S":83,"T":84,"U":85,"V":86,"W":87,"X":88,"Y":89,"Z":90,
		"!":33,'"':34,"#":35,"$":36,"%":37,"&":38,"'":39,"(":40,")":41,"-":45,"=":61,"^":94,"~":126,"\\":92,"|":124,
		"@":64,"`":96,"[":91,"{":123,";":59,"+":43,":":58,"*":42,"]":93,"}":125,",":44,"<":60,".":44,">":62,"/":47,"?":63,"_":95
};
/**
 * 英字キーコード<br>
 * keydown keyup 時用
 * @constant
 * @example
 * var f2 = musi.KEY.F2; // ファンクションキー2のキーコード
 * var t2 = musi.KEY.T2; // テンキー2のキーコード
 */
musi.KEY = {
		"BS":8,"BackSpace":8,"\b":8,"TAB":9,"Tab":9,"\t":9,"NT5":12,"nt5":12,"ENTER":13,"Enter":13,"SHIFT":16,"Shift":16,"CTRL":17,"Ctrl":17,"ALT":18,"Alt":18,"PAUSE":19,"Pause":19,"ESC":27,"Esc":27,"変換":28,"無変換":29,"SPACE":32,"Space":32," ":32,"PAGEUP":33,"PageUp":33,"PGUP":33,"PgUp":33,"PAGEDOWN":34,"PageDown":34,"PGDN":34,"PgDn":34,"END":35,"End":35,"DELETE":46,"Delete":46,"HOME":36,"Home":36,"LEFT":37,"Left":37,"UP":38,"Up":38,"RIGHT":39,"Right":39,"DOWN":40,"Down":40,"INSERT":45,"Insert":45,
		"0":48,"1":49,"2":50,"3":51,"4":52,"5":53,"6":54,"7":55,"8":56,"9":57,
		"a":65,"b":66,"c":67,"d":68,"e":69,"f":70,"g":71,"h":72,"i":73,"j":74,"k":75,"l":76,"m":77,"n":78,"o":79,"p":80,"q":81,"r":82,"s":83,"t":84,"u":85,"v":86,"w":87,"x":88,"y":89,"z":90,
		"A":65,"B":66,"C":67,"D":68,"E":69,"F":70,"G":71,"H":72,"I":73,"J":74,"K":75,"L":76,"M":77,"N":78,"O":79,"P":80,"Q":81,"R":82,"S":83,"T":84,"U":85,"V":86,"W":87,"X":88,"Y":89,"Z":90,
		"T0":96,"T1":97,"T2":98,"T3":99,"T4":100,"T5":101,"T6":102,"T7":103,"T8":104,"T9":105,
		"t0":96,"t1":97,"t2":98,"t3":99,"t4":100,"t5":101,"t6":102,"t7":103,"t8":104,"t9":105,
		"T*":106,"T+":107,"T-":109,"T.":110,"T/":111,
		"t*":106,"t+":107,"t-":109,"t.":110,"t/":111,
		"F1":112,"F2":113,"F3":114,"F4":115,"F5":116,"F6":117,"F7":118,"F8":119,"F9":120,"F10":121,"F11":122,"F12":123,
		"f1":112,"f2":113,"f3":114,"f4":115,"f5":116,"f6":117,"f7":118,"f8":119,"f9":120,"f10":121,"f11":122,"f12":123,
		"!":49,'"':50,"#":51,"$":52,"%":53,"&":54,"'":55,"(":56,")":57,
		"*":186,":":186,"+":187,";":187,"<":188,",":188,"-":189,"=":189,">":190,".":190,"?":191,"/":191,"`":192,"@":192,"[":219,"{":219,"|":220,"\\":220,"}":221,"]":221,"~":222,"^":222,"_":226,"＼":226
};
/**
 * 日本語キーコード<br>
 * keydown keyup 時用
 * @constant
 * @example
 * var f2 = musi.JKEY.F2; // ファンクションキー2のキーコード
 * var t2 = musi.JKEY.HanZen; // 全角半角のキーコード
 */
musi.JKEY = {
		"BS":8,"BackSpace":8,"\b":8,"TAB":9,"Tab":9,"\t":9,"NT5":12,"nt5":12,"ENTER":13,"Enter":13,"SHIFT":16,"Shift":16,"CTRL":17,"Ctrl":17,"ALT":18,"Alt":18,"PAUSE":19,"Pause":19,"ESC":27,"Esc":27,"変換":28,"無変換":29,"SPACE":32,"Space":32," ":32,"PAGEUP":33,"PageUp":33,"PGUP":33,"PgUp":33,"PAGEDOWN":34,"PageDown":34,"PGDN":34,"PgDn":34,"END":35,"End":35,"DELETE":46,"Delete":46,"HOME":36,"Home":36,"LEFT":37,"Left":37,"UP":38,"Up":38,"RIGHT":39,"Right":39,"DOWN":40,"Down":40,"INSERT":45,"Insert":45,
		"!":49,'"':50,"#":51,"$":52,"%":53,"&":54,"'":55,"(":56,")":57,
		"0":48,"1":49,"2":50,"3":51,"4":52,"5":53,"6":54,"7":55,"8":56,"9":57,
		"*":59,":":59,
		"a":65,"b":66,"c":67,"d":68,"e":69,"f":70,"g":71,"h":72,"i":73,"j":74,"k":75,"l":76,"m":77,"n":78,"o":79,"p":80,"q":81,"r":82,"s":83,"t":84,"u":85,"v":86,"w":87,"x":88,"y":89,"z":90,
		"A":65,"B":66,"C":67,"D":68,"E":69,"F":70,"G":71,"H":72,"I":73,"J":74,"K":75,"L":76,"M":77,"N":78,"O":79,"P":80,"Q":81,"R":82,"S":83,"T":84,"U":85,"V":86,"W":87,"X":88,"Y":89,"Z":90,
		"WINDOWS":92,"Windows":92,
		"T0":96,"T1":97,"T2":98,"T3":99,"T4":100,"T5":101,"T6":102,"T7":103,"T8":104,"T9":105,
		"t0":96,"t1":97,"t2":98,"t3":99,"t4":100,"t5":101,"t6":102,"t7":103,"t8":104,"t9":105,
		"T*":106,"T+":107,"T-":109,"T.":110,"T/":111,
		"t*":106,"t+":107,"t-":109,"t.":110,"t/":111,
		"F1":112,"F2":113,"F3":114,"F4":115,"F5":116,"F6":117,"F7":118,"F8":119,"F9":120,"F10":121,"F11":122,"F12":123,
		"f1":112,"f2":113,"f3":114,"f4":115,"f5":116,"f6":117,"f7":118,"f8":119,"f9":120,"f10":121,"f11":122,"f12":123,
		"NUMLOCK":144,"NumLock":144,"SCROLLLOCK":145,"ScrollLock":145,
		"*":186,":":186,"+":187,";":187,"<":188,",":188,"-":189,"=":189,">":190,".":190,"?":191,"/":191,"`":192,"@":192,"[":219,"{":219,"|":220,"\\":220,"}":221,"]":221,"~":222,"^":222,"_":226,"＼":226,"CAPS":240,"Caps":240,"英数":240,"KANA":242,"KANA":242,"KANJI":243,"HanZen":243
};

/**
 * 汎用イベントオブジェクトに変換<br>
 * 忘れがちな座標系<br>
 * offsetX,offsetY(layerX,layerY): cssのleft,topの起点となるところを原点とした座標系　通常は親からの相対位置が入る<br>
 * pageX,pageY: ページ表示の左上を原点とした座標系、position:abusolute;の時の原点<br>
 * clientX,clientY: 表示上の左上を原点とした座標系、position:fixed;の時の原点<br>
 * screenX,screenY: ブラウザやページなどが関係しない、PCスクリーン上の左上が原点
 * @param {Object} in_evt イベントオブジェクト
 * @return {Object} 汎用イベントオブジェクト
 * @example
 * obj.onclick = function(evt){
 * 	evt = musi.convert2ExEvent(evt);
 * 	alert(evt.layerX+", "+evt.offsetY);
 * 	// イベントオブジェクトに存在するほぼすべての要素にアクセスできます
 * 	evt.targetParasite.innerHeight(); // イベントの発生原をパラサイトとして収納
 * 	evt.stop(); ///< stopPropagation そのままでも使えるけど省略形を登録
 * 	evt.cancel(); ///< preventDefault そのままでも使えるけど省略形を登録
 * 	var p = evt.pos();
 * 	alert(p.x+", "+p.y);	// イベントの対象からの位置
 * 	p = evt.pos(musi.getParasiteByID("pupa"));
 * 	alert(p.x+", "+p.y);	// IDがpupaのオブジェクトから見た位置 ただし IDがpupaのオブジェクトが、evt.targetを内包していること
 * };
 */
musi.convert2ExEvent = function(in_evt){
	if(in_evt.musi == true) return in_evt;
	in_evt.musi = true;
	try{
		if(musi.isNumber(in_evt.layerX)){
			in_evt["offsetX"] = in_evt.layerX;
			in_evt["offsetY"] = in_evt.layerY;
		}else{
			in_evt["layerX"] = in_evt.offsetX;
			in_evt["layerY"] = in_evt.offsetY;
		}
	}catch(err){}
	try{
		if(in_evt.target == null){
			in_evt.target = in_evt.srcElement;
		}
		if(in_evt.srcElement == null){
			in_evt.srcElement = in_evt.target;
		}
		in_evt.targetParasite = musi.convertParasite(in_evt.target);
	}catch(err){};
	try{
		if(in_evt.detail == null){
			in_evt.detail = in_evt.wheelDelta;
		}else{
			in_evt.wheelDelta = in_evt.detail;
		}
	}catch(err){}
	if(!musi.isFunction(in_evt.stopPropagation)){
		in_evt.stopPropagation = function(){this.cancelBubble = true;};
	}
	if(!musi.isFunction(in_evt.preventDefault)){
		in_evt.preventDefault = function(evt){this.returnValue  = false;};
	}
	in_evt.stop = in_evt.stopPropagation;
	in_evt.cancel = in_evt.preventDefault;
	in_evt.pos = musi.___get_pos_from_target;
	return in_evt;
};

/**
 * @class イベント管理用のパッケージ
 */
musi.EventIndex = function(){
};
/** @ignore */
musi.EventIndex.prototype.obj = null;
/** @ignore */
musi.EventIndex.prototype.type = null;
/** @ignore */
musi.EventIndex.prototype.hdl = null;
/** @ignore */
musi.EventIndex.prototype.next = null;


(new function(){
	var ____replaceEvent = {};
	/**
	 * イベントを別のイベントに置き換えるルールを登録する
	 * @param {String} from 置換前 onを抜いた文字列
	 * @param {String} to 置換後 onを抜いた文字列
	 * @return {Boolean} true:登録成功 false:fromがすでに登録されている
	 */
	musi.setReplaceEvent = function(from, to){
		if(____replaceEvent[from] != null) return false;
		____replaceEvent[from] = to;
		return true;
	};
	/**
	 * イベントを別のイベントに置き換えるルールを登録から外す
	 * @param {String} from 登録時のfromに入れた値
	 */
	musi.resetReplaceEvent = function(from){
		delete ____replaceEvent[from];
	};
	/**
	 * 指定要素の子要素の属性に指定されているイベントを置き換えルールで変換する<br>
	 * 重いので、毎フレーム使ったりしないこと
	 * @param {Element or musi.Parasite} parent 置き換えを行う要素 指定しない場合bodyになる
	 */
	musi.replaceEvent = function(parent){
		if(parent == null) parent = musi.getRootParasite();
		parent = musi.convertParasite(parent);
		var list = musi.getElementsByFunc(function(){return true;},parent);
		for(var i=0,len=list.length;i<len;++i){
			var obj = list[i];
			for(var j in ____replaceEvent){
				if(obj["on"+j] != null){
					obj["on"+____replaceEvent[j]] = obj["on"+j];
					delete obj["on"+j];
				}
			}
		}
	};

	var ____addEvent = (function(){
		if(window.addEventListener){
			return function(o,t,h){
				if(____replaceEvent[t] != null) t = ____replaceEvent[t];
				if(o instanceof musi.Parasite)
					o._.addEventListener(t,h,false);
				else
					o.addEventListener(t,h,false);
			};
		}else{
			return function(o,t,h){
				if(____replaceEvent[t] != null) t = ____replaceEvent[t];
				if(o instanceof musi.Parasite)
					o._.attachEvent("on"+t,h);
				else
					o.attachEvent("on"+t,h);

			};
		}
	})();
	/**
	 * イベントを追加する<br>
	 * イベントハンドラには、convert2ExEventによって変換されたものが渡されます。<br>
	 * 呼び出されたハンドル内でのthisは、in_objなので注意<br>
	 * @param {Object or musi.Parasite}in_obj イベントを追加するオブジェクト
	 * @param {String}in_type イベントタイプ  "click"といったような頭のonを抜く カンマ区切りで複数同時に指定できる
	 * @param {Function}in_handle イベントハンドラ
	 * @param {Object} in_arg イベントと一緒に渡す引数 handle(event, arg) の形式で渡される 設定しなくてもいい
	 * @returns {musi.EventIndex} イベントインデックス
	 */
	musi.addEvent = function(in_obj, in_type, in_handle, in_arg){
		in_type = in_type.replace(/\s/g, "");
		in_type = in_type.split(",");
		if(in_type.length > 1){
			var next = null;
			for(var i=0,len=in_type.length;i<len;++i){
				if(in_type[i] == "") continue;
				var p = musi.addEvent(in_obj, in_type[i], in_handle, in_arg);
				p.next = next;
				next = p;
			}
			return next;
		}else{
			in_type = in_type[0];
		}
		var f = function(evt){
			return in_handle.call(in_obj, musi.convert2ExEvent(evt), in_arg);
		};
		____addEvent(in_obj, in_type, f);
		var ret= new musi.EventIndex();
		ret.obj = in_obj;
		ret.type = in_type;
		ret.hdl = f;
		return ret;
	};

	/**
	 * 回数制限イベントを追加する<br>
	 * イベントハンドラには、convert2ExEventによって変換されたものが渡されます。
	 * @param {Object}in_obj イベントを追加するオブジェクト
	 * @param {String}in_type イベントタイプ  "click"といったような頭のonを抜く
	 * @param {Function}in_handle イベントハンドラ
	 * @param {Number} in_limit イベントを行う回数
	 * @param {Object} in_arg イベントと一緒に渡す引数 handle(event, arg) の形式で渡される 設定しなくてもいい
	 * @return {musi.EventIndex} イベントインデックス
	 */
	musi.addLimitEvent = function(in_obj, in_type, in_handle, in_limit, in_arg){
		if(in_limit < 1) return;

		var pack= new musi.EventIndex();
		var f = function(evt){
			var ret= in_handle.call(in_obj, musi.convert2ExEvent(evt), in_arg);
			--in_limit;
			if(in_limit < 1) musi.delEvent(pack);
			return ret;
		};
		pack.obj = in_obj;
		pack.type = in_type;
		pack.hdl = f;
		____addEvent(in_obj,in_type, f);
		return pack;
	};
});
(new function(){
	var ____delEvent = (function(){
		if(window.removeEventListener){
			return function(p){
				if(p.obj instanceof musi.Parasite)
					p.obj._.removeEventListener(p.type, p.hdl ,false);
				else
					p.obj.removeEventListener(p.type, p.hdl ,false);
				if(p.next != null)
					____delEvent(p.next);
			};
		}else{
			return function(p){
				if(p.obj instanceof musi.Parasite)
					p.obj._.detachEvent("on"+p.type,p.hdl);
				else
					p.obj.detachEvent("on"+p.type,p.hdl);
				if(p.next != null)
					____delEvent(p.next);
			};
		}
	})();
	/**
	 * イベントを削除する
	 * @param {musi.EventIndex} in_pack イベントインデックス
	 */
	musi.delEvent = function(in_pack){
		if(!in_pack) return;
		if(in_pack instanceof musi.EventIndex){
			____delEvent(in_pack);
		}
	};
});
/**
 * ファイルのドラッグアンドドロップイベントを追加する
 * @param {Element or musi.Parasite} in_obj ドラッグアンドドロップを追加するオブジェクト
 * @param {String["url" or "binary" or "text"]} in_type ロードするタイプを指定指定が間違っている場合、urlが選択される　
 * @param {Function} in_handle イベントハンドラ ドラッグアンドドロップ時に{String:mime,FileReader:reader}の配列を引数として呼び出す
 * @throws イベントハンドラが関数オブジェクトでない場合
 */
musi.addDragAndDrop = function(in_obj,in_type,in_handle){
	if(!musi.isFunction(in_handle)){
		throw "error:musi.addDragAndDrop in_handle is invalid value.";
	}
	musi.addEvent(in_obj,"dragover",function(evt){
		evt.preventDefault();
	});
	musi.addEvent(in_obj,"drop",function(evt){
		evt.preventDefault();
		var readers = new Array();
		for(var i=0,len=evt.dataTransfer.files.length;i<len;i++){
			var file = evt.dataTransfer.files[i];
			if(file == null)
				continue;
			var add_item = {};
			add_item.reader = new FileReader();

			add_item.file = file;
			switch(in_type.toUpperCase()){
				case "BINARY":
					add_item.reader.readAsBinaryString(file);
					break;
				case "TEXT":
					add_item.reader.readAsText(file);
					break;
				case "URL":
				default:
					add_item.reader.readAsDataURL(file);
				break;
			}
			readers.push(add_item);
		}
		in_handle(readers);
	});
};

(new function(){
	var mValues=new musi.Book();
	/**
	 * 定数アクセス 可変長引数[1,2]<br>
	 * @function
	 * @param {String}in_key キー
	 * @param {Object}in_value 設定する内容
	 * @return {Object} 定数値
	 * @throws 重複キーによる登録、未登録キーによる取得
	 * @example
	 * // 定数の設定
	 * musi.define("pupa", "MyNameIsPupa");
	 *
	 * // 定数の取得
	 * var v = musi.define("pupa"); // v = "MyNameIsPupa"
	 *
	 * // musi.define("pupa", "ERROR");	// 重複キーによるエラー
	 * // musi.define("ERROR");			// 未登録キーアクセスによるエラー
	 */
	musi.define = function(in_key, in_value){
		switch(arguments.length){
			case 1:
				if(mValues[arguments[0]] == undefined)
					throw "error:musi.define dont find "+arguments[0]+"'s value.";
				return mValues[arguments[0]];
				break;
			case 2:
				if(mValues[arguments[0]] != undefined)
					throw "error:musi.define already exist "+arguments[0]+"'s value.";
				mValues[arguments[0]] = arguments[1];
				return mValues[arguments[0]];
				break;
			default:
				throw "error:musi.define no match arguments.";
			break;
		}
	};
});


(new function(){
	var mFiles=new musi.Book();			///< インクルード済みファイル一覧
	var mMusiDir="";		///< core.jsファイルがあるディレクトリの相対パス
	var script_tags = document.getElementsByTagName("script");
	for (var index in script_tags) {
		try {
			if (script_tags[index].src.indexOf("musi/core.js", 0) < 0) {
				continue;
			}
			var full_path = script_tags[index].src;
			mMusiDir = full_path.replace("core.js", "");
			break;
		}catch (e) {
			continue;
		}
	}
	/**
	 * 外部Javascriptファイルを展開する
	 * @param {String} in_filename インクルードするファイル名
	 */
	musi.include = function(in_filename){
		if(mFiles[in_filename] != undefined){
			// 既に読み込み済み
			return;
		}
		try {
			var text = musi.connect(in_filename, "TXT", "GET", null, null);
			window.eval(text);
			mFiles[in_filename] = true;
		}catch(e){
			if (arguments.length < 2 || arguments[1]) {
				in_filename = mMusiDir + in_filename;
				musi.include(in_filename,false);
			}else{
				throw "error:musi.include dont include "+in_filename+".";
			}
		}
	};
});

/* *************************************** */
/* Primiteve Class */
(new function(){
	/**
	 * @augments musi.JsonXchanger
	 * @class  2次元ベクトル 可変長引数[0,1,2]
	 * @param {Number or musi.Vec2} arg1
	 * @param {Number} arg2
	 * @example
	 * // 0で初期化
	 * var v0 = new musi.Vec2();
	 * v0.x = 12;
	 *
	 * // musi.Vec2で初期化
	 * var v1 = new musi.Vec2(v0);
	 *
	 * // 2数値で初期化
	 * var v2 = new musi.Vec2(1.23, 4.5);
	 */
	musi.Vec2 = musi.inherit(musi.JsonXchanger, function(arg1,arg2){
		if(arguments.length == 1){
			if(arg1 instanceof musi.Vec2){
				this.x = arg1.x();
				this.y = arg1.y();
			}else{
				throw "error:musi.Vec2 arg1 is invalid value.";
			}
		}else if(arguments.length == 2){
			if(!musi.isNumber(arg1)){
				throw "error:musi.Vec2 arg1 is invalid value.";
			}
			if(!musi.isNumber(arg2)){
				throw "error:musi.Vec2 arg2 is invalid value.";
			}
			this.x = arg1;
			this.y = arg2;
		}
	});
	/**
	 * @type Number
	 */
	musi.Vec2.prototype.x = 0;
	/**
	 * @type Number
	 */
	musi.Vec2.prototype.y = 0;
	/**
	 * クローン生成
	 * @return {musi.Vec2} 複製したオブジェクト
	 */
	musi.Vec2.prototype.clone = function(){
		return new musi.Vec2(this.x,this.y);
	};
	/**
	 * 長さ取得
	 * @return {Number} 長さ
	 */
	musi.Vec2.prototype.length = function(){
		return Math.sqrt(this.length2());
	};
	/**
	 * 長さの二乗を取得
	 * @returns {Number} 長さの二乗
	 */
	musi.Vec2.prototype.length2 = function(){
		return this.x*this.x+this.y*this.y;
	};

	/**
	 * ゼロ初期化
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.zero = function(){
		this.x = 0;
		this.y = 0;
		return this;
	};
	/**
	 * 設定
	 * @param {number} in_x
	 * @param {number} in_y
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.set = function(in_x, in_y){
		this.x= in_x;
		this.y= in_y;
		return this;
	};
	/**
	 * 加算
	 * @param {Number} value 全パラメータに加算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.add = function(value){
		this.x+=value;
		this.y+=value;
		return this;
	};
	/**
	 * 加算
	 * @param {musi.Vec2} vec 各要素で加算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.addv = function(vec){
		this.x+=vec.x;
		this.y+=vec.y;
		return this;
	};
	/**
	 * 減算
	 * @param {Number} value 全パラメータを減算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.sub = function(value){
		this.x-=value;
		this.y-=value;
		return this;
	};
	/**
	 * 減算
	 * @param {musi.Vec2} vec 各要素で減算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.subv = function(vec){
		this.x-=vec.x;
		this.y-=vec.y;
		return this;
	};
	/**
	 * 乗算
	 * @param {Number} value 全パラメータを乗算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.mul = function(value){
		this.x*=value;
		this.y*=value;
		return this;
	};
	/**
	 * 乗算
	 * @param {musi.Vec2} vec 各要素で乗算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.mulv = function(vec){
		this.x*=vec.x;
		this.y*=vec.y;
		return this;
	};
	/**
	 * 除算<br>
	 * 0でもホイホイ除算しちゃう
	 * @param {Number} value 全パラメータを除算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.div = function(value){
		this.x/=value;
		this.y/=value;
		return this;
	};
	/**
	 * 除算<br>
	 * 0でもホイホイ除算しちゃう
	 * @param {musi.Vec2} vec 各要素で除算
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.divv = function(vec){
		this.x/=vec.x;
		this.y/=vec.y;
		return this;
	};
	/**
	 * 内積
	 * @param {musi.Vec2} in_vec 内積するベクター
	 * @return {Number} 内積値
	 */
	musi.Vec2.prototype.dot = function(in_v){
		return this.x*in_v.x+this.y*in_v.y;
	};
	/**
	 * 単位化
	 * @return {musi.Vec2} 自己参照
	 */
	musi.Vec2.prototype.normalize = function(){
		var len = this.length();
		if(len > 0.00001){
			this.div(len);
		}
		return this;
	};
	/**
	 * Jsonへ符号化する
	 * @return {String} Json文字列
	 */
	musi.Vec2.prototype.encodeJson = function(){
		return '{"x":'+this.x+', "y":'+this.y+'}';
	};
	/**
	 * Jsonから復元する
	 * @param {Object} in_json jsonオブジェクト
	 * @return {Boolean} 復元の成否
	 */
	musi.Vec2.prototype.decodeJson = function(in_json){
		this.x= in_json["x"];
		this.y= in_json["y"];
		return musi.isNumber(this.x) && musi.isNumber(this.y);
	};

	/*******************************************/
	/**
	 * @augments musi.Vec2
	 * @class  3次元ベクトル 可変長引数[0,1,3]
	 * @param {musi.Vec3 or Number}	arg1
	 * @param {Number}	arg2
	 * @param {Number}	arg3
	 * @example
	 * // 0で初期化
	 * var v0 = new musi.Vec3();
	 * v0.z = 12;
	 *
	 * // musi.Vec3で初期化
	 * var v1 = new musi.Vec3(v0);
	 *
	 * // 3数値で初期化
	 * var v3 = new musi.Vec3(1.23, 4.5, 6.0);
	 */
	musi.Vec3 = musi.inherit(musi.Vec2, function(arg1, arg2, arg3){
		this.zero();
		if(arguments.length == 1 && (arg1 instanceof musi.Vec3)){
			this.set(arg1.x,arg1.y,arg1.z);
		}else if(arguments.length == 3){
			this.set(arg1, arg2, arg3);
		}
		throw "error:musi.Vec3 is invalid arg.";
	});
	/** @type Number */
	musi.Vec3.prototype.z = 0;

	/**
	 * クローン生成
	 * @return {musi.Vec3} 複製したオブジェクト
	 */
	musi.Vec3.prototype.clone = function(){
		return new musi.Vec3(this.x,this.y,this.z);
	};
	/**
	 * 長さ取得
	 * @return {Number} 長さ
	 */
	musi.Vec3.prototype.length = function(){
		return Math.sqrt(this.length2());
	};
	/**
	 * 長さの二乗取得
	 * @return {Number} 長さの二乗
	 */
	musi.Vec3.prototype.length2 = function(){
		return this.x*this.x+this.y*this.y+this.z*this.z;
	};

	/**
	 * ゼロ初期化
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.zero = function(){
		this.x = 0;
		this.y = 0;
		this.z = 0;
		return this;
	};
	/**
	 * 設定
	 * @param {number} in_x
	 * @param {number} in_y
	 * @param {number} in_z
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.set = function(in_x, in_y, in_z){
		this.x = in_x;
		this.y = in_y;
		this.z = in_z;
		return this;
	};
	/**
	 * 加算
	 * @param {Number} value 全パラメータに加算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.add = function(value){
		this.x+=value;
		this.y+=value;
		this.z+=value;
		return this;
	};
	/**
	 * 加算
	 * @param {musi.Vec3} vec 各要素で加算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.addv = function(vec){
		this.x+=vec.x;
		this.y+=vec.y;
		this.z+=vec.z;
		return this;
	};
	/**
	 * 減算
	 * @param {Number} value 全パラメータを減算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.sub = function(value){
		this.x-=value;
		this.y-=value;
		this.z-=value;
		return this;
	};
	/**
	 * 減算
	 * @param {musi.Vec3} vec 各要素で減算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.subv = function(vec){
		this.x-=vec.x;
		this.y-=vec.y;
		this.z-=vec.z;
		return this;
	};
	/**
	 * 乗算
	 * @param {Number} value 全パラメータを乗算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.mul = function(value){
		this.x*=value;
		this.y*=value;
		this.z*=value;
		return this;
	};
	/**
	 * 乗算
	 * @param {musi.Vec3} vec 各要素で乗算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.mulv = function(vec){
		this.x*=vec.x;
		this.y*=vec.y;
		this.z*=vec.z;
		return this;
	};
	/**
	 * 除算<br>
	 * 0でもホイホイ除算しちゃう
	 * @param {Number} value 全パラメータを除算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.div = function(value){
		this.x/=value;
		this.y/=value;
		this.z/=value;
		return this;
	};
	/**
	 * 除算<br>
	 * 0でもホイホイ除算しちゃう
	 * @param {musi.Vec3} vec 各要素で除算
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.divv = function(vec){
		this.x/=vec.x;
		this.y/=vec.y;
		this.z/=vec.z;
		return this;
	};
	/**
	 * 外積
	 * @param {musi.Vec3} in_vec 外積するベクター
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.cross = function(in_v){
		var px = this.x;
		var py = this.y;
		var pz = this.z;
		this.x = py*in_v.z-pz*in_v.y;
		this.x = pz*in_v.x-px*in_v.z;
		this.x = px*in_v.y-py*in_v.x;
		return this;
	};
	/**
	 * 内積
	 * @param {musi.Vec3} in_vec 内積するベクター
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.dot = function(in_v){
		if(in_v instanceof musi.Vec3){
			return this.x*in_v.x+this.y*in_v.y+this.z*in_v.z;
		}
		throw "error:musi.Vec3.dot arg1 is invalid value.";
	};
	/**
	 * 単位化
	 * @return {musi.Vec3} 自己参照
	 */
	musi.Vec3.prototype.normalize = function(){
		var len = this.length();
		if(len > 0.00001){
			this.div(len);
		}
		return this;
	};
	/**
	 * Jsonへ符号化する
	 * @return {String} Json文字列
	 */
	musi.Vec3.prototype.encodeJson = function(){
		return '{"x":'+this.x+', "y":'+this.y+', "z":'+this.z+'}';
	};
	/**
	 * Jsonから復元する
	 * @param {Object} in_json jsonオブジェクト
	 * @return {Boolean} 復元の成否
	 */
	musi.Vec3.prototype.decodeJson = function(in_json){
		this.x=in_json["x"];
		this.y=in_json["y"];
		this.z=in_json["z"];
		return musi.isNumber(this.x) && musi.isNumber(this.y) && musi.isNumber(this.z);
	};


	/*******************************************/
	/**
	 * @augments musi.JsonXchanger
	 * @class  マトリクス基底
	 */
	musi.MatBase = musi.inherit(musi.JsonXchanger, function(){});
	/**
	 * マトリクス初期化
	 */
	musi.MatBase.prototype.identity = function(){
		throw "error:musi.MatBase.identity is abstruct method.";
	};
	/**
	 * @brief  コンテキストにこのマトリクスをセットする
	 * @param {RendarContext} in_context
	 */
	musi.MatBase.prototype.set2Context=function(in_context){
		throw "error:musi.MatBase.set2Context is abstruct method.";
	};
	/**
	 * @brief  クローンメソッド
	 */
	musi.MatBase.prototype.clone = function(){
		throw "error:musi.MatBase.clone is abstruct method.";
	};
	/*******************************************/
	/**
	 * @augments musi.MatBase
	 * @class  2D変換用マトリクス<br>
	 * 単位マトリクスで初期化
	 */
	musi.Mat2D = musi.inherit(musi.MatBase,function(){
		this.zero();
		this.identity();
	});

	/**
	 * 指定箇所の数値を取得
	 * @param {Integer} row 行番号 0～2
	 * @param {Integer} col 列番号 0～1
	 * @param {Number} value 設定値
	 * @return {Number} row,colの場所にある数値
	 * @example
	 * var v = new musi.Mat2D();
	 *
	 * // 設定
	 * v.at(0,1,0.5);
	 *
	 * // 取得
	 * var f = v.at(0,1); // f = 0.5
	 */
	musi.Mat2D.prototype.at = function(row,col,value){
		var p = row*3+col;
		if(arguments.length == 2)
			switch(p){
				case 0:
					return this._e0;
				case 1:
					return this._e1;
				case 2:
					return this._e2;
				case 3:
					return this._e3;
				case 4:
					return this._e4;
				case 5:
					return this._e5;
				default:
					break;
			}
		if(arguments.length == 3){
			switch(p){
				case 0:
					return this._e0 = value;
				case 1:
					return this._e1 = value;
				case 2:
					return this._e2 = value;
				case 3:
					return this._e3 = value;
				case 4:
					return this._e4 = value;
				case 5:
					return this._e5 = value;
				default:
					break;
			}
		}
		throw "error:musi.Mat2D.at size of args is invalid.";
		return 0;
	};

	/**
	 * コンテキストにこのマトリクスをセットする
	 * @param {RendarContecolt} in_context
	 */
	musi.Mat2D.prototype.set2Context=function(in_context){
		if(!musi.isObject(in_context)){
			throw "error:musi.Mat2D.set2Context arg1 is invalid value.";
		}
		in_context.setTransform(this._e0,this._e3,this._e1,this._e4,this._e2,this._e5);
	};
	/**
	 * マトリクス文字列を取得
	 * @return {String} CSSとかに使う文字列
	 */
	musi.Mat2D.prototype.toString = function(){
		return "matrix("+this._e0+","+this._e3+","+this._e1+","+this._e4+","+this._e2+","+this._e5+")";
	};
	/**
	 * クローンメソッド
	 * @return {musi.Mat2D} 複製したオブジェクト
	 */
	musi.Mat2D.prototype.clone = function(){
		var ret = new musi.Mat2D();
		ret._e0 = this._e0;
		ret._e1 += this._e1;
		ret._e2 += this._e2;
		ret._e3 += this._e3;
		ret._e4 += this._e4;
		ret._e5 += this._e5;
		return ret;
	};
	/**
	 * ゼロマトリクス化
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.zero = function(){
		this._e0 = 0;
		this._e1 = 0;
		this._e2 = 0;
		this._e3 = 0;
		this._e4 = 0;
		this._e5 = 0;
		return this;
	};
	/**
	 * マトリクス初期化
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.identity = function(){
		this.zero();
		this._e0 = 1;
		this._e4 = 1;
		return this;
	};
	/**
	 * マトリクス掛け合わせ
	 * @param {musi.Mat2D} in_mat 右側から掛け合わせるマトリクス
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.mul = function(in_mat){
		if(!(in_mat instanceof musi.Mat2D)){
			throw "error musi.Mat2D.mul art1 is invalid value.";
		}
		var l00=this._e0,l01=this._e1,l02=this._e2;
		var l10=this._e3,l11=this._e4,l12=this._e5;
		var r00=in_mat._e0,r01=in_mat._e1,r02=in_mat._e2;
		var r10=in_mat._e3,r11=in_mat._e4,r12=in_mat._e5;
		this._e0=l00*r00+l01*r10;
		this._e1=l00*r01+l01*r11;
		this._e2=l00*r02+l01*r12+l02;
		this._e3=l10*r00+l11*r10;
		this._e4=l10*r01+l11*r11;
		this._e5=l10*r02+l11*r12+l12;

		return this;
	};
	/**
	 * 平行移動
	 * @param {Number} x 移動X値
	 * @param {Number} y 移動Y値
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.translate = function(x, y){
		this._e2= this._e0*x + this._e1*y+this._e2;
		this._e5= this._e3*x + this._e4*y+this._e5;
		return this;
	};
	/**
	 * 平行移動
	 * @param {musi.Vec2} vec 移動量
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.translatev = function(vec){
		this._e2= this._e0*vec.x + this._e1*vec.y+this._e2;
		this._e5= this._e3*vec.x + this._e4*vec.y+this._e5;
		return this;
	};
	/**
	 * スケール
	 * @param {Number} x スケールX値
	 * @param {Number} y スケールY値
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.scale = function(x, y){
		this._e0=this._e0*x;	this._e1=this._e1*y;
		this._e3=this._e3*x;	this._e4=this._e4*y;
		return this;
	};
	/**
	 * スケール
	 * @param {musi.Vec2} vec スケール値ベクトル
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.scalev = function(vec){
		this._e0=this._e0*vec.x;	this._e1=this._e1*vec.y;
		this._e3=this._e3*vec.x;	this._e4=this._e4*vec.y;
		return this;
	};
	/**
	 * 回転
	 * @param {Number} in_rad 角度
	 * @return {musi.Mat2D} 自己参照
	 */
	musi.Mat2D.prototype.rotate = function(in_rad){
		var c = Math.cos(in_rad),s = Math.sin(in_rad);
		var r00 = +c,r01 = -s;
		var r10 = +s,r11 = +c;
		var l00 = this._e0,l01 = this._e1;
		var l10 = this._e3,l11 = this._e4;
		this._e0=l00*r00+l01*r10;
		this._e1=l00*r01+l01*r11;
		this._e3=l10*r00+l11*r10;
		this._e4=l10*r01+l11*r11;
		return this;
	};

	/**
	 * Jsonへ符号化する
	 * @return {String} Json文字列
	 */
	musi.Mat2D.prototype.encodeJson = function(){
		var ret="[";
		ret += this._e0+",";
		ret += this._e1+",";
		ret += this._e2+",";
		ret += this._e3+",";
		ret += this._e4+",";
		ret += this._e5;
		ret += "]";
		return ret;
	};
	/**
	 * Jsonから復元する
	 * @param {Object} in_json jsonオブジェクト
	 * @return {Boolean} 復元の成否
	 */
	musi.Mat2D.prototype.decodeJson = function(in_json){
		this._e0 = in_json[0];
		this._e1 = in_json[1];
		this._e2 = in_json[2];
		this._e3 = in_json[3];
		this._e4 = in_json[4];
		this._e5 = in_json[5];
		return true;
	};



	/*******************************************/
	/**
	 * @augments musi.JsonXchanger
	 * @class  色オブジェクト 可変長引数[0,1,3,4]
	 * @param {Integer or Integer or musi.Color} arg1 赤　0～255
	 * @param {Integer or Integer} arg2 緑　0～255
	 * @param {Integer or Integer} arg3 青　0～255
	 * @param {Number} arg4 透明　0～1.0
	 *
	 */
	musi.Color = musi.inherit(musi.JsonXchanger, function(arg1,arg2,arg3,arg4){
		// コンストラクタ
		switch(arguments.length){
			case 1:
				if(arg1 instanceof musi.Color){
					this._r = arg1.r();
					this._g = arg1.g();
					this._b = arg1.b();
					this._a = arg1.a();
					break;
				}
			case 3:
				this.rgba(arg1,arg2,arg3,1);
				break;
			case 4:
				this.rgba(arg1,arg2,arg3,arg4);
				break;
			default:
				this.rgba(0,0,0,1);
			break;
		}
	});

	/**
	 * 赤色アクセス 可変長引数[0,1]<br>
	 * 引数0の場合取得、引数1の場合設定
	 * @param {Number} 赤色設定値
	 * @return {Number or musi.Color} 赤色値 or 自己参照
	 */
	musi.Color.prototype.r = function(in_red){
		if(arguments.length == 0){
			return this._r;
		}

		var r = Math.floor(in_red);
		if(r < 0)
			r = 0;
		if(r > 255)
			r = 255;
		this._r = r;
		return this;
	};
	/**
	 * 緑色アクセス 可変長引数[0,1]<br>
	 * 引数0の場合取得、引数1の場合設定
	 * @param {Number} 緑色設定値
	 * @return {Number or musi.Color} 緑色値 or 自己参照
	 */
	musi.Color.prototype.g = function(in_green){
		if(arguments.length == 0){
			return this._g;
		}
		var g = Math.floor(in_green);
		if(g < 0)
			g = 0;
		if(g > 255)
			g = 255;
		this._g = g;
		return this;
	};
	/**
	 * 青色アクセス 可変長引数[0,1]<br>
	 * 引数0の場合取得、引数1の場合設定
	 * @param {Number} 青色設定値
	 * @return {Number or musi.Color} 青色値 or 自己参照
	 */
	musi.Color.prototype.b = function(in_blue){
		if(arguments.length == 0){
			return this._b;
		}
		var b = Math.floor(in_blue);
		if(b < 0)
			b = 0;
		if(b > 255)
			b = 255;
		this._b = b;
		return this;
	};
	/**
	 * 透過アクセス 可変長引数[0,1]<br>
	 * 引数0の場合取得、引数1の場合設定
	 * @param {Number} 透過設定値
	 * @return {Number or musi.Color} 透過値 or 自己参照
	 */
	musi.Color.prototype.a = function(in_a){
		if(arguments.length == 0){
			return this._a;
		}
		a = parseFloat(in_a);
		if(a < 0.0)
			a = 0.0;
		if(a > 1.0)
			a = 1.0;
		this._a = a;
		return this;
	};
	/**
	 * 3原色アクセス 可変長引数[0,3]<br>
	 * 引数0の場合rgb文字列を取得, 引数3の場合設定してrgb文字列を取得
	 * @param {Number} in_r
	 * @param {Number} in_g
	 * @param {Number} in_b
	 * @returns {String} rgb文字列 キャンバスとかCSSとかで使える
	 */
	musi.Color.prototype.rgb = function(in_r, in_g, in_b){
		if (arguments.length == 3) {
			this.r(in_r);
			this.g(in_g);
			this.b(in_b);
		}
		return "rgb("+this._r+","+this._g+","+this._b+")";
	};
	/**
	 * 3原色+透過アクセス 可変長引数[0,4]<br>
	 * 引数0の場合rgba文字列を取得, 引数4の場合設定してrgba文字列を取得
	 * @param {Number} in_r
	 * @param {Number} in_g
	 * @param {Number} in_b
	 * @param {Number} in_a
	 * @returns {String} rgba文字列 キャンバスとかCSSとかで使える
	 */
	musi.Color.prototype.rgba = function(in_r, in_g, in_b, in_a){
		if (arguments.length == 4) {
			this.r(in_r);
			this.g(in_g);
			this.b(in_b);
			this.a(in_a);
		}
		return  "rgba("+this._r+","+this._g+","+this._b+","+this._a+")";
	};
	/**
	 * @brief  クローンメソッド
	 */
	musi.Color.prototype.clone = function(){
		return new musi.Color(this._r,this._g,this._b,this._a);
	};
	/**
	 * Jsonへ符号化する
	 * @return {String} Json文字列
	 */
	musi.Color.prototype.encodeJson = function(){
		return '{"r":'+this._r+',"g":'+this._g+',"b":'+this._b+',"a":'+this._a+'}';
	};
	/**
	 * Jsonから復元する
	 * @param {Object} in_json jsonオブジェクト
	 * @return {Boolean} 復元の成否
	 */
	musi.Color.prototype.decodeJson = function(in_json){
		if(musi.isString(in_json)){
			in_json = eval("("+in_json+")");
		}
		this._r = in_json["r"];
		this._g = in_json["g"];
		this._b = in_json["b"];
		this._a = in_json["a"];
	};
	musi.Color.prototype.toString = function(){
		return "rgb("+this._r+","+this._g+","+this._b+")";
	};

	/**
	 * rgba(255,255,255,1),rgb(255,255,255) #FFFFFFなどの文字列から値を設定する
	 * @param {String} str 色文字列
	 * @returns {Boolean} 変換成否
	 */
	musi.Color.prototype.parse = function(str){
		var ret = false;
		if(str == null) return ret;
		var m=str.match(/#[0-9A-Fa-f]{6}/);
		if(m==null || m.length == 0){
			m = str.match(/[0-9\.]+/g);
			if(m==null){
				ret = false;
			}else if(m.length == 4){
				this.r(Number(m[0]));
				this.g(Number(m[1]));
				this.b(Number(m[2]));
				this.a(Number(m[3]));
				ret = true;
			}else if(m.length == 3){
				this.r(Number(m[0]));
				this.g(Number(m[1]));
				this.b(Number(m[2]));
				this.a(1);
				ret = true;
			}
		}else{
			m = m[0];
			this.r(parseInt(m.slice(1,3),16));
			this.g(parseInt(m.slice(3,5),16));
			this.b(parseInt(m.slice(5,7),16));
			this.a(1);
			ret = true;
		}
		return ret;
	};
	/**
	 * rgba(255,255,255,1),rgb(255,255,255) #FFFFFFなどの文字列から値を設定する
	 * @param {String} str 色文字列
	 * @returns {musi.Color} 失敗した場合null
	 */
	musi.Color.parse = function(str){
		var ret = null;
		if(str == null) return ret;
		var m=str.match(/#[0-9A-Fa-f]{6}/);
		if(m==null || m.length == 0){
			m = str.match(/[0-9\.]+/g);
			if(m==null){
				ret = null;
			}else if(m.length == 4){
				ret = new musi.Color();
				ret.r(Number(m[0]));
				ret.g(Number(m[1]));
				ret.b(Number(m[2]));
				ret.a(Number(m[3]));
			}else if(m.length == 3){
				ret = new musi.Color();
				ret.r(Number(m[0]));
				ret.g(Number(m[1]));
				ret.b(Number(m[2]));
				ret.a(1);
			}
		}else{
			m = m[0];
			ret = new musi.Color();
			ret.r(parseInt(m.slice(1,3),16));
			ret.g(parseInt(m.slice(3,5),16));
			ret.b(parseInt(m.slice(5,7),16));
			ret.a(1);
		}
		return ret;
	};
});// Primitive class END

(new function(){
	var mEntity = {};

	var makeCache = function(in_key, in_url){
		if(in_url == null){
			in_url = in_key;
		}
		if(mEntity[in_key] == null){
			var img = new Image();
			img.loaded = false;
			img.onload = function(){
				img.loaded = true;
				delete img.onload;
				delete img.onerror;
			};
			img.onerror = function(){
				img.src = null;
				if(in_url.match(/data:/) == null)
					img.src = in_url+"?"+(new Date()).getTime();
				else
					img.src = in_url;
			};

			img.src = in_url;

			mEntity[in_key]=img;
		}
		return mEntity[in_key];
	};
	/*******************************************/
	/**
	 * @class イメージキャッシュ<br>
	 * イメージデータの先行読み込みを行う際に使用
	 * @static
	 */
	musi.ImageCache = {};
	/**
	 * オリジナルイメージオブジェクトを取得<br>
	 * 内容を変更するときなどに使用
	 * @param {String} in_url
	 * @returns {Image} イメージオブジェクト
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.getOriginal = function(in_url){
		if(!musi.isString(in_url)){
			throw "error:musi.ImageCache.get arg1 is invalid value.";
		}
		if(mEntity[in_url] == null){
			mEntity[in_url] = makeCache(in_url,in_url);
		}

		return mEntity[in_url];
	};
	/**
	 * イメージオブジェクトの複製を取得<br>
	 * オリジナルは使い回しできないので、基本的にこっちを使う
	 * @param {String} in_url
	 * @returns {Image} イメージオブジェクト
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.get = function(in_url){
		if(!musi.isString(in_url)){
			throw "error:musi.ImageCache.get arg1 is invalid value.";
		}
		if(mEntity[in_url] == null){
			mEntity[in_url] = makeCache(in_url,in_url);
		}
		var ret = mEntity[in_url].cloneNode(false);
		return ret;
	};

	/**
	 * 手動でキャッシュ生成
	 * @param {String} in_url キャッシュキー
	 * @param {DataURL} in_data 生成データ
	 * @return {Image} オリジナルイメージオブジェクト nullの場合生成失敗
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.createOriginal = function(in_url, in_data){
		if(mEntity[in_url] == null){
			mEntity[in_url] = makeCache(in_url,in_data);
			return mEntity[in_url];
		}
		return null;
	};
	/**
	 * 手動でキャッシュ生成<br>
	 * @param {String} in_url キャッシュキー
	 * @param {DataURL} in_data 生成データ 指定しない場合、in_urlを使用
	 * @return {Image} 複製イメージオブジェクト nullの場合生成失敗
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.create = function(in_url, in_data){
		if(mEntity[in_url] == null){
			mEntity[in_url] = makeCache(in_url,in_data);

			var ret = new Image();
			ret.src = mEntity[in_url].src;
			return ret;
		}
		return null;
	};
	/**
	 * データを取得する
	 * @param {Object} in_url
	 * @return {String} イメージデータのsrc
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.getData = function(in_url){
		if(mEntity[in_url] == null){
			return null;
		}
		return mEntity[in_url].src;
	};
	/**
	 * キャッシュ削除
	 * @param {String} in_url キャッシュキー
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.destroy = function(in_url){
		mEntity[in_url] = null;
		delete mEntity[in_url];
	};

	/**
	 * キャッシュクリア
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.clear = function(){
		mEntity = {};
	};

	/**
	 * 全キャッシュロード済みかをチェック
	 * @memberOf musi.ImageCache
	 */
	musi.ImageCache.isLoaded = function(){
		for(var i in mEntity){
			var e = mEntity[i];
			if((e instanceof Image) && !e.loaded)
				return false;
		}
		return true;
	};
});
//特殊イメージを設定
musi.ImageCache.create("musi:special:ninja","data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9sEEgY4FLJ1a6wAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAADUlEQVQI12NgYGBgAAAABQABXvMqOgAAAABJRU5ErkJggg==");
musi.ImageCache.create("musi:special:arrow","data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9sFChAOBJa4Y78AAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAAkElEQVRo3u2YQQ7EIAzEqrxsnsbT9mm9V91daA+NK/scpFgkENg2EREREXmMMcbnzvqiSxR9J4peTkXviaI3dkuBFYm2ArMSrQVmJNoL/JNACPySwAh8k0AJnEngBI4SVIGQSyjkJg75GA35Igt5lAh5mAt5nM5MXJGT7/qkzEp8kZNvJXAleeQvhIiIyKvYAe36NvNq3bndAAAAAElFTkSuQmCC");


/***************************************************************
 ******************** 通信関連    ******************************
 **************************************************************/
(new function(){
	var _keychar = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	var _keylength = _keychar.length;
	/**
	 * @class
	 * 非同期通信用イベントリスナークラス<br>
	 * {@link musi.connect}で使用する
	 */
	musi.NetHandler = function(){
		this._key = "";
		for(var i=0;i<5;++i){
			this._key += _keychar.charAt(Math.floor(Math.random()*_keylength));
		}
		this._key += (new Date()).getTime();
		this.header = {};
	};
});
musi.NetHandler.prototype.header = null;
/**
 * ローディング状態になったら呼び出される<br>
 * オーバーライドして使用
 * @param {Number} loaded ロードしたサイズ
 * @param {Number} total 合計サイズ
 */
musi.NetHandler.prototype.onLoading = function(loaded, total){};
/**
 * ロードが完了した<br>
 * オーバーライドして使用
 * @param {XML or String or Object} in_data connectで設定した取得データ形式が入ってくる
 */
musi.NetHandler.prototype.onLoaded = function(in_data){};
/**
 * エラー時<br>
 * オーバーライドして使用
 * @param {Number} in_err_code 401,404,500などのサーバーステータスコード
 */
musi.NetHandler.prototype.onError = function(in_err_code){document.body.innerHTML="Error:"+in_err_code;};
/**
 * 強制アボート時間(ms)<br>
 * nullの場合強制アボートは行わない<br>
 * 強制アボートした際は、onError(-408)が呼ばれる
 */
musi.NetHandler.prototype.abortTime = null;

musi.____createNetHandler = function(in_hdl, in_type, httpObj){
	if(musi.isNumber(in_hdl.abortTime)){
		var time = Number(in_hdl.abortTime);
		setTimeout(function(){
			if(httpObj.readyState == 4) return;
			httpObj.abort();
			if(in_hdl.onError)
				in_hdl.onError(-408);
			musi.Log.out("musi.connect: -408 Force timeout");
		},time);
	}

	return function(evt){
		switch(this.readyState){
			case 2:
				break;
			case 4:
				if((200 <= this.status && this.status < 300) || (this.status == 304)){
					if(in_hdl.onLoaded){
						switch(in_type){
							case 0://xml
								in_hdl.onLoaded(this.responseXML);
								break;
							case 1://json
								try{
									in_hdl.onLoaded(musi.fromJson(this.responseText));
								}catch(e){
									in_hdl.onLoaded(null);
								}
								break;
							case 2://txt
							default:
								in_hdl.onLoaded(this.responseText);
							break;
						};
					}
				}else if(in_hdl.onError){
					in_hdl.onError(this.status);
				}
				break;
			default:
				break;
		}
	};
};
(new function(){
	/**@ignore*/
	var NewHttpObject = null;
	if(NewHttpObject == null){
		try{
			new XMLHttpRequest();
			/**@ignore*/
			NewHttpObject = function(){
				return new XMLHttpRequest();
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.6.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.6.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.3.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.3.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	// 推奨非推奨ライン ↑推奨 ↓非推奨
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.5.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.5.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.4.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.4.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Microsoft.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Microsoft.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		/**@ignore*/
		NewHttpObject = function(){return null;};
	}
	/**
	 *  通信<br>
	 *  in_dataの内部キーでmutdtとmuthdを使用しているので、使用しないこと<br>
	 *  現在FormDataに対応中<br>
	 * @param  {String}in_url		接続URL
	 * @param  {String}in_type 取得データ形式　"XML" or "TXT" or "JSON" Jsonの場合オブジェクト化したものが返される
	 * @param  {String}in_method メソッド "GET" or "POST"
	 * @param  {Object}in_data POSTやGETの付加データ連想配列 オブジェクトの場合in_data[key]=value　FormDataの場合強制的にPOSTにされるので注意
	 * @param  {musi.NetHandler} in_handle コールバック　指定しない場合、同期読みを行う
	 * @param  {String} in_user Basic認証用ユーザー名 必要な時だけ
	 * @param  {String} in_pass Basic認証用パスワード 必要な時だけ
	 * @return {String or XMLDocument or Object or Boolean} 同期通信の場合指定形式のデータ　非同期の場合、接続合否
	 * @example
	 * // 同期通信
	 * // http://test.url.com?name=pupaと通信を行った時に取得できるテキスト
	 * var v = musi.connect("http://test.url.com", "TXT", "GET", {"name":"pupa"});
	 * alert(v);
	 * // 非同期通信
	 * // http://test.url.comにPOSTメソッドでname=pupaを送った時に取得できるXML
	 * var hdl = new musi.NetHandler();
	 * hdl.onLoaded = function(data){
	 *   alert(data);
	 * }
	 * musi.connect("http://test.url.com", "XML", "POST", {"name":"pupa"}, hdl);
	 */
	musi.connect = function(in_url, in_type, in_method, in_data, in_handle,in_user, in_pass){
		in_method = in_method.toUpperCase();
		if(in_method != "GET" && in_method != "POST"){
			throw "error:musi.connect "+in_method+" is unknown method.";
			return false;
		}
		var is_get = false;
		if(in_method != "POST"){
			is_get = true;
		}
		in_type = in_type.toUpperCase();
		if(in_type != "XML" && in_type != "TXT" && in_type != "JSON"){
			throw "error:musi.connect "+in_type+" is unknown datatype.";
			return false;
		}
		if(in_handle != null && !(in_handle instanceof musi.NetHandler)){
			throw "error:musi.connect handler is not musi.NetHandler.";
			return false;
		}
		var httpObj=NewHttpObject();
		if(httpObj == null){
			throw "error:musi.connect cant create httpObj.";
			return false;
		}
		var data = null;
		if(in_data){
			/* なんか動かない
			if(FormData != null){
				if(in_data instanceof FormData){
					data = in_data;
				}else if(!is_get){
					data = new FormData();
					for(var i in in_data){
						data.append(i,in_data[i]);
					}
				}
			}
			 */
			if(data == null){
				var nf=false;
				for(var i in in_data){
					if (!data) {
						data = "";
					}
					if(nf){
						data += "&";
					}
					data += encodeURIComponent(i)+"="+encodeURIComponent(in_data[i]);
					nf = true;
				}
			}
		}
		if(data == null){
			data = "mutdt="+encodeURIComponent((new Date()).getTime());
			if(in_handle != null)
				data += "&muthd="+encodeURIComponent(in_handle._key);
		}else{
			data += "&";
			data += "mutdt="+encodeURIComponent((new Date()).getTime());
			if(in_handle != null)
				data += "&muthd="+encodeURIComponent(in_handle._key);
		}

		switch(in_type){
			case "XML":
				in_type=0;
				break;
			case "JSON":
				in_type=1;
				break;
			case "TXT":
			default:
				in_type=2;
			break;
		};
		var url = in_url;
		var not_sync = false;
		if(in_handle){
			not_sync = true;
		}
		if(is_get && data!=null)
			url += "?" + data;
		httpObj.open(in_method,url,not_sync,in_user, in_pass);
		if (not_sync) {
			httpObj.onreadystatechange = musi.____createNetHandler(in_handle, in_type, httpObj);
			musi.addEvent(httpObj,"progress",function(evt){
				if(evt.lengthComputable)
					in_handle.onLoading(evt.loaded,evt.total);
			});
			//musi.addEvent(httpObj, "readystatechange", musi.____createNetHandler(in_handle, in_type));
		}
		if(in_handle){
			// ヘッダ追加
			for(var key in in_handle.header){
				if(musi.isString(key) && key.length > 0){
					httpObj.setRequestHeader(key , in_handle.header[key]);
				}
			}
		}
		if(is_get)
			httpObj.send(null);
		else{
			httpObj.setRequestHeader("Content-Type" , "application/x-www-form-urlencoded");
			httpObj.send(data);
		}

		if(!not_sync){
			switch(in_type){
				case 0://xml
					return httpObj.responseXML;
					break;
				case 1://json
					try{
						return musi.fromJson(httpObj.responseText);
					}catch(e){
						return null;
					}
					break;
				case 2://txt
				default:
					return httpObj.responseText;
				break;
			};
		}
		return true;
	};
});



/***************************************************************
 ******************** doc block   ******************************
 **************************************************************/
(new function(){
	var doc = document;


	(new function(){
		var win = window;
		// AutoWork
		(new function(){
			/** @ignore **/
			var timeout = (function(){
				return win.requestAnimationFrame ||
				win.webkitRequestAnimationFrame ||
				win.mozRequestAnimationFrame ||
				win.oRequestAnimationFrame ||
				win.msRequestAnimationFrame ||
				function(callback, element){
					//alert("requestAnimationFrameなんてねーよ！！");
					win.setTimeout(callback, 1000 / 60);
				};
			})();
			var autoworklist = [];
			var autowork = null;
			/** @ignore **/
			(autowork = function(){
				for(var i=0,len=autoworklist.length;i<len;++i){
					var e = autoworklist[i];
					try{
						if(e && e.run()){
							autoworklist[i] = null;
							delete e;
						}
					}catch(ex){
						autoworklist[i] = null;
						delete e;
					}
				}
				for(var i=0,len=autoworklist.length;i<len;++i){
					var e = autoworklist[i];
					if(e==null){
						autoworklist.splice(i,1);
						break;
					}
				}
				timeout(autowork);
			})();
			/**
			 * @class
			 * フレーム処理抽象クラス<br>
			 * 60FPSで処理をしようと頑張る
			 */
			musi.AutoWork = function(){
			};
			/** @ignore **/
			musi.AutoWork.prototype.__musi_autowork_is_run = false;
			/**
			 * 処理開始<br>
			 * オーバーライド禁止<br>
			 * 1度しか実行できない
			 */
			musi.AutoWork.prototype.start = function(){
				if(!this.__musi_autowork_is_run)
					autoworklist.push(this);
				this.__musi_autowork_is_run = true;
			};
			/**
			 * 1フレームの処理<br>
			 * trueを返すと次回から呼ばれなくなる<br>
			 * オーバーライドして使ってね！！
			 * @returns {Boolean} 終了フラグ
			 */
			musi.AutoWork.prototype.run = function(){return true;};
		});
		(new function(){
			var body = null;
			(new function(){
				var html = doc.getElementsByTagName("html")[0];
				/***************************************/
				/**
				 * @static
				 * @class 環境変数
				 */
				musi.env = new musi.Book();

				/**
				 * 画面の縦スクロール距離
				 * @returns {Number}
				 * @memberOf musi.env
				 */
				musi.env.scrollTop = function(){
					return win.scrollY || html.scrollTop;
				};
				/**
				 * 画面の横スクロール距離
				 * @returns {Number}
				 * @memberOf musi.env
				 */
				musi.env.scrollLeft = function(){
					return win.scrollX || html.scrollLeft;
				};
				/**
				 * 画面の横幅取得
				 * @returns {Number}
				 * @memberOf musi.env
				 */
				musi.env.width = function(){
					return win.innerWidth || doc.documentElement.clientWidth || body.clientWidth;
				};
				/**
				 * 画面の縦幅取得
				 * @returns {Number}
				 * @memberOf musi.env
				 */
				musi.env.height = function(){
					return win.innerHeight || doc.documentElement.clientHeight || body.clientHeight;
				};

				(new function(){
					/**@ignore*/
					var ___musi_css = null;
					try{
						if(document.defaultView.getComputedStyle){
							/**@ignore*/
							___musi_css = function(obj){
								return document.defaultView.getComputedStyle(obj, '');
							};
						}else{
							throw "jump";
						}
					}catch(e){
						/**@ignore*/
						___musi_css = function(obj){
							return obj.currentStyle;
						};
					}
					/**
					 * zIndex 最大値
					 * @returns {Number}
					 * @memberOf musi.env
					 */
					musi.env.maxZindex = function(){
						var list = doc.getElementsByTagName("*");
						var ret = 0;
						for(var i=0,len=list.length;i<len;++i){
							var n = Number(___musi_css(list[i]).zIndex);
							if(ret < n) ret=n;
						}
						return ret;
					};
				});
			});// html

			/**
			 * ルートパラサイト取得
			 * @returns {musi.Parasite}
			 */
			musi.getRootParasite = function(){
				if(body.parasite instanceof musi.Parasite)
					return body.parasite;
				return new musi.Parasite(body);
			};
			/**
			 * ルート要素取得
			 * @returns {Element}
			 */
			musi.getRootElement = function(){
				return body;
			};

			/** @ignore */
			musi.addLimitEvent(win, "load", function(){
				body = doc.body;
			},1);

		});// body
		/***************************************/
		(new function(){
			/**
			 * @static
			 * @class ローカルストレージターミナル<br>
			 * ローカルストレージにデータの保存を行う<br>
			 * ファイル名として使用できる記号は . _ ( ) のみ
			 */
			musi.terminal = new musi.Book();

			var self = musi.terminal;
			var mCd = "/";	///< カレントディレクトリ 最後に必ず/を付ける

			var mFileSystem = null;
			var mEnable=false;

			var reg = new RegExp("[:;\\\-\=\^\~\|\&\'\"\#\$\%\!\`\+\*\,\?]");
			var convertPath = function(path){
				if(!musi.isString(path) || reg.test(path) || !mEnable)
					return null;
				path = path.replace(/\s/g, "");
				if(path.charAt(0) != "/"){
					path = mCd+path;
				}
				var list = path.split("/");
				var out = [];
				for(var i=0,len=list.length;i<len;++i){
					if(list[i] == ".")
						continue;
					if(list[i] == ".."){
						out.pop();
						continue;
					}
					out.push(list[i]);
				}
				out = out.join("/");
				if(out == "") out = "/";
				out = "musi:"+out;
				list = out.split("/");
				var buf = list.shift();
				while(list.length > 0){
					if(buf.charAt(buf.length-1) != "/" && mFileSystem[buf] != null){
						return null;
					}
					buf += "/"+list.shift();
				}
				return out;
			};

			/**
			 * 指定ディレクトリ内のファイル一覧を取得<br>
			 * 下の構造のオブジェクト配列を返す
			 * <pre>
			 *  {
			 *     "name": {String}ファイル名,
			 *     "date": {String}更新日時,	// GMT
			 *     "d": {Boolean}ディレクトリフラグ
			 *  }
			 * </pre>
			 * @param {String} in_path 相対パスまたは絶対パス  無入力の場合カレントディレクトリ
			 * @return {Array} ファイル情報配列 nullの場合 ディレクトリじゃない
			 * @memberOf musi.terminal
			 */
			musi.terminal.ls = function(in_path){
				if(!mEnable) return null;

				if(in_path == null)
					path = convertPath(mCd);
				else
					path = convertPath(in_path);
				if(path == null){
					musi.Log.out("error: musi.terminal.ls ... invalid path.");
					return null;
				}
				if(path.charAt(path.length-1) != "/")
					path += "/";
				if(mFileSystem[path] == null){
					musi.Log.out("error: musi.terminal.ls ... "+in_path+" is not directory.");
					return null;
				}
				var info = musi.fromJson(mFileSystem[path]);
				var ret = [];
				for(var i in mFileSystem){
					if(i == path) continue;
					if(i.indexOf(path) > -1){
						var p = i.replace(path,"");
						if(p.indexOf("/") < 0){
							var bk = new musi.Book();
							bk.name = p;
							bk.date = info[p];
							bk.d = false;
							ret.push(bk);
						}else if(p.match(/\//g).length == 1 && p.charAt(p.length-1) == "/"){
							var bk = new musi.Book();
							bk.name = p.replace("/","");
							bk.date = info[bk.name];
							bk.d = true;
							ret.push(bk);
						}
					}
				}
				return ret;
			};

			var _makeDirectory = function(file, in_date){
				var d = file.substr(0,file.lastIndexOf("/"));

				if(d != "" && d+"/" != file && mFileSystem[file] == null && mFileSystem[file+"/"] == null){
					_makeDirectory(d,in_date);
				}
				if(mFileSystem[d+"/"] == null){
					var dat= {};
					dat[file.replace(d+"/","")] = in_date;
					mFileSystem[d+"/"] = musi.toJson(dat);
				}else{
					var dat= musi.fromJson(mFileSystem[d+"/"]);
					dat[file.replace(d+"/","")] = in_date;
					mFileSystem[d+"/"] = musi.toJson(dat);
				}
			};

			/**
			 * 書き込み
			 * @param {String} in_path カレントからの相対パスまたは、絶対パス
			 * @param {String} in_mode モード　w:上書き a:追記
			 * @param {String} in_text 書き込むテキスト
			 * @return {Boolean} true:書き込み成功　false:書き込み失敗
			 * @memberOf musi.terminal
			 */
			musi.terminal.write = function(in_path, in_mode, in_text){
				var path = convertPath(in_path);
				var date = (new Date()).toGMTString();
				if(path == null){
					musi.Log.out("error: musi.terminal.write ... "+in_path+" is invalid value.");
					return false;
				}
				if(path.charAt(path.length-1) == "/"){
					musi.Log.out("error: musi.terminal.write ... "+in_path+" is directory.");
					return false;
				}
				in_mode = in_mode.toLowerCase();
				if(in_mode == "w" || (in_mode == "a" && mFileSystem[path] == null)){
					_makeDirectory(path,date);
					mFileSystem[path] = in_text;
				}else if(in_mode == "a"){
					_makeDirectory(path,date);
					mFileSystem[path] += in_text;
				}else{
					musi.Log.out("error: musi.terminal.write ... "+in_mode+" is invalid mode.");
					return false;
				}
				return true;
			};
			/**
			 * 読み込み
			 * @param {String} in_path カレントからの相対パスまたは、絶対パス
			 * @returns {String} ファイルデータ nullの場合、読み込み失敗
			 * @memberOf musi.terminal
			 */
			musi.terminal.read = function(in_path){
				var path = convertPath(in_path);
				if(path == null){
					musi.Log.out("error: musi.terminal.read ... "+in_path+" is invalid value.");
					return null;
				}
				if(path.charAt(path.length-1) == "/"){
					musi.Log.out("error: musi.terminal.read ... "+in_path+" is directory.");
					return null;
				}
				return mFileSystem[path];
			};
			/**
			 * 削除する
			 * ディレクトリの場合、サブディレクトリ、ファイルも削除する
			 * @param {String} in_path カレントからの相対パスまたは、絶対パス
			 * @return {Boolean} 削除の成否
			 * @memberOf musi.terminal
			 */
			musi.terminal.rm = function(in_path){
				var path = convertPath(in_path);
				if(path == null){
					musi.Log.out("error: musi.terminal.rm ... "+in_path+" is invalid value.");
					return false;
				}
				if(mFileSystem[path] == null && mFileSystem[path+"/"] == null){
					musi.Log.out("error: musi.terminal.rm ... "+in_path+":  No such file or directory.");
					return false;
				}
				if(mFileSystem[path+"/"] != null)
					path += "/";

				if(path.charAt(path.length-1) == "/"){
					for(var i in mFileSystem){
						if(i.indexOf(path) > -1)
							mFileSystem.removeItem(i);
					}
					if(path == "musi:/"){
						mFileSystem["musi:/"] = "{}";
						return true;
					}
					path = path.substr(0,path.length-1);
				}else{
					mFileSystem.removeItem(path);
				}

				var dir = path.substr(0,path.lastIndexOf("/"))+"/";
				var dat = musi.fromJson(mFileSystem[dir]);
				delete dat[path];
				mFileSystem[dir] = musi.toJson(dat);
				return true;
			};


			/**
			 * ターミナルが有効性を判定
			 * @memberOf musi.terminal
			 * @return {Boolean}
			 */
			musi.terminal.isEnable = function(){
				return mEnable;
			};
			/**
			 * カレントディレクトリパスを取得
			 * @memberOf musi.terminal
			 * @return {String} カレントディレクトリパス
			 */
			musi.terminal.pwd = function(){
				return mCd;
			};
			/**
			 * カレントディレクトリを移動する
			 * @param {String} in_path 移動先のパス
			 * @returns {Boolean} 移動の成否
			 * @memberOf musi.terminal
			 */
			musi.terminal.cd = function(in_path){
				var path = convertPath(in_path);
				if(path == null){
					musi.Log.out("error: musi.terminal.cd ... "+in_path+" is invalid value.");
					return false;
				}
				if(path.charAt(path.length-1) != "/"){
					path += "/";
				}
				if(mFileSystem[path] == null){
					musi.Log.out("error: musi.terminal.cd ... "+in_path+":  No such directory.");
					return false;
				}
				mCd = path.replace("musi:","");
				return true;
			};

			var _cp = function(from, to){
				var list = self.ls(from);
				for(var i=0,len=list.length;i<len;++i){
					var bk = list[i];
					if(bk.d){
						_cp(from+bk.name+"/",to+bk.name+"/");
					}else{
						self.write(to+bk.name, "w", mFileSystem["musi:"+from+bk.name]);
					}
				}
			};
			/**
			 * データコピー
			 * 上書きコピー,サブディレクトリなどもコピーする
			 * @param {Object} from コピー元
			 * @param {Object} to コピー先
			 * @returns {Boolean} コピーの成否
			 * @memberOf musi.terminal
			 */
			musi.terminal.cp = function(from,to){
				var fpath = convertPath(from);
				var tpath = convertPath(to);
				if(fpath == null){
					musi.Log.out("error: musi.terminal.cp ... "+from+" is invalid value.");
					return false;
				}
				if(tpath == null){
					musi.Log.out("error: musi.terminal.cp ... "+to+" is invalid value.");
					return false;
				}
				if(mFileSystem[fpath] == null && mFileSystem[fpath+"/"] == null){
					musi.Log.out("error: musi.terminal.cp ... "+from+":  No such file or directory.");
					return false;
				}
				if(fpath.charAt(fpath.length-1) == "/" || mFileSystem[fpath+"/"] != null){
					if(fpath.charAt(fpath.length-1) != "/")
						fpath += "/";
					if(tpath.charAt(tpath.length-1) != "/")
						tpath += "/";
					fpath = fpath.replace("musi:","");
					tpath = tpath.replace("musi:","");
					_cp(fpath,tpath);
				}else{
					if(tpath.charAt(tpath.length-1) == "/" || mFileSystem[tpath+"/"] != null){
						if(tpath.charAt(tpath.length-1) != "/")
							tpath += "/";
						tpath += fpath.split("/").pop();
					}
					return self.write(tpath.replace("musi:",""), "w", mFileSystem[fpath]);
				}
				return true;
			};
			/**
			 * データ移動
			 * 上書き移動,サブディレクトリなども移動
			 * @param {Object} from 移動元
			 * @param {Object} to 移動先
			 * @returns {Boolean} 移動の成否
			 * @memberOf musi.terminal
			 */
			musi.terminal.mv = function(from,to){
				if(self.cp(from,to)){
					if(self.rm(from))
						return true;
				}
				return false;
			};

			mFileSystem = win.localStorage;
			if(mFileSystem){
				mEnable=true;
				if(mFileSystem["musi:/"] == null)
					mFileSystem["musi:/"]= "{}";
			}

		});
	});	/// win

	/**
	 * クッキー操作　可変長引数[1,3]
	 * @param {String} key データキー (半角英数字_)
	 * @param {String} value データ
	 * @param {Integer} days 保存日数
	 * @returns {String} クッキーデータ
	 * @example
	 * // クッキー設定
	 * musi.cookie("pupa", "WeArePupa!!", 10);
	 *
	 * // クッキー取得
	 * musi.cookie("pupa");
	 */
	musi.cookie = function(in_key,in_value,in_days){
		if(!musi.isString(in_key))
			throw "error:musi.Cookie arg1 is invalid value.";

		var reg_dels = new RegExp(" ","g");
		in_key = in_key.replace(reg_dels,"");

		var reg = new RegExp("[^0-9A-Za-z_]");
		if(reg.test(in_key))
			throw "error:musi.Cookie arg1 is invalid value.";

		if(arguments.length == 1){
			// 読み込み
			var data = doc.cookie.split(";");
			for(var i=0,len=data.length;i<len;i++){
				var word = data[i].split("=");
				word[0] = word[0].replace(reg_dels,"");
				if(key == word[0]){
					return unescape(word[1]);
				}
			}
			return "";
		}else if(arguments.length == 3){
			// 書き込み
			if(!musi.isString(in_value)){
				throw "error:musi.Cookie arg2 is invalid value.";
			}
			in_days = parseInt(in_days,10);
			if(!musi.isNumber(in_days)){
				throw "error:musi.Cookie arg3 is invalid value.";
			}
			var cookie = in_key+"="+escape(in_value)+";";
			if(in_days != 0){
				var date = new Date();
				date.setDate(date.getDate()+in_days);
				cookie += "expires="+date.toGMTString()+";";
			}
			doc.cookie = cookie;
			return in_value;
		}
	};


	//	-----------------------------------------------------------------------------
	//	element functions
	//	要素取得系関数
	//	-----------------------------------------------------------------------------
	/**
	 * 指定セレクタの最初の要素を取得<br>
	 * querySelectorラッパー
	 * @param {String} sel セレクタ
	 * @param {Element or Parasite} parent 親要素 指定しない場合 document
	 * @returns {Element} 最初に合致した要素
	 */
	musi.element = function(sel, parent){
		if(parent == null) parent = doc;
		if(parent instanceof musi.Parasite) parent = parent._;

		return parent.querySelector(sel);
	};

	/**
	 * 要素取得<br>
	 * querySelectorAllラッパー
	 * @param {String} sel セレクタリスト
	 * @param {Element or Parasite} parent 親要素 指定しない場合 document
	 * @returns {Array} セレクタのどれかに合致するElement配列
	 * @example
	 */
	musi.elements = function(sel, parent){
		if(parent == null) parent = doc;
		if(parent instanceof musi.Parasite) parent = parent._;

		return parent.querySelectorAll(sel);
	};

	/***
	 * IDによる要素取得.
	 * @param {String} in_index　取得する要素ID
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Element} 要素
	 */
	musi.getElementByID = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		return in_parent.querySelector("#"+in_index);
	};
	/***
	 * @function
	 * IDによる要素取得.
	 * @param {String} in_index　取得する要素ID
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Element} 要素
	 */
	musi.getElementById = musi.getElementByID;
	/***
	 * タグ名による要素取得.
	 * @param {String} in_index　取得する要素タグ名
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByTagName = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		return in_parent.querySelectorAll(in_index);
	};
	/***
	 * @function
	 * タグ名による要素取得.
	 * @param {String} in_index　取得する要素タグ名
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByTag = musi.getElementsByTagName;

	/***
	 * クラス名による要素取得
	 * @param {String} in_index　取得する要素クラス名
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByClass = function(in_index,in_parent){
		if(in_parent == null) in_parent = doc;
		if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		return in_parent.querySelectorAll("."+in_index);
	};
	/***
	 * @function
	 * クラス名による要素取得
	 * @param {String} in_index　取得する要素クラス名
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByClassName = musi.getElementsByClass;

	/***
	 * 名前による要素取得
	 * @param {String} in_index　取得する要素名
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByName = function(in_index,in_parent){
		if(in_parent == null) in_parent = doc;
		if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		return in_parent.querySelectorAll("*[name="+in_index+"]");
	};
	/***
	 * 選別関数による要素取得
	 * @param {Function} in_func 選別関数 in_func(element)の形式で呼ばれ、trueが返ると選別する
	 * @param {Element} in_parent 親クラスまたは、親クラスパラサイト 存在しない場合、全オブジェクトから検索
	 * @return	{Array} Element配列
	 */
	musi.getElementsByFunc = function(in_func,in_parent){
		if(in_parent == null) in_parent = doc;
		if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var all = in_parent.querySelectorAll("*");
		var ret = [];
		for(var i=0,len=all.length;i<len;++i){
			if(in_func(all[i]))
				ret.push(all[i]);
		}
		return ret;
	};


	/* ***************** パラサイトセレクタ ******************** */
	/**
	 * パラサイトリストを取得<br>
	 * querySelectorAllラッパー
	 * @param {Array or String} sel セレクタリスト
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合 document
	 * @returns {Array} セレクタのどれかに合致するElement配列
	 */
	musi.parasites = function(sel, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var list = in_parent.querySelectorAll(sel);
		var ret = [];
		for(var i=0,len=list.length;i<len;++i){
			var e = list[i];
			if(e.parasite instanceof musi.Parasite) ret[i] = e.parasite;
			else ret[i] = new musi.Parasite(e);
		}
		return ret;
	};
	/**
	 * パラサイト取得<br>
	 * querySelectorラッパー
	 * @param {Array or String} sel セレクタリスト
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合 document
	 * @returns {musi.Parasite} セレクタのどれかに合致するElement
	 */
	musi.parasite = function(sel, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelector(sel);
		if(ret == null) return null;
		if(ret.parasite instanceof musi.Parasite){
			return ret.parasite;
		}
		return new musi.Parasite(ret);
	};
	/**
	 * IDによるパラサイト取得
	 * @param {String} in_index
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {musi.Parasite}
	 */
	musi.getParasiteByID = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelector("#"+in_index);
		if(ret == null) return null;
		if(ret.parasite instanceof musi.Parasite){
			return ret.parasite;
		}
		return new musi.Parasite(ret);
	};
	/**
	 * @function
	 * IDによるパラサイト取得
	 * @function
	 * @param {String} in_index
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {musi.Parasite}
	 */
	musi.getParasiteById = musi.getParasiteByID;
	/**
	 * Classによるパラサイト取得
	 * @param {String} in_index クラス名
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByClass = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelectorAll("."+in_index);
		var list = [];
		for(var i=0,len=ret.length;i<len;++i){
			var e = ret[i];
			if(e == null) continue;
			if(e.parasite instanceof musi.Parasite){
				list[list.length] = e.parasite;
			}else{
				list[list.length] = new musi.Parasite(e);
			}
		}
		return list;
	};
	/**
	 * @function
	 * Classによるパラサイト取得
	 * @param {String} in_index クラス名
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByClassName = musi.getParasiteByClass;
	/**
	 * タグによるパラサイト取得
	 * @param {String} in_index タグ名
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByTagName = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelectorAll(in_index);
		var list = [];
		for(var i=0,len=ret.length;i<len;++i){
			var e = ret[i];
			if(e == null) continue;
			if(e.parasite instanceof musi.Parasite){
				list[list.length] = e.parasite;
			}else{
				list[list.length] = new musi.Parasite(e);
			}
		}
		return list;
	};
	/**
	 * @function
	 * タグによるパラサイト取得
	 * @param {String} in_index タグ名
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByTag = musi.getParasiteByTagName;
	/**
	 * 名前によるパラサイト取得
	 * @param {String} in_index 名前
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByName = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelectorAll("*[name="+in_index+"]");
		var list = [];
		for(var i=0,len=ret.length;i<len;++i){
			var e = ret[i];
			if(e == null) continue;
			if(e.parasite instanceof musi.Parasite){
				list[list.length] = e.parasite;
			}else{
				list[list.length] = new musi.Parasite(e);
			}
		}
		return list;
	};
	/**
	 * 選別関数によるパラサイト取得
	 * @param {Function} in_index 選別関数 in_index(elem:musi.Parasite)の形で呼ばれる trueを返すと選択される
	 * @param {Element or Parasite} in_parent 親要素 指定しない場合、document
	 * @returns {Array} パラサイト配列
	 */
	musi.getParasiteByFunc = function(in_index, in_parent){
		if(in_parent == null) in_parent = doc;
		else if(in_parent instanceof musi.Parasite) in_parent = in_parent._;
		var ret = in_parent.querySelectorAll("*");
		var list = [];
		for(var i=0,len=ret.length;i<len;++i){
			var e = ret[i];
			if(e == null) continue;
			if(e.parasite instanceof musi.Parasite){
				e = e.parasite;
			}else{
				e = new musi.Parasite(e);
			}
			if(in_index(e))
				list[list.length] = e;
		}
		return list;
	};
	/**
	 * 入力要素をパラサイトオブジェクトに変換する
	 * @param {Element or musi.Parasite} in_elem
	 * @return {musi.Parasite}
	 */
	musi.convertParasite = function(in_elem){
		if(in_elem instanceof musi.Parasite) return in_elem;
		if(in_elem.parasite instanceof musi.Parasite) return in_elem.parasite;
		return new musi.Parasite(in_elem);
	};


});



/**
 * @class 乱数ファクトリ<br>
 * シード値を元にXorShiftで乱数を生成する<br>
 * @param {Integer} seed ランダムシード デフォルトは0
 */
musi.Random = function(seed){
	if(seed == null)
		seed = 0;
	this.setSeed(seed);
};
/**@ignore*/
musi.Random.prototype._x=123456789;
/**@ignore*/
musi.Random.prototype._y=362459284;
/**@ignore*/
musi.Random.prototype._z=992785381;
/**@ignore*/
musi.Random.prototype._w=87330122;
/**
 * シードを設定する
 * @param {Integer} seed ランダムシード
 */
musi.Random.prototype.setSeed = function(seed){
	seed = parseInt(seed+"",10);
	this._x = musi.Random.prototype._x ^ seed;
	var shift = 8;
	this._y = musi.Random.prototype._y ^ (seed << shift | seed >> (32-shift));
	shift = 16;
	this._z = musi.Random.prototype._z ^ (seed << shift | seed >> (32-shift));
	shift = 24;
	this._w = musi.Random.prototype._w ^ (seed << shift | seed >> (32-shift));
};

/**
 * 次のランダム値を取得する
 */
musi.Random.prototype.next = function(){
	var t=0;
	t=this._x ^ (this._x << 11);
	this._x = this._y;
	this._y = this._z;
	this._z = this._w;
	this._w = (this._w^(this._w>>19))^(t^(t>>8));
	return this._w;
};
//-----------------------------------------------------------------------------
//Parasite system
//要素拡張システム
//-----------------------------------------------------------------------------
(new function(){
	var NINJA = null;	/// 隠し要素
	var doc = document;
	musi.Parasite.prototype.___init = function(in_host){
		if(musi.isString(in_host)){
			this._ = doc.createElement(in_host);
		}else if(musi.isObject(in_host)){
			if( in_host instanceof musi.Parasite)
				this._ = in_host._;
			else
				this._ = in_host;
		}else{
			throw "error:musi.Parasite.init arg1 is invalid value.";
		}
		if(this._ instanceof Element){
			if(this._.style.zoom == null)
				this._.style.zoom = "1";
			if(this._.parentNode == null && NINJA != null){
				NINJA.appendChild(this._);
			}
		}
		this._.parasite = this;
		this.style = this._.style;
		this.__musi_event_index = [];
		this.___release_hdl = [];
	};
	musi.addLimitEvent(window, "load", function(){
		NINJA = document.createElement("div");
		NINJA.style.visibility="hidden";
		NINJA.style.position="fixed";
		NINJA.style.width = "0px";
		NINJA.style.height = "0px";
		NINJA.style.overflow = "hidden";
		doc.getElementsByTagName("body")[0].appendChild(NINJA);
	},1);
});
/**
 * スタイルシートオブジェクト
 * @type Object
 */
musi.Parasite.prototype.style = {};
(new function(){
	/**@ignore*/
	var ___musi_css = null;
	try{
		if(document.defaultView.getComputedStyle){
			/**@ignore*/
			___musi_css = function(obj){
				return document.defaultView.getComputedStyle(obj, '');
			};
		}else{
			throw "jump";
		}
	}catch(e){

		/**@ignore*/
		___musi_css = function(obj){
			return obj.currentStyle;
		};
	}
	/**
	 * 計算後のCSSを取得する
	 * @param {String} in_name CSS要素名
	 * @returns {String} 指定したCSS要素の値
	 */
	musi.Parasite.prototype.curCss = function(in_name){
		var css = ___musi_css(this._);
		in_name = musi.___convert_css_string(in_name);

		if(css[in_name])
			return css[in_name];

		var subn = in_name.charAt(0).toUpperCase()+in_name.slice(1);
		if(css["webkit"+subn] != null)
			return css["webkit"+subn];
		else if(css["Moz"+subn] != null)
			return css["Moz"+subn];
		else if(css["ms"+subn] != null)
			return css["ms"+subn];
		else if(css["O"+subn] != null)
			return css["O"+subn];
		return null;
	};
	/**
	 * 親オブジェクトまたは対象オブジェクトからの位置<br>
	 * @param {Element or musi.Parasite} target 対象オブジェクト このオブジェクトを内包するオブジェクトであること 指定しない場合やnullの場合 親オブジェクトからの位置
	 * @returns {musi.Vec2} 位置オブジェクト
	 */
	musi.Parasite.prototype.pos = function(target){
		var ret = new musi.Vec2(this._.offsetLeft, this._.offsetTop);
		if(target != null){
			var obj = this._;
			var tgt = musi.convertParasite(target)._;
			if(obj.offsetLeft == null){
				return ret;
			}
			var has_fixed = false;
			if(___musi_css(obj)["position"] == "fixed") has_fixed = true;
			var scroll = new musi.Vec2();
			while((obj = obj.offsetParent) && (tgt != obj)){
				var css = ___musi_css(obj);
				var bx = Number(css["borderLeftWidth"].match(/[0-9\.]+/));
				var by = Number(css["borderTopWidth"].match(/[0-9\.]+/));
				ret.x += obj.offsetLeft-scroll.x+bx;//obj.scrollLeft;
				ret.y += obj.offsetTop-scroll.y+by;//obj.scrollTop;
				if(!has_fixed && css["position"] == "fixed") has_fixed = true;
				scroll.set(obj.scrollLeft,obj.scrollTop);
			}
			if(obj != musi.getRootElement()){
				ret.x -= scroll.x;
				ret.y -= scroll.y;
			}
			scroll.set(musi.env.scrollLeft(),musi.env.scrollTop());
			if(has_fixed){
				ret.x += scroll.x;
				ret.y += scroll.y;
			}
			return ret;
		}
		return ret;
	};

	/**
	 * ページの原点からの位置
	 * @returns {musi.Vec2} 位置オブジェクト
	 */
	musi.Parasite.prototype.gpos = function(){
		var ret = new musi.Vec2();
		var obj = this._;
		if(obj.offsetLeft == null){
			return ret;
		}
		var has_fixed = false;
		ret.x += obj.offsetLeft;
		ret.y += obj.offsetTop;
		if(___musi_css(obj)["position"] == "fixed") has_fixed = true;
		var scroll = new musi.Vec2();
		while(obj = obj.offsetParent){
			var css = ___musi_css(obj);
			var bx = Number(css["borderLeftWidth"].match(/[0-9\.]+/));
			var by = Number(css["borderTopWidth"].match(/[0-9\.]+/));
			ret.x += obj.offsetLeft-scroll.x+bx;//obj.scrollLeft;
			ret.y += obj.offsetTop-scroll.y+by;//obj.scrollTop;
			if(!has_fixed && css["position"] == "fixed") has_fixed = true;
			scroll.set(obj.scrollLeft,obj.scrollTop);
		}
		if(obj != musi.getRootElement()){
			ret.x -= scroll.x;
			ret.y -= scroll.y;
		}
		scroll.set(musi.env.scrollLeft(),musi.env.scrollTop());
		if(has_fixed){
			ret.x += scroll.x;
			ret.y += scroll.y;
		}
		return ret;
	};
	/**
	 * @ignore
	 */
	musi.___get_pos_from_target = function(target){
		if(target == null)
			return new musi.Vec2(this.offsetX,this.offsetY);
		var tgt = musi.convertParasite(target)._;
		var ret = new musi.Vec2();
		var obj = this.target;
		if(obj.offsetLeft == null){
			return ret;
		}
		var has_fixed = false;
		ret.x += obj.offsetLeft+this.offsetX;
		ret.y += obj.offsetTop+this.offsetY;
		if(___musi_css(obj)["position"] == "fixed") has_fixed = true;
		var scroll = new musi.Vec2();
		while((obj = obj.offsetParent) && (obj != tgt)){
			var css = ___musi_css(obj);
			var bx = Number(css["borderLeftWidth"].match(/[0-9\.]+/));
			var by = Number(css["borderTopWidth"].match(/[0-9\.]+/));
			ret.x += obj.offsetLeft-scroll.x+bx;//obj.scrollLeft;
			ret.y += obj.offsetTop-scroll.y+by;//obj.scrollTop;
			if(!has_fixed && css["position"] == "fixed") has_fixed = true;
			scroll.set(obj.scrollLeft,obj.scrollTop);
		}
		if(obj != musi.getRootElement()){
			ret.x -= scroll.x;
			ret.y -= scroll.y;
		}
		scroll.set(musi.env.scrollLeft(),musi.env.scrollTop());
		if(has_fixed){
			ret.x += scroll.x;
			ret.y += scroll.y;
		}
		return ret;

	};
	/**
	 * 表示上の原点からの位置<br>
	 * スクロールに影響を受けないFixed的な位置
	 * @return {musi.Vec2}
	 */
	musi.Parasite.prototype.dpos = function(){
		var ret = new musi.Vec2();
		var obj = this._;
		if(obj.offsetLeft == null){
			return ret;
		}
		var has_fixed = false;
		ret.x += obj.offsetLeft;
		ret.y += obj.offsetTop;
		if(___musi_css(obj)["position"] == "fixed") has_fixed = true;
		var scroll = new musi.Vec2();
		while(obj = obj.offsetParent){
			var css = ___musi_css(obj);
			var bx = Number(css["borderLeftWidth"].match(/[0-9\.]+/));
			var by = Number(css["borderTopWidth"].match(/[0-9\.]+/));
			ret.x += obj.offsetLeft-scroll.x+bx;//obj.scrollLeft;
			ret.y += obj.offsetTop-scroll.y+by;//obj.scrollTop;
			if(!has_fixed && css["position"] == "fixed") has_fixed = true;
			scroll.set(obj.scrollLeft,obj.scrollTop);
		}
		if(obj != musi.getRootElement()){
			ret.x -= scroll.x;
			ret.y -= scroll.y;
		}
		scroll.set(musi.env.scrollLeft(),musi.env.scrollTop());
		if(!has_fixed){
			ret.x -= scroll.x;
			ret.y -= scroll.y;
		}
		return ret;
	};
});
/**
 * 寄生先や子供ごと完全に削除する
 */
musi.Parasite.prototype.release = function(){
	var _ = this._;
	if(!_)return;
	this.releaseChild();
	var s = this;
	for(var i=0,len=s.__musi_event_index.length;i<len;++i){
		musi.delEvent(s.__musi_event_index[i]);
	}
	var list = this.___release_hdl;
	for(var i=0,len=list.length;i<len;++i){
		if(musi.isFunction(list[i]))
			list[i].call(this);
	}
	list = void 0;
	_.parentNode.removeChild(_);
	for(var i in this){
		delete this[i];
	}
};
/**
 * 親要素からこの要素を除去する
 */
musi.Parasite.prototype.remove = function(){
	var _h = this._;
	if(!_h) return;
	_h.parentNode.removeChild(_h);
};
/**
 * メソッドアクセス
 * @param {String} in_method 実行するメソッド名
 * @param {any} arg1～argN メソッド名以降の引数はメソッドに渡される
 * @return {any} メソッドの返り値
 */
musi.Parasite.prototype.func = function(in_method){
	var _ = this._;
	var arg = [];
	for(var i=1,len=arguments.length;i<len;++i) arg[arg.length]=arguments[i];
	return _[in_method].apply(_,arg);
};
/**
 * 属性アクセス 可変長引数[1,2]<br>
 * Htmlに書かれた属性値をそのまま取得する<br>
 * @parem {String} in_name 属性名
 * @parem {any} in_value 設定値
 * @returns {any} 属性値
 */
musi.Parasite.prototype.attr = function(in_name, in_value){
	var _ = this._;
	if(arguments.length > 1){
		_.setAttribute(in_name,in_value);
	}
	if(_.hasAttribute(in_name))
		return _.getAttribute(in_name);
	return null;
};
/**
 * 属性を削除する
 * @param {String} in_name 属性名
 */
musi.Parasite.prototype.delAttr = function(in_name){
	this._.removeAttribute(in_name);
};
/**
 * 寄生先等値チェック
 * @param {musi.Parasite or Element} in_host 比較対象
 * @returns {Boolean} 等値合否
 */
musi.Parasite.prototype.equal = function(in_host){
	if(in_host == null)
		return false;
	if(in_host instanceof musi.Parasite){
		return in_host._ == this._;
	}else if(in_host instanceof Element){
		return in_host == this._;
	}
	return false;
};/**
 * 子ノードを追加する
 * @param {Element or musi.Parasite} in_node 子ノード
 */
musi.Parasite.prototype.appendChild = function(in_node){
	if(!in_node){
		throw "error:musi.Parasite.appendChild in_node arg is null.";
	}
	if(in_node instanceof musi.Parasite){
		this._.appendChild(in_node._);
	}else{
		this._.appendChild(in_node);
	}
};
/**
 * 子ノードを除去する 可変長引数[0,1]
 * 引数0個　全子ノードを削除
 * 引数1個　特定オブジェクトを削除
 * @param {Element or musi.Parasite} in_node 子ノード
 * @example
 * var v = musi.getParasiteElementByID("pupa");
 * var c = new musi.Parasite("div");
 * v.appendChild(c);
 *
 * // 特定子ノードを削除
 * v.removeChild(c);
 *
 * // 全子ノードを削除
 * v.removeChild();
 */
musi.Parasite.prototype.removeChild = function(in_node){
	var host = this._;
	if(arguments.length == 0){
		for(var i=host.childNodes.length-1;i>=0;i--){
			host.removeChild(host.childNodes[i]);
		}
	}else{
		if(!in_node){
			return;
		}
		if(in_node instanceof musi.Parasite){
			host.removeChild(in_node._);
		}else{
			host.removeChild(in_node);
		}
	}
};
/**
 * 子ノードを完全に削除する 可変長引数[0,1]
 * 引数0個　全子ノードを削除
 * 引数1個　特定オブジェクトを削除
 * @param {Element or musi.Parasite} in_node 子ノード
 * @example
 * var v = musi.getParasiteElementByID("pupa");
 * var c = new musi.Parasite("div");
 * v.appendChild(c);
 *
 * // 特定子ノードを削除
 * v.removeChild(c);
 *
 * // 全子ノードを削除
 * v.removeChild();
 */
musi.Parasite.prototype.releaseChild = function(in_node){
	var host = this._;
	if(arguments.length == 0){
		var c = host.childNodes;
		for(var i=c.length-1;i>=0;i--){
			if(c[i].parasite instanceof musi.Parasite)
				c[i].parasite.release();
			else
				musi.convertParasite(c[i]).release();
		}
	}else{
		if(!in_node){
			return;
		}
		if(in_node instanceof musi.Parasite){
			in_node.release();
		}else{
			if(in_node.parasite instanceof musi.Parasite){
				in_node.parasite.release();
			}else{
				musi.convertParasite(in_node).release();
			}
		}
	}
};
/**
 * データのコピーを作成する
 * @param {Boolean} is_deep ディープコピーフラグ
 */
musi.Parasite.prototype.clone = function(is_deep){
	return musi.convertParasite(this._.cloneNode(is_deep));
};
musi.Parasite.prototype.cloneNode = musi.Parasite.prototype.clone;
/**
 * イベント追加<br>
 * イベントが発生するとin_handle(event, in_arg)を呼び出す<br>
 * 呼び出されたin_handle内でのthisは、このオブジェクトなので注意<br>
 * 入力されるeventは、そこそこ互換性を持たせている<br>
 * 入力されるeventにcancelという、呼ぶとデフォルトの動作を行わなくなる関数を実装している<br>
 * タイプにreleaseを指定すると、オブジェクトリリース時に実行される
 * @param {String} in_type		イベントタイプ "click"の様にonを抜く カンマ区切りで複数設定できる
 * @param {Function} in_handle	イベントハンドラ
 * @param {Object} in_arg		ハンドラに渡す引数
 * @return {musi.EventIndex}
 */
musi.Parasite.prototype.addEvent = function(in_type, in_hdl, in_arg){
	if(in_type == "release"){
		this.___release_hdl.push(in_hdl);
		return null;
	}
	if(in_type.indexOf("release") > -1){
		this.addEvent("release",in_hdl,in_arg);
		in_type = in_type.replace("release", "");
	}
	var p = musi.addEvent(this,in_type, in_hdl, in_arg);
	this.__musi_event_index[this.__musi_event_index.length] = p;
	return p;
};
/**
 * 回数制限イベント追加<br>
 * イベントが発生するとin_handle(event, in_arg)を呼び出す<br>
 * 入力されるeventは、そこそこ互換性を持たせている<br>
 * 入力されるeventにcancelという、呼ぶとデフォルトの動作を行わなくなる関数を実装している<br>
 * 通常のイベントと違い、イベント伝播しないので注意<br>
 *
 * @param {String} in_type		イベントタイプ "click"の様にonを抜く
 * @param {Function} in_handle	イベントハンドラ
 * @param {Number} in_limit 制限回数
 * @param {Object} in_arg		ハンドラに渡す引数
 * @return {musi.EventIndex}
 */
musi.Parasite.prototype.addLimitEvent = function(in_type, in_hdl, in_limit, in_arg){
	return musi.addLimitEvent(this._,in_type, in_hdl,in_limit, in_arg);
};
/**
 * イベントを削除する
 * @param {mEventIndex} in_index AddEventで返されたオブジェクト
 */
musi.Parasite.prototype.delEvent = function(in_index){
	musi.delEvent(in_index);
};
/**
 * HTMLアクセス
 * @param {String} in_html 設定するHTML
 * @returns {String} html文字列
 */
musi.Parasite.prototype.html = function(in_html){
	var h=this._;
	if(arguments.length > 0){
		h.innerHTML = in_html;
	}
	return h.innerHTML;
};
/**
 * Textアクセス
 * @param {String} in_text 設定する文字列
 * @returns {String} 文字列
 */
musi.Parasite.prototype.text = function(in_text){
	var h=this._;
	if(arguments.length > 0){
		h.textContent = this.innerText = in_text;
	}
	if(h.textContent)
		return h.textContent;
	else
		return h.innerText;
};
/**
 * Valueアクセス
 * @param {String} in_val 設定するvalueL
 * @returns {Object} value値
 */
musi.Parasite.prototype.value = function(in_val){
	var h=this._;
	if(arguments.length > 0){
		h.value = in_val;
	}
	return h.value;
};
/** @ignore **/
musi.___convert_css_string = function(in_name){
	var list = in_name.split("-");
	var ret = "";
	while(list.length > 0){
		var e = list.shift();
		if(e == "") continue;
		if(ret.length > 0 || e == "moz" || e == "o"){
			e = e.charAt(0).toUpperCase()+e.slice(1);
		}
		ret += e;
	}
	return ret;
};
(new function(){
	var __musi_set_css = function(c, in_name, in_value){
		in_name = musi.___convert_css_string(in_name);
		var v = in_value+"";
		c[in_name] = v;
		var subn = in_name.charAt(0).toUpperCase()+in_name.slice(1);
		if(c["webkit"+subn] != null)
			c["webkit"+subn] = v;
		else if(c["Moz"+subn] != null)
			c["Moz"+subn] = v;
		else if(c["ms"+subn] != null)
			c["ms"+subn] = v;
		else if(c["O"+subn] != null)
			c["O"+subn] = v;

	};
	/**
	 * CSSアクセス 可変長引数[1,2]<br>
	 * ベンダプレフィクスを勝手につける
	 * @param {String or Object} in_name CSS要素名 または、設定する値
	 * @param {String} in_value 設定値
	 * @returns {String} CSS要素の値 一括設定の場合null
	 * @example
	 * var v = new musi.Parasite("div");
	 * // CSSの設定
	 * v.css("width","12px");
	 * // CSSの取得
	 * var a = v.css("width"); // a = "12px"
	 * // CSS一括設定
	 * v.css({width:"12px",});
	 */
	musi.Parasite.prototype.css = function(in_name, in_value){
		var c=this.style;
		if(arguments.length > 1){
			__musi_set_css(c,in_name,in_value);
		}else if(musi.isObject(in_name)){
			for(var n in in_name){
				var v = in_name[n];
				if(musi.isString(v))
					__musi_set_css(c,n,v);
			}
			return null;
		}
		in_name = musi.___convert_css_string(in_name);
		if(c[in_name] != null)
			return c[in_name];

		var subn = in_name.charAt(0).toUpperCase()+in_name.slice(1);
		if(c["webkit"+subn] != null)
			return c["webkit"+subn];
		else if(c["Moz"+subn] != null)
			return c["Moz"+subn];
		else if(c["ms"+subn] != null)
			return c["ms"+subn];
		else if(c["O"+subn] != null)
			return c["O"+subn];

	};
});
/**
 * alpha値アクセス 可変長引数[0,1]
 * @param {Number} in_alpha 設定する透過度 0.0～1.0
 * @return {Number} 透過度
 * @example
 * var v = new musi.Parasite("div");
 * // 透過度の設定
 * v.alpha(0.3);
 * // 透過度の取得
 * var a = v.alpha(); // a = 0.3
 */
musi.Parasite.prototype.alpha = function(in_alpha){
	var h=this._;
	var s=h.style;
	var a=Number(in_alpha);
	if(!isNaN(a)){
		if(a < 0.0) a = 0.0;
		if(a > 1.0) a = 1.0;
		s.filter = "alpha(opacity="+(a*100)+")";
		s.MozOpacity = a;
		s.opacity = a;
		s.KhtmlOpacity = a;
		this._alpha = a;
	}
	return this._alpha;
};
/** @ignore */
musi.Parasite.prototype._alpha=1;

/**
 * オブジェクトの枠内幅
 * @returns {Number}
 */
musi.Parasite.prototype.innerWidth = function(){
	return this._.clientWidth;
};
/**
 * オブジェクトの枠内高
 * @returns {Number}
 */
musi.Parasite.prototype.innerHeight = function(){
	return this._.clientHeight;
};
/**
 * オブジェクトの枠内サイズ
 * @returns {musi.Vec2}
 */
musi.Parasite.prototype.innerSize = function(){
	return new musi.Vec2(this._.clientWidth,this._.clientHeight);
};
/**
 * オブジェクトの枠外幅
 * @returns {Number}
 */
musi.Parasite.prototype.outerWidth = function(){
	return this._.offsetWidth;
};
/**
 * オブジェクトの枠外高
 * @returns {Number}
 */
musi.Parasite.prototype.outerHeight = function(){
	return this._.offsetHeight;
};
/**
 * オブジェクトの枠外サイズ
 * @returns {musi.Vec2}
 */
musi.Parasite.prototype.outerSize = function(){
	return new musi.Vec2(this._.offsetWidth,this._.offsetHeight);
};

/**
 * クラス名を追加する
 * @param {String} clsname 追加するクラス名
 */
musi.Parasite.prototype.addClass = function(clsname){
	if(this.hasClass(clsname)){
		return;
	}
	this._.className += " "+clsname;
};
/**
 * クラス名を削除する
 * @param {String} clsname 削除するクラス名
 */
musi.Parasite.prototype.delClass = function(clsname){
	var newcls = "";
	var h=this._;
	var list = h.className.split(" ");
	for(var i=0,len=list.length;i<len;++i){
		var l=list[i];
		if(clsname == l || l.length == 0) continue;
		if(newcls.length > 0) newcls += " ";
		newcls += l;
	}
	h.className = newcls;
};
/**
 * クラス名を全削除する
 */
musi.Parasite.prototype.clearClass = function(){
	this._.className = "";
};
/**
 * 子ノードリストを取得する
 * @returns {Array} 子ノードmusi.Parasiteリスト
 */
musi.Parasite.prototype.getChild = function(){
	var list = this._.childNodes;
	var ret = [];
	for(var i=0,len=list.length;i<len;++i){
		ret.push(musi.convertParasite(list[i]));
	}
	return ret;
};
/**
 * 親ノードを取得する
 * @returns {musi.Parasite} 親ノード
 */
musi.Parasite.prototype.getParent = function(){
	return musi.convertParasite(this._.parentNode);
};
/**
 * クラスチェック<br>
 * クラスは、スペースで複数宣言できるので、どれかに当てはまるかをチェックする
 * @param {String} clsname チェックするクラス名
 * @returns {Boolean}
 */
musi.Parasite.prototype.hasClass = function(clsname){
	if(this._.className == null) return false;
	var list = this._.className.split(" ");
	for(var i=0,len=list.length;i<len;++i){
		var l=list[i];
		if(clsname == l) return true;
	}
	return false;
};
/**
 * このオブジェクトに内包された要素を検索する
 * querySelectorラッパー
 * @param {String} in_sel CSSセレクター
 * @returns {musi.Parasite}
 */
musi.Parasite.prototype.parasite = function(in_sel){
	var e = this._.querySelector(in_sel);

	if(e == null) return null;
	if(e.parasite instanceof musi.Parasite){
		return e.parasite;
	}
	return new musi.Parasite(e);
};
/**
 * このオブジェクトに内包された要素を検索する
 * querySelectorラッパー
 * @param {String} in_sel CSSセレクター
 * @returns {Array} パラサイト配列
 */
musi.Parasite.prototype.parasites = function(in_sel){
	var ret = this._.querySelectorAll(in_sel);
	var list = [];
	for(var i=0,len=ret.length;i<len;++i){
		var e = ret[i];
		if(e == null) continue;
		if(e.parasite instanceof musi.Parasite){
			list[list.length] = e.parasite;
		}else{
			list[list.length] = new musi.Parasite(e);
		}
	}
	return list;
};
/**
 * このオブジェクトの子供からIDによる要素を検索する
 * @param {String} in_id 取得する要素ID
 * @returns {musi.Parasite}
 */
musi.Parasite.prototype.getParasiteById = function(in_id){
	var ret = this._.querySelector("#"+in_index);
	if(ret == null) return null;
	if(ret.parasite instanceof musi.Parasite){
		return ret.parasite;
	}
	return new musi.Parasite(ret);
};
/**
 * このオブジェクトの子供からIDによる要素を検索する
 * @param {String} id 取得する要素ID
 * @returns {musi.Parasite}
 */
musi.Parasite.prototype.getParasiteByID = musi.Parasite.prototype.getParasiteById;
/**
 * このオブジェクトの子供からClassによる要素を検索する
 * @param {String} in_class 取得するクラス名
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByClass = function(in_class){
	var ret = this._.querySelectorAll("."+in_index);
	var list = [];
	for(var i=0,len=ret.length;i<len;++i){
		var e = ret[i];
		if(e == null) continue;
		if(e.parasite instanceof musi.Parasite){
			list[list.length] = e.parasite;
		}else{
			list[list.length] = new musi.Parasite(e);
		}
	}
	return list;
};
/**
 * このオブジェクトの子供からClassによる要素を検索する
 * @param {String} in_class 取得するクラス名
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByClassName = musi.Parasite.prototype.getParasiteByClass;
/**
 * このオブジェクトの子供から名前による要素を検索する
 * @param {String} in_name 取得する名前
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByName = function(in_name){
	var ret = this._.querySelectorAll("*[name="+in_index+"]");
	var list = [];
	for(var i=0,len=ret.length;i<len;++i){
		var e = ret[i];
		if(e == null) continue;
		if(e.parasite instanceof musi.Parasite){
			list[list.length] = e.parasite;
		}else{
			list[list.length] = new musi.Parasite(e);
		}
	}
	return list;
};
/**
 * このオブジェクトの子供からタグによる要素を検索する
 * @param {String} in_tag 取得するタグ
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByTag = function(in_tag){
	var ret = this._.querySelectorAll(in_index);
	var list = [];
	for(var i=0,len=ret.length;i<len;++i){
		var e = ret[i];
		if(e == null) continue;
		if(e.parasite instanceof musi.Parasite){
			list[list.length] = e.parasite;
		}else{
			list[list.length] = new musi.Parasite(e);
		}
	}
	return list;
};
/**
 * このオブジェクトの子供からタグによる要素を検索する
 * @param {String} in_tag 取得するタグ
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByTagName = musi.Parasite.prototype.getParasiteByTag;

/**
 * このオブジェクトの子供から選別関数による要素を検索する
 * @param {Function} in_func 選別関数 in_func({Element}elem)の形で呼ばれるtrueを返すと選択される
 * @returns {Array<musi.Parasite>}
 */
musi.Parasite.prototype.getParasiteByFunc = function(in_tag){
	return musi.getParasiteByFunc(in_tag,this);
};
(new function(){
	/** @ignore */
	var fadeinhdl = musi.inherit(musi.AutoWork, function(in_obj,in_wait,in_speed, in_hdl){
		this._obj = in_obj;
		this._wait = in_wait || 0;
		this._speed = in_speed;
		this._hdl = in_hdl;
		this._start = null;
		if(in_speed < 0) throw "musi.Parasite.fadein speed is invalid value.";
	});
	/** @ignore */
	fadeinhdl.prototype.run = function(){
		var now = (new Date()).getTime();
		if(this._start == null)
			this._start = now;
		if(this._start+this._wait > now) return;

		var o = this._obj;
		var a = o.alpha()+this._speed;
		o.alpha(a);
		if(a > 1.0){
			if(this._hdl)this._hdl();
			return true;
		}
		return false;
	};
	/** @ignore */
	var fadeouthdl = musi.inherit(musi.AutoWork, function(in_obj,in_wait,in_speed, in_hdl){
		this._obj = in_obj;
		this._wait = in_wait || 0;
		this._speed = in_speed;
		this._hdl = in_hdl;
		this._start = null;
		if(in_speed < 0) throw "musi.Parasite.fadeout speed is invalid value.";
	});
	/** @ignore */
	fadeouthdl.prototype.run = function(){
		var now = (new Date()).getTime();
		if(this._start == null)
			this._start = now;
		if(this._start+this._wait > now) return;

		var o = this._obj;
		var a = o.alpha()-this._speed;
		o.alpha(a);
		if(a < 0.0){
			if(this._hdl)this._hdl();
			return true;
		}
		return false;
	};


	/**
	 * フェードイン
	 * @param {Number} wait フェードイン開始までのミリ秒
	 * @param {Number} speed フェードイン速度 0～1.0
	 * @param {Function} hdl フェードイン終了時ハンドル
	 */
	musi.Parasite.prototype.fadein = function(wait, speed, hdl){
		(new fadeinhdl(this,wait, speed,hdl)).start();
	};

	/**
	 * フェードアウト
	 * @param {Number} wait フェードアウト開始までのミリ秒
	 * @param {Number} speed フェードアウト速度 0～1.0
	 * @param {Function} hdl フェードアウト終了時ハンドル
	 */
	musi.Parasite.prototype.fadeout = function(wait, speed,hdl){
		(new fadeouthdl(this,wait, speed,hdl)).start();
	};
});


/*************************************************************/
/**
 * @augments musi.Parasite
 * @class
 * テーブル<br>
 * テーブル操作を行う<br>
 * @param {Number} in_r 初期ロー数 @default 3
 * @param {Number} in_c 初期カラム数 @default 3
 */
musi.Table = musi.inherit(musi.Parasite, function(in_r, in_c){
	this.super_constructor("table");
	if(in_c != null) this.column = in_c;
	if(in_r != null) this.row = in_r;
	var _ = this._;
	var cen=this.column;
	for(var r=0,ren=this.row;r<ren;++r){
		var row = _.insertRow(-1);
		for(var c=0;c<cen;++c){
			row.insertCell(-1);
		}
	}
});
/**
 * ロー数<br>
 * ReadOnly ここを変更した場合、動作を保証しません
 */
musi.Table.prototype.row = 3;
/**
 * カラム数<br>
 * ReadOnly ここを変更した場合、動作を保証しません
 */
musi.Table.prototype.column = 3;

/**
 * サイズ変更を行う
 * @param {Number} in_r ロー数
 * @param {Number} in_c カラム数
 * @return {musi.Table} 自己参照
 */
musi.Table.prototype.resize = function(in_r, in_c){
	var _ = this._;
	var subc = in_c - this.column;
	var subr = in_r = this.row;
	this.column = in_c;
	this.row = in_r;
	// まず削除
	while(subr < 0){
		_.deleteRow(-1);
		++subr;
	}
	if(subc != 0){
		for(var r=0,ren=_.rows.length;r<ren;++i){
			var row = _.rows[r];
			var sub = subc;
			while(sub < 0){
				row.deleteCell(-1);
				++sub;
			}
			while(sub > 0){
				row.insertCell(-1);
				--sub;
			}
		}
	}
	while(subr > 0){
		_.insertRow(-1);
		--subr;
	}
	return this;
};
/**
 * セルアクセス<br>
 * 指定位置の要素をパラサイトで取得
 * @param {Number} in_r ロー
 * @param {Number} in_c カラム
 * @return {musi.Parasite}
 */
musi.Table.prototype.at = function(in_r, in_c){
	if(in_c >= this.column || in_r >= this.row) return null;
	return musi.convertParasite(this._.rows[in_r].cells[in_c]);
};
/**
 * 行アクセス
 * @param {Number} in_r アクセスする行番号
 * @return {musi.TableRow} 範囲外や存在しない場合null
 */
musi.Table.prototype.atRow = function(in_r){
	if(in_r >= this.row) return null;
	var elem = this._.rows[in_r];
	if(!(elem.parasite instanceof musi.TableRow)){
		elem.parasite = null;
		var row = new musi.TableRow();
		row.super_constructor(elem);
		row.column = this.column;
	}
	return elem.parasite;
};
/**
 * 行を追加
 * @param {Number} in_r 挿入する位置 指定しない場合やオーバーしている場合、最後に追加
 * @return {musi.Table} 自己参照
 */
musi.Table.prototype.addRow = function(in_r){
	if(in_r == null) in_r = -1;
	if(in_r >= this.row) in_r = -1;
	var row = this._.insertRow(in_r);
	for(var i=0,len=this.column;i<len;++i){
		row.insertCell(-1);
	}
	++this.row;
	return this;
};
/**
 * 見出し行を追加
 * @param {Number} in_r 挿入する位置 指定しない場合やオーバーしている場合、最後に追加
 * @return {musi.Table} 自己参照
 */
musi.Table.prototype.addTRow = function(in_r){
	if(in_r == null) in_r = -1;
	if(in_r >= this.row) in_r = -1;
	var row = this._.insertRow(in_r);
	for(var i=0,len=this.column;i<len;++i){
		var cell = document.createElement("th");
		row.appendChild(cell);
	}
	++this.row;
	return this;
};

/**
 * 列を追加
 * @param {Number} in_c 挿入する位置 指定しない場合やオーバーしている場合、最後に追加
 * @return {musi.Table} 自己参照
 */
musi.Table.prototype.addColumn = function(in_c){
	if(in_c == null) in_c = -1;
	if(in_c >= this.column) in_c = -1;
	var _ = this._;
	for(var i=0,len=this.row;i<len;++i){
		var row = _.rows[i];
		if(row == null) continue;

		row.insertCell(in_c);
	}
	return this;
};

/**
 * @augments musi.Parasite
 * @class
 * テーブルの行<br>
 * {@link musi.Table}クラスのgetRowで取得できる
 */
musi.TableRow = musi.inherit(musi.Parasite,function(){});
/**
 * カラム数<br>
 * ReadOnly ここを変更した場合、動作を保証しません
 */
musi.TableRow.prototype.column = 3;
/**
 * セルアクセス<br>
 * 指定位置の要素をパラサイトで取得
 * @param {Number} in_c カラム
 * @return {musi.Parasite}
 */
musi.TableRow.prototype.at = function(in_c){
	return musi.convertParasite(this._.cells[in_c]);
};

/* ***************************************** */
/**
 * Base64変換を行うクラス
 * @class Base64変換クラス
 * @param {String} mark Base64でしようする記号2文字+パディングを指定する 指定しない場合"+/="が使用される
 * @throws markが文字列かつ2文字または3文字じゃなかった場合
 */
musi.Base64 = function(mark){
	var tbl = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	if(!musi.isString(mark)){
		tbl += "+/=";
	}else if(mark.length == 2 || mark.length == 3){
		tbl += mark;
	}else{
		throw "error: musi.Base64 mark is invalid value.";
	}
	this._tbl = tbl.split("");
	this._pad = "";
	if(this._tbl.length > 64){
		this._pad = this._tbl[64];
		this._tbl.splice(64);
	}
	this._dtbl = {};
	for(var i=0;i<64;++i){
		this._dtbl[this._tbl[i]] = i;
	}
};
(new function(){
	var deftbl = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9","+","/"];
	var defdtbl = {"0":52,"1":53,"2":54,"3":55,"4":56,"5":57,"6":58,"7":59,"8":60,"9":61,"A":0,"B":1,"C":2,"D":3,"E":4,"F":5,"G":6,"H":7,"I":8,"J":9,"K":10,"L":11,"M":12,"N":13,"O":14,"P":15,"Q":16,"R":17,"S":18,"T":19,"U":20,"V":21,"W":22,"X":23,"Y":24,"Z":25,"a":26,"b":27,"c":28,"d":29,"e":30,"f":31,"g":32,"h":33,"i":34,"j":35,"k":36,"l":37,"m":38,"n":39,"o":40,"p":41,"q":42,"r":43,"s":44,"t":45,"u":46,"v":47,"w":48,"x":49,"y":50,"z":51,"+":62,"/":63};
	var defpad = "=";
	var _encode = function(tbl,pad,bytes){
		var md = bytes.length % 3;
		var ret = "";
		var tmp = 0;
		if(md>0){
			for(var i=3-md;i>0;--i)
				bytes[bytes.length]=0;
		}
		for(var i=0,len=bytes.length;i<len;i+=3){
			tmp = (bytes[i]<<16) | (bytes[i+1]<<8) | bytes[i+2];
			ret += tbl[(tmp >>> 18) & 0x3f]+tbl[(tmp>>>12) & 0x3f] + tbl[ (tmp >>> 6) & 0x3f] + tbl[tmp & 0x3f];
		}
		if(md){
			md = 3-md;
			ret = ret.substr(0,ret.length-md);
		}
		var max = ret.length%4;
		if(max != 0){
			for(var i=0,len=4-max;i<len;++i){
				ret+=pad;
			}
		}
		return ret;
	};
	var _decode = function(tbl,pad,str){
		if(pad.length < 0){
			pad = "XX";
		}
		var c,b;
		var ret = [];
		var tmp = null;
		for(var i=0,len=str.length;i<len;++i){
			c = str[i];
			if(c == pad) break;
			b = tbl[c];
			switch(i%4){
				case 0:
					tmp = b;
					break;
				case 1:
					ret[ret.length] = (tmp<<2)|((b&0x30)>>>4);
					tmp = b&0xf;
					break;
				case 2:
					ret[ret.length] = (tmp<<4)|((b&0x3c)>>>2);
					tmp = b&0x3;
					break;
				case 3:
					ret[ret.length] = (tmp<<6)|b;
					tmp = null;
					break;
				default:
					break;
			}
		}
		return ret;
	};
	/**
	 * バイト配列を標準のBase64に変換する
	 * @param {Array} bytes 変換元のバイト配列
	 * @return {String} Base64文字列
	 */
	musi.Base64.encode = function(bytes){
		return _encode(deftbl,defpad,bytes);
	};
	/**
	 * バイト配列をBase64に変換する
	 * @param {Array} bytes 変換元のバイト配列
	 * @return {String} Base64文字列
	 */
	musi.Base64.prototype.encode = function(bytes){
		return _encode(this._tbl,this._pad,bytes);
	};
	/**
	 * 標準Base64文字列をバイト配列に変換する
	 * @param {String} Base64文字列
	 * @return {Array} バイト配列
	 */
	musi.Base64.decode = function(str){
		return _decode(defdtbl,defpad,str);
	};
	/**
	 * Base64文字列をバイト配列に変換する
	 * @param {String} Base64文字列
	 * @return {Array} バイト配列
	 */
	musi.Base64.prototype.decode = function(str){
		return _decode(this._dtbl,this._pad,str);
	};
});

/**
 * @namespace UTF8を変換する
 */
musi.UTF8 = {};
/**
 * UTF8文字列をバイト配列に変換する
 * @param {String} str UTF8文字列
 * @return {Array} バイト配列
 */
musi.UTF8.encode = function(str){
	var ret = [];
	if(str == null) return ret;
	var idx = 0;
	var c,j;
	for(var i=0,len=str.length;i<len;++i){
		c = str.charCodeAt(i);
		if(c<=0x7f) ret[idx++] = c;
		else if(c<=0x7ff){
			ret[idx++] = ((c>>6) & 0x1f) | 0xC0;
			ret[idx++] = (c & 0x3f) | 0x80;
		}else if(c<=0xffff){
			ret[idx++] = 0xe0 | (c >>> 12 );
			ret[idx++] = 0x80 | ((c >>> 6 ) & 0x3f);
			ret[idx++] = 0x80 | (c & 0x3f);
		}else{
			j = 4;
			while(c >> (6*j)) ++j;

			ret[idx++] = ((0xff00 >>> j) & 0xff) | (c >>> (6*--j) );
			while (j--)
				ret[idx++] = 0x80 | ((c >>> (6*j)) & 0x3f);
		}
	}
	return ret;
};
/**
 * バイト配列をUTF8文字列に変換する
 * @param {Array} bytes バイト配列
 * @return {String} UTF8文字列
 */
musi.UTF8.decode = function(bytes){
	if(bytes == null) return "";
	var ret = "";
	var idx=0;
	var len=bytes.length;
	var b,j;
	while(idx < len){
		b=bytes[idx++];
		if(b <= 0x7f){
			// IYH
		}else if(b <= 0xdf){
			b = ((b&0x1f)<<6)+(bytes[idx++]&0x3f);
		}else if(b <= 0xe0){
			b = ((bytes[idx++]&0x1f)<<6|0x800)+(bytes[idx++]&0x3f);
		}else{
			j=1;
			while(b&(0x20 >>> j))j++;
			b = b & (0x1f >>> j);
			while(j-- >= 0)
				b = (b << 6) ^ (bytes[idx++] & 0x3f);
		}
		ret += String.fromCharCode(b);
	}
	return ret;
};
(new function(){
	/**
	 * @class Zipデータ
	 */
	musi.Zip = function(){
		this._child = {};
		this._child["."] = this;
		this._child[".."] = null;
		this._comm = "";
		this._cd = this;
		this._clip = null;
	};

	/**
	 * Zipデータのコメントを設定する
	 * @param {String} comm コメント文字列 UTF-8
	 */
	musi.Zip.prototype.comment = function(comm){
		if(comm == null)
			this._comm = "";
		else
			this._comm = ""+comm;
	};

	/**
	 * カレントディレクトリを表示
	 * @return {String}
	 */
	musi.Zip.prototype.pwd = function(){
		var ret = "";

		var c = this._cd;
		var p = this._cd[".."];
		while(p != null && musi.isObject(p._child)){
			var cld = p._child;
			for(var i in cld){
				if(cld[i] == c){
					ret = i+"/"+ret;
					c = p;
					p = c._child[".."];
					break;
				}
			}
		}

		return "/"+ret;
	};
	var _cleanPath = function(path){
		path = path.replace(/\\/g,"/").split("/");
		for(var i=path.length-1;i>=0;--i){
			path[i] = path[i].replace(/\s+$/g,"").replace(/^\s+/g);
			if(path[i] == "" && i < path.length-1)
				path.splice(i,1);
		}
		return path.join("/");
	};
	musi.Zip.prototype._mkPathArray = function(path){
		path = _cleanPath(path);
		var c = this._cd;
		if(path[0] == ""){
			c = this._root;
			path.shift();
		}
		for(var i=path.length-1;i>=0;--i){
			if(path[i] == ""){
				path.splice(i,1);
			}
		}
		return [c,path];
	};
	musi.Zip.prototype._cdl = function(path,c,err){
		var pp=null;
		while(path.length > 0){
			var p = path.shift();
			if(pp == null)
				pp=p;
			else
				pp+="/"+p;
			if(c._child[p] == null){
				musi.Log.out("musi.Zip."+err+" dont find path "+pp, true);
				return null;
			}
			c = c._child[p];
		}
		return c;
	};
	/**
	 * ディレクトリを変更
	 * @param {String} path	移動するディレクトリパス 相対または絶対パス
	 * @return {Boolean} 存在しないディレクトリパス
	 */
	musi.Zip.prototype.cd = function(path){
		var pp = path;
		var c = this._mkPathArray(path);
		path=c[1];
		c=c[0];
		c=this._cdl(path,c,"cd");
		if(c==null) return false;
		if(c == null || c._child == null){
			musi.Log.out("musi.Zip.cd dont find path "+pp, true);
			return false;
		}
		this._cd = c;
		return true;
	};
	/**
	 * ディレクトリ内のアイテム一覧
	 * @param {String} path 表示させたいディレクトリパス 相対または絶対パス 指定しない場合カレントディレクトリ
	 * @return {Array} Object{"n":ファイル名,"d":ディレクトリフラグ}の配列
	 */
	musi.Zip.prototype.ls = function(path){
		var c=null;
		if(path != null){
			c = this._mkPathArray(path);
			path=c[1];
			c=c[0];
			c=this._cdl(path,c,"cd");
			if(c==null) return null;
			c = c._child;
		}else{
			c=this._cd._child;
		}

		var ret = [];
		for(var i in c){
			if(c[i] == null) continue;
			ret[ret.length] = {"n":i,"d":((c[i] instanceof dir) || (c[i] instanceof musi.Zip))};
		}
		return ret;
	};

	/**
	 * ディレクトリ作成
	 * @param {String} path 作成するパス 相対または絶対パス
	 * @return {Boolean} 作成合否
	 */
	musi.Zip.prototype.mkdir = function(path){
		var c = this._mkPathArray(path);
		path=c[1];
		c=c[0];
		var dname = path.pop();
		c=this._cdl(path,c,"mkdir");
		if(c == null || c._child == null){
			musi.Log.out("musi.Zip.mkdir dont find path "+dname, true);
			return false;
		}
		if(c._child[dname] != null){
			musi.Log.out("musi.Zip.mkdir already exist "+dname, true);
			return false;
		}
		var d = new dir();
		d._child["."]=d;
		d._child[".."]=c;
		c._child[dname] = d;

		return true;
	};
	/**
	 * アイテムを削除する
	 * @param {String} path 削除するパス ファイルでもディレクトリでも削除する サブディレクトリとかも削除する
	 * @return {Boolean} 作成合否
	 */
	musi.Zip.prototype.rm = function(path){
		var pp = path;
		var c = this._mkPathArray(path);
		path=c[1];
		c=c[0];
		var dname = path.pop();
		c=this._cdl(path,c,"rm");
		if(c == null || c._child == null || c._child[dname] == null){
			musi.Log.out("musi.Zip.rm dont find path "+dname, true);
			return false;
		}
		if(c._child[dname] instanceof file || c._child[dname] instanceof dir){
			delete c._child[dname];
		}else{
			musi.Log.out("musi.Zip.rm "+pp+" is invalid item.", true);
			return false;
		}

		return true;
	};


	/**
	 * 新規ファイル作成
	 * @param {String} path 作成するパス
	 * @return {Boolean} 作成合否
	 */
	musi.Zip.prototype.touch = function(path){
		var c = this._mkPathArray(path);
		path=c[1];
		c=c[0];
		var dname = path.pop();
		c=this._cdl(path,c,"touch");
		if(c == null || c._child == null)
			return false;
		if(c._child[dname] != null){
			musi.Log.out("musi.Zip.touch already exist "+dname, true);
			return false;
		}
		c._child[dname] = new file();

		return true;
	};


	/**
	 * 属性 名前<br>
	 * 型 UTF-8文字列
	 * @constant
	 * @type Number
	 */
	musi.Zip.CAT_NAME=1;
	/**
	 * 属性 更新日付<br>
	 * 型 Date
	 * @constant
	 * @type Number
	 */
	musi.Zip.CAT_DATE=2;
	/**
	 * 属性 コメント<br>
	 * 型 UTF-8文字列
	 * @constant
	 * @type Number
	 */
	musi.Zip.CAT_COMM=3;
	/**
	 * 属性 データ<br>
	 * 型 UTF-8文字列またはバイト配列<br>
	 * ファイルのみ
	 * @constant
	 * @type Number
	 */
	musi.Zip.FAT_DATA=101;

	/**
	 * 属性設定
	 * @param {Number} type CAT_XXX,FAT_XXX 属性
	 * @param {String} path 作成するパス
	 * @param {any} val 各属性値に設定する値
	 * @return {Boolean} 作成合否
	 */
	musi.Zip.prototype.attr = function(type,path,val){
		var pp = path;
		var c = this._mkPathArray(path);
		path=c[1];
		c=c[0];
		c=this._cdl(path,c,"attr");
		if(c == null){
			musi.Log.out("musi.Zip.attr dont find path "+dname, true);
			return false;
		}
		switch(type){
			case musi.Zip.CAT_NAME:{
				if(musi.isString(val))
					c.name = val;
				else{
					musi.Log.out("musi.Zip.attr(name) val is not string.",true);
					return false;
				}
				break;
			}
			case musi.Zip.CAT_DATE:{
				if(val instanceof Date)
					c.date = val;
				else{
					musi.Log.out("musi.Zip.attr(date) val is not Date.",true);
					return false;
				}
				break;
			}
			case musi.Zip.CAT_COMM:{
				if(musi.isString(val))
					c.comm = val;
				else{
					musi.Log.out("musi.Zip.attr(comment) val is not string.",true);
					return false;
				}
				break;
			}
			case musi.Zip.FAT_DATA:{
				if(c instanceof dir){
					musi.Log.out("musi.Zip.attr(data) "+pp+" is not file.",true);
					return false;
				}else if(musi.isString(val) || musi.isArray(val))
					c.data = val;
				else{
					musi.Log.out("musi.Zip.attr(data) val is invalid value.",true);
					return false;
				}
				break;
			}
			default:
				break;
		}

		return true;
	};

	/**
	 * Zip化
	 * @return {Array} byte配列 nullの場合失敗
	 */
	musi.Zip.prototype.encode = function(){
		var local=[];
		var cdir=[];
		var cend=[];
		var cend_obj= new _EndCentDirHeader();
		if(!_encode(this._child,local.cdir,cend_obj)){
			musi.Log.out("musi.Zip.encode invalid value",true);
			return null;
		}
		var comm = musi.UTF8.encode(this._comm);
		// セントラルディレクトリ終端レコード
		// シグネチャ
		cend[cend.length] = 0x50;
		cend[cend.length] = 0x4b;
		cend[cend.length] = 0x05;
		cend[cend.length] = 0x06;
		// テープ時代の名残？
		cend[cend.length] = 0x00;
		cend[cend.length] = 0x00;
		cend[cend.length] = 0x00;
		cend[cend.length] = 0x00;
		// セントラルディレクトリレコード数 テープ時代用
		cend[cend.length] = (cend_obj.dir_num>>>0) & 0xff;
		cend[cend.length] = (cend_obj.dir_num>>>2) & 0xff;
		// セントラルディレクトリレコード数2回目
		cend[cend.length] = (cend_obj.dir_num>>>0) & 0xff;
		cend[cend.length] = (cend_obj.dir_num>>>2) & 0xff;
		// セントラルディレクトリサイズ
		cend[cend.length] = (cdir.length>>>0) & 0xff;
		cend[cend.length] = (cdir.length>>>2) & 0xff;
		cend[cend.length] = (cdir.length>>>4) & 0xff;
		cend[cend.length] = (cdir.length>>>6) & 0xff;
		// セントラルディレクト開始位置
		cend[cend.length] = (local.length>>>0) & 0xff;
		cend[cend.length] = (local.length>>>2) & 0xff;
		cend[cend.length] = (local.length>>>4) & 0xff;
		cend[cend.length] = (local.length>>>6) & 0xff;
		// コメントサイズ
		cend[cend.length] = (comm.length>>>0) & 0xff;
		cend[cend.length] = (comm.length>>>2) & 0xff;
		// コメント
		cend.concat(comm);


		return local.concat(cdir).concat(cend);
	};
	/**
	 * Zip解凍
	 * @param {Array} bytes byte配列
	 * @return {Boolean} 解凍合否
	 */
	musi.Zip.prototype.decode = function(bytes){
		return true;
	};
	var _EndCentDirHeader = function(){};
	_EndCentDirHeader.prototype.dir_num = 0;
	var _encode = function(dir, local,cdir,cend){
		for(var i in dir){
			if(i=="." || i=="..") continue;
			var data = null;

			if(dir[i] instanceof dir){
				data = [];
			}else if(dir[i] instanceof file){
				if(musi.isArray(dir[i].data))
					data = dir[i].data;
				else
					data = musi.UTF8.encode(dir[i].data);
			}else{
				return false;
			}
			(new function(){
				var off = local.length;
				var name = musi.UTF8.encode(i);
				var exp = [];	// 拡張フィールド
				var comm = musi.UTF8.encode(dir[i].comm);
				// ローカルファイルヘッダ
				// シグネチャ
				local[local.length] = 0x50;
				local[local.length] = 0x4b;
				local[local.length] = 0x03;
				local[local.length] = 0x04;
				// 展開バージョン	ver1.0
				local[local.length] = 10;
				local[local.length] = 00;
				// オプション
				local[local.length] = 00;
				local[local.length] = 00;
				// 圧縮アルゴリズム 無圧縮
				local[local.length] = 00;
				local[local.length] = 00;
				// 更新時間 TODO
				local[local.length] = 00;
				local[local.length] = 00;
				// 更新日付 TODO
				local[local.length] = 00;
				local[local.length] = 00;
				// CRC-32 TODO
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				// 圧縮サイズ TODO
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				// 展開サイズ TODO
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				local[local.length] = 00;
				// ファイル名長
				local[local.length] = name.length & 0xff;
				local[local.length] = (name.length >>> 2) & 0xff;
				// 拡張フィールド長
				local[local.length] = exp.length & 0xff;
				local[local.length] = (exp.length >>> 2) & 0xff;
				// 名前
				local.concat(name);
				// 拡張フィールド
				local.concat(exp);

				// セントラルディレクトリファイルヘッダ
				// シグネチャ
				cdir[cdir.length] = 0x50;
				cdir[cdir.length] = 0x4b;
				cdir[cdir.length] = 0x01;
				cdir[cdir.length] = 0x02;
				// 展開バージョン	ver1.0
				cdir[cdir.length] = 10;
				cdir[cdir.length] = 00;
				// オプション
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 圧縮アルゴリズム 無圧縮
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 更新時間 TODO
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 更新日付 TODO
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// CRC-32 TODO
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 圧縮サイズ TODO
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 展開サイズ TODO
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// ファイル名長
				cdir[cdir.length] = name.length & 0xff;
				cdir[cdir.length] = (name.length >>> 2) & 0xff;
				// 拡張フィールド長
				cdir[cdir.length] = exp.length & 0xff;
				cdir[cdir.length] = (exp.length >>> 2) & 0xff;
				// コメント長
				cdir[cdir.length] = comm.length & 0xff;
				cdir[cdir.length] = (comm.length >>> 2) & 0xff;
				// ディスク番号 テープ時代の名残？
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// 内部ファイル属性
				cdir[cdir.length] = (dir[i] instanceof dir)?0:musi.isString(dir[i].data)?1:0;
				cdir[cdir.length] = 00;
				// 外部ファイル属性 TODO 調べてもわからなかった
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				cdir[cdir.length] = 00;
				// ローカルファイルヘッダ位置
				cdir[cdir.length] = (off>>>0) & 0xff;
				cdir[cdir.length] = (off>>>2) & 0xff;
				cdir[cdir.length] = (off>>>4) & 0xff;
				cdir[cdir.length] = (off>>>6) & 0xff;
				// 名前
				cdir.concat(name);
				// 拡張フィールド
				cdir.concat(exp);
				// コメント
				cdir.concat(comm);

				++cend.dir_num;
			});

			if(dir[i] instanceof dir){

			}else if(dir[i] instanceof file){

			}
		}
		return true;
	};

	var header = function(){
		this.date=new Date();
	};
	header.prototype.date=null;
	header.prototype.comm="";

	var file = musi.inherit(header, function(){});
	file.prototype.data = null;

	var dir = musi.inherit(header, function(){
		this._child = {};
	});
	dir.prototype._child = null;
});

/*******************************************/
/**
 * @function
 * @description
 * コンテキストメニューを作成する<br>
 * 複数本体設定がある場合、先の設定が優先される<br>
 * また、子の設定は子の設定の方が優先される<br>
 * 設定できるオブジェクト<br>
 * <style type="text/css">
 * .musi_contectmenu_tbl th, .musi_contectmenu_tbl td{
 * 	border-bottom: 1px solid #B4B4B4;
 * 	border-right: 1px solid #B4B4B4;
 * 	font-weight: normal;
 * 	padding: 4px;
 * 	vertical-align: top;
 * }
 * </style>
 * <table cellspacing="0" style="border:1px #aaaaaa solid; " class="musi_contectmenu_tbl">
 * <tr style="background-color:#ddccff;"><td>種類</td><td>メンバ</td><td>設定値</td><td>詳細</td></tr>
 *
 * <tr><td rowspan=5>要素</td>
 * 			<td>type</td><td>"element"</td><td>タイプ判定:必須</td>	</tr>
 * <tr>		<td>label</td><td>String</td><td>表示文字:必須</td>	</tr>
 * <tr>		<td>icon</td><td>String</td><td>アイコンURL</td>	</tr>
 * <tr>		<td>handle</td><td>Function</td><td>クリック動作</td>	</tr>
 * <tr>		<td>child</td><td>Array</td><td>子要素用オブジェクトリスト。設定するとクリック動作が無効になる。</td>	</tr>
 *
 * <tr><td rowspan=1>罫線</td>
 * 			<td>type</td><td>"line"</td><td>タイプ判定:必須</td></tr>
 *
 * <tr><td rowspan=16>本体設定</td>
 * 			<td>type</td>	<td>"option"</td><td>タイプ判定:必須</td>	</tr>
 * <tr>		<td>pos</td>	<td>musi.Vec2</td><td>親オブジェクトからの差分位置</td>	</tr>
 * <tr>		<td>end</td>	<td>"click"|"mouseout"|"manual"</td><td>終了判定</td>	</tr>
 * <tr>		<td>lcolor</td>	<td>musi.Color</td>	<td>枠線色(要素ではなく、全体の枠)</td>	</tr>
 * <tr>		<td>fsize</td>	<td>Number</td>		<td>文字サイズ 単位px</td>	</tr>
 * <tr>		<td>fcolor</td>	<td>musi.Color</td>	<td>文字色</td>	</tr>
 * <tr>		<td>bcolor</td>	<td>musi.Color</td>	<td>背景色</td>	</tr>
 * <tr>		<td>ofcolor</td><td>musi.Color</td>	<td>マウスオーバー時文字色</td>	</tr>
 * <tr>		<td>obcolor</td><td>musi.Color</td>	<td>マウスオーバー時背景色</td>	</tr>
 * <tr>		<td>olcolor</td><td>musi.Color</td>	<td>マウスオーバー時枠線色</td>	</tr>
 * <tr>		<td>dfcolor</td><td>musi.Color</td>	<td>マウス押下時文字色</td>	</tr>
 * <tr>		<td>dbcolor</td><td>musi.Color</td>	<td>マウス押下時背景色</td>	</tr>
 * <tr>		<td>dlcolor</td><td>musi.Color</td>	<td>マウス押下時枠千色</td>	</tr>
 * <tr>		<td>anime</td>	<td>"default"|"fade"|"scale"</td><td>開閉時アニメ</td></tr>
 * <tr>		<td>speed</td>	<td>Number</td><td>開閉アニメ速度<br>scaleでマイナスにすると下から出る</td>	</tr>
 * <tr>		<td>close</td>	<td>Function</td><td>閉じた時に呼ばれる</td>	</tr>
 * </table>
 * @param {Array} in_config 各種オブジェクトの配列
 * @param {Element or musi.Parasite or Event} in_parent 親オブジェクト または、イベントオブジェクト 指定しない場合body
 * @return {Function} 終了関数 呼ぶと、メニューが消える。manualタイプの時には呼ばないと消えない
 * @example
var element = musi.getParasiteById("pupa");
element.addEvent("click",function(evt){
	musi.createContextMenu([
		{type:"element",label:"Test1",handle:function(){alert("test1");}},
		{type:"line"},
		{type:"element",label:"Test2",child:[
			{type:"element",label:"Test21",handle:function(){alert("test21");}},
			{type:"line"},
			{type:"element",label:"Test22",handle:function(){alert("test22");}},
			{type:"option",bcolor:new musi.Color(200,255,200)}
		]},
		{type:"option",bcolor:new musi.Color(255,240,230)}
	],evt);
});
 */
musi.createContextMenu = null;
(new function(){
	musi.createContextMenu = function(in_config, in_parent, sys_bind, sys_opt, sys_top, sys_left, sys_right, sys_mout, sys_release){
		var opt = {};
		var elem = [];
		for(var i=0,len=in_config.length;i<len;++i){
			var e = in_config[i];
			if(e==null) break;
			if(e.type != "option"){
				if(e.type == "element" || e.type == "line")
					elem[elem.length] = e;
				continue;
			}
			for(var j in e){
				var l=e[j];
				if(opt[j] == null && l != null){
					opt[j] = l;
				}
			}
		}
		if(sys_opt && sys_opt.type == "option"){
			for(var j in sys_opt){
				var l=sys_opt[j];
				if(opt[j] == null && l != null){
					opt[j] = l;
				}
			}
		}
		(new function(){
			var par = in_parent;
			if(!(par instanceof Event)){
				if(par instanceof musi.Parasite){
					par = par._;
				}
				if(par == window || par == document || par == null){
					in_parent = document.getElementsByTagName("body")[0];
				}
				in_parent = musi.convertParasite(in_parent);
			}
		});


		// 不足オプションの追加
		if(!opt.pos) opt.pos = new musi.Vec2(0,0);
		if(!opt.fsize) opt.fsize = 12;
		if(!opt.fcolor) opt.fcolor = new musi.Color(0,0,0);
		if(!opt.bcolor) opt.bcolor = new musi.Color(230,230,230);
		if(!opt.lcolor) opt.lcolor = new musi.Color(100,100,100);
		if(!opt.ofcolor) opt.ofcolor = new musi.Color(0,0,0);
		if(!opt.obcolor) opt.obcolor = new musi.Color(230,237,246);
		if(!opt.olcolor) opt.olcolor = new musi.Color(174,207,247);
		if(!opt.dfcolor) opt.dfcolor = new musi.Color(230,237,246);
		if(!opt.dbcolor) opt.dbcolor = new musi.Color(30,77,136);
		if(!opt.dlcolor) opt.dlcolor = new musi.Color(174,207,247);
		if(!opt.end) opt.end = "click";
		if(!sys_right) sys_right = 0;
		if(!opt.speed) opt.speed = 5;
		var body = musi.getRootParasite();
		var winw = musi.env.width();
		var winh = musi.env.height();
		var z = musi.env.maxZindex()+1;
		var z1 = z+1;
		z +="";
		z1+="";
		var alf = 0.001;
		var enable_action = false;
		var css = null;
		var base = new musi.Parasite("div");
		var orgbind = null;
		while(true){
			orgbind = musi.rebindID(base);
			if(musi.getElementsByClass(orgbind).length == 0) break;
		}
		var bind = orgbind;
		base.addClass(bind);
		css = base.style;
		css.zIndex = z1;
		try{
			css.border = "1px "+opt.lcolor.rgba()+" solid";
		}catch(err){css.border = "1px "+opt.lcolor.rgb()+" solid";}
		try{
			css.backgroundColor = opt.bcolor.rgba();
		}catch(err){css.backgroundColor = opt.bcolor.rgb();}
		css.padding = "2px";
		css.position = "fixed";
		css.boxShadow = "2px 2px 3px rgba(0,0,0,0.5)";

		var tbl = new musi.Table(elem.length, 4); // ベース
		if(sys_bind != null){
			opt.end = "mouseout";
			bind += " "+sys_bind;
		}else if(in_parent instanceof Event){
			sys_top = in_parent.clientY-5;
			sys_left = in_parent.clientX-5;
		}else{
			sys_top = in_parent.dpos().y+opt.pos.y;
			sys_left = sys_right+in_parent.dpos().x+opt.pos.x;
		}

		css = tbl.style;
		css.position = "relative";
		css.width = "auto";
		css.borderCollapse = "collapse";
		css.fontSize = opt.fsize+"px";
		css.cursor = "pointer";
		try{
			css.color = opt.fcolor.rgba();
		}catch(err){css.color = opt.fcolor.rgb();}
		css.zIndex = z1;
		css.margin = "0px";
		css.padding = "0px";
		base.appendChild(tbl);
		var child = [];
		var release = null;
		(new function(){
			var sato = null;
			if(sys_opt == null){
				sato = new musi.Parasite("div"); // 他の操作をさせないための保護BOX
				css = sato.style;
				css.position = "fixed";

				sato.alpha(alf);
				css.backgroundColor = "black";
				css.left = css.right = "0px";
				css.width = winw+"px";
				css.height = winh+"px";
				css.top = "0px";
				css.left = "0px";
				css.zIndex = z;
				css.margin = "0px";
				css.padding = "0px";
				body.appendChild(sato);
				if(opt.end != "manual"){
					sato.addEvent("click", function(evt){
						evt.stop();
						if(!enable_action) return;
						release();
					});
				}
			}
			var local_release = function(){
				for(var i=0,len=child.length;i<len;++i){
					try{
						var c = child[i];

						if(c instanceof Function) c();
					}catch(ex){}
				}
				base.release();
				if(sato)sato.release();
				if(musi.isFunction(opt.close))
					opt.close();
			};
			var is_released = false;
			/** @ignore */
			release = function(){
				if(is_released) return;
				is_released = true;
				if(opt.anime == "scale"){
					enable_action = false;
					var now = tbl.innerHeight();
					var speed = 1;
					var scroll = 0;
					if(opt.speed > 0) speed = opt.speed;
					else{
						speed = -opt.speed;
						scroll = -opt.speed;
					}
					var anime = new musi.AutoWork();
					anime.run = function(){
						var is_end = false;
						now-=speed;
						if(now < 0){
							now = 0;
							is_end = true;
							local_release();
						}
						base.style.height = now+"px";
						base.style.top = base.dpos().y+scroll+"px";
						return is_end;
					};
					anime.start();
				}else if(opt.anime == "fade"){
					if(opt.speed < 0) opt.speed *= -1;
					enable_action = false;
					base.fadeout(0, opt.speed, function(){
						local_release();
					});
				}else{
					local_release();
				}
			};
		});

		var ninja = new musi.Parasite("div"); // ベースタッチ範囲
		ninja.addClass(bind);
		css = ninja.style;
		ninja.alpha(alf);
		css.backgroundColor = "black";
		css.zIndex = z;
		css.position = "fixed";
		css.margin = "0px";
		css.padding = "0px";

		base.appendChild(ninja);
		if(!(in_parent instanceof Event)){
			var kuno1 = new musi.Parasite("div"); // 親オブジェクトタッチ範囲
			kuno1.addClass(bind);
			css = kuno1.style;
			kuno1.alpha(alf);
			css.backgroundColor = "black";
			css.zIndex = z;
			css.position = "fixed";
			css.margin = "0px";
			css.padding = "0px";
			var pos = in_parent.dpos();
			kuno1.left = pos.x-5;
			kuno1.top = pos.y-5;
			css.left = kuno1.left+"px";
			css.top = kuno1.top+"px";
			css.width = in_parent.outerWidth()+10+"px";
			css.height = in_parent.outerHeight()+10+"px";
			base.appendChild(kuno1);
		}
		var mouseout = null;
		if(opt.end == "mouseout"){
			var mout = function(evt){
				evt.stop();
				evt.cancel();
				if(!enable_action) return;
				var ps = musi.getParasiteByClass(orgbind,this);
				var is_out = true;
				var px = evt.clientX;
				var py = evt.clientY;
				for(var i=0,len=ps.length;i<len;++i){
					var c = ps[i];
					var p = null;
					if(c.top == null){
						p = c.dpos();
					}else{
						p = new musi.Vec2(c.left,c.top);
					}
					if(px <= p.x || py <= p.y) continue;
					if(c.outerWidth()+p.x <= px) continue;
					if(c.outerHeight()+p.y <= py) continue;
					is_out = false;
					break;
				}
				if(is_out){
					release();
					if(sys_mout) sys_mout(evt);
				}
			};
			base.addEvent("mouseout", mout);
			mouseout = function(evt,arg){mout.call(base,evt,arg);};
		}
		var inrelease = null;
		if(opt.end != "manual"){
			base.addEvent("click", function(evt){
				evt.stop();
				if(!enable_action) return;
				release();
				if(sys_release) sys_release();
			});
			inrelease = function(){
				release();if(sys_release)sys_release();
			};
		}



		for(var i=0,len=elem.length;i<len;++i){
			(new function(){
				var e = elem[i];
				var row = tbl.atRow(i);
				css = row.style;
				css.margin = "0px";
				css.padding = "0px";
				if(e.type == "line"){
					css.backgroundImage = "url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAFCAYAAACEhIafAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9sEGgoPNju8W/cAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAAF0lEQVQI12NgQAbGDP///3/OwMDAwAAAH5wEGEilGu0AAAAASUVORK5CYII=)";
					css.height = "5px";
					css.backgroundRepeat = "repeat-x";
					css.backgroundPosition = "left 0px";
					css.border = "0px #000000 solid";
					css.borderLeft = "1px transparent solid";
					(new function(){
						var cell = tbl.at(i,1);
						css = cell.style;
						try{
							css.borderLeft = "1px rgba(0,0,0,0.5) solid";
							css.borderRight = "1px rgba(255,255,255,0.5) solid";
						}catch(err){
							css.borderLeft = "1px rgb(0,0,0) solid";
							css.borderRight = "1px rgb(255,255,255) solid";
						}
						css.margin = "0px";
						css.padding = "0px";
					});
				}else{
					css.border = "1px transparent solid";
					css.fontSize = opt.fsize+"px";
					row.addEvent("mouseover",function(){
						if(!enable_action) return;
						var css = this.style;
						try{
							css.color = opt.ofcolor.rgba();
							css.backgroundColor = opt.obcolor.rgba();
							css.border = "1px "+opt.olcolor.rgba()+" solid";
						}catch(err){
							css.color = opt.ofcolor.rgb();
							css.backgroundColor = opt.obcolor.rgb();
							css.border = "1px "+opt.olcolor.rgb()+" solid";
						}
						if(e.child){
							var left = tbl.dpos().x;
							child[child.length] = musi.createContextMenu(e.child, this, bind,opt, this.dpos().y, left,left+base.outerWidth(),mouseout,inrelease);
						}
					});
					row.addEvent("mouseout",function(){
						if(!enable_action) return;
						var css = this.style;
						css.color = "";
						css.backgroundColor = "";
						css.border = "1px transparent solid";
					});
					if(e.handle){
						row.addEvent("click", function(evt){
							if(!enable_action) return;
							e.handle();
							if(sys_release) sys_release();
						});
					}
					(new function(){
						var cell = tbl.at(i,0);
						var img=null;
						if(e.icon){
							img = musi.ImageCache.get(e.icon);
						}else{
							img = musi.ImageCache.get("musi:special:ninja");
						}
						css = img.style;
						css.height = opt.fsize+"px";
						css.margin = "0px";
						css.padding = "2px";
						cell.appendChild(img);
					});
					(new function(){
						var cell = tbl.at(i,1);
						css = cell.style;
						try{
							css.borderLeft = "1px rgba(0,0,0,0.5) solid";
							css.borderRight = "1px rgba(255,255,255,0.5) solid";
						}catch(err){
							css.borderLeft = "1px rgb(0,0,0) solid";
							css.borderRight = "1px rgb(255,255,255) solid";
						}
						css.margin = "0px";
						css.padding = "0px";
					});
					(new function(){
						var cell = tbl.at(i,2);
						css = cell.style;
						css.margin = "0px";
						css.padding = "2px";
						css.paddingLeft = "10px";
						cell.html(e.label);
					});
					(new function(){
						var cell = tbl.at(i,3);
						css = cell.style;
						css.margin = "0px";
						css.padding = "0px";
						css.height = opt.fsize+"px";
						if(e.child){
							var img = musi.ImageCache.get("musi:special:arrow");
							img.style.height = opt.fsize+"px";
							img.style.width = opt.fsize+"px";
							cell.appendChild(img);
						}
					});

				}
			});
		}

		body.appendChild(base);
		var rev_over = false;
		var rev_scale = opt.anime == "scale" && opt.speed < 0;
		if(sys_bind){
			if(sys_right+base.outerWidth()-5 < winw){
				base.style.left = sys_right-5+"px";
			}else{
				base.style.left = sys_left-base.outerWidth()+5+"px";
			}
			if(sys_top+base.outerHeight() >= winh){
				if(rev_scale)
					base.style.top = sys_top+"px";
				else
					base.style.top = winh-base.outerHeight()+"px";
			}else if(sys_top-tbl.innerHeight() < 0){
				if(rev_scale){
					base.style.top = 0+tbl.innerHeight()+"px";
					rev_over = true;
				}else
					base.style.top = sys_top+"px";
			}else{
				base.style.top = sys_top+"px";
			}
		}else{
			if(sys_left+base.outerWidth() < winw){
				base.style.left = sys_left+"px";
			}else{
				base.style.left = winw-base.outerWidth()+"px";
			}
			if(sys_top+base.outerHeight() >= winh){
				if(rev_scale)
					base.style.top = sys_top+"px";
				else
					base.style.top = winh-base.outerHeight()+"px";
			}else if(sys_top-tbl.innerHeight() < 0){
				if(rev_scale)
					base.style.top = 0+tbl.innerHeight()+"px";
				else
					base.style.top = sys_top+"px";
			}else{
				base.style.top = sys_top+"px";
			}
		}
		if(opt.end == "click"){
			css = ninja.style;
			css.width = winw+"px";
			css.height = winh+"px";
			ninja.top = 0;
			ninja.left = 0;
			css.top = "0px";
			css.left = "0px";
		}else{
			css = ninja.style;
			css.width = base.outerWidth()+"px";
			css.height = base.outerHeight()+"px";
			ninja.top = base.dpos().y;
			ninja.left = base.dpos().x;
			css.top = ninja.top+"px";
			css.left = ninja.left+"px";
		}
		// アニメ設定
		if(opt.anime == "scale"){
			var max = tbl.innerHeight();
			base.style.overflow = "hidden";
			base.style.height = "0px";
			var speed = 1;
			var scroll = 0;
			if(opt.speed > 0) speed = opt.speed;
			else{
				speed = -opt.speed;
				scroll = opt.speed;
				var sub = 0;
				if(sys_opt && !rev_over)
					sub=opt.fsize;
				var nst = sys_top+sub-max;
				if(nst > 0){
					ninja.top = nst;
					ninja.style.top = nst+"px";
				}else{
					ninja.top = 0;
					ninja.style.top = "0px";
				}
				base.style.top = base.dpos().y+base.innerHeight()+sub+"px";
			}
			var now = 0;
			var anime = new musi.AutoWork();
			anime.run = function(){
				var is_end = false;
				now+=speed;
				if(now > max){
					if(scroll < 0) scroll += now - max;
					now = max;
					is_end = true;
					enable_action = true;
				}
				base.style.height = now+"px";
				base.style.top = base.dpos().y+scroll+"px";
				return is_end;
			};
			anime.start();
		}else if(opt.anime == "fade"){
			if(opt.speed < 0) opt.speed *= -1;
			base.alpha(0);
			base.fadein(0, opt.speed, function(){
				enable_action = true;
			});
		}else{
			enable_action = true;
		}
		return release;
	};
});

(new function(){
	var DefaultOption = {
			"bcolor":new musi.Color(255,255,255),
			"lcolor":new musi.Color(150,150,150),
			"fcolor":new musi.Color(0,0,0),
			"scolor":new musi.Color(150,150,150),
			"ofcolor":new musi.Color(100,100,200),
			"obcolor":new musi.Color(230,230,255),
			"olcolor":new musi.Color(200,200,255),
			"sfcolor":new musi.Color(230,230,255),
			"sbcolor":new musi.Color(50,50,100),
			"slcolor":new musi.Color(100,100,200),
			"wday":["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
			"ym":"Y/M"
	};
	var mouseover_hdl = function(evt,opt){
		if(this.select) return;
		var css = this.style;
		try{
			css.border = "1px "+opt["olcolor"].rgba()+" solid";
			css.backgroundColor = opt["obcolor"].rgba();
			css.color = opt["ofcolor"].rgba();
		}catch(err){
			css.border = "1px "+opt["olcolor"].rgb()+" solid";
			css.backgroundColor = opt["obcolor"].rgb();
			css.color = opt["ofcolor"].rgb();
		}
	};
	var mouseout_hdl = function(evt,opt){
		if(this.select) return;
		var css = this.style;
		try{
			css.border = "1px "+opt["bcolor"].rgba()+" solid";
			css.backgroundColor = opt["bcolor"].rgba();
			if(this.inner){
				css.color = opt["fcolor"].rgba();
			}else{
				css.color = opt["scolor"].rgba();
			}
		}catch(err){
			css.border = "1px "+opt["bcolor"].rgb()+" solid";
			css.backgroundColor = opt["bcolor"].rgb();
			if(this.inner){
				css.color = opt["fcolor"].rgb();
			}else{
				css.color = opt["scolor"].rgb();
			}
		}
	};

	/**
	 * 日付設定用ボックス作成<br>
	 * 設定オブジェクト
	 * <style type="text/css">
	 * .musi_datebox_tbl th, .musi_datebox_tbl td{
	 * 	border-bottom: 1px solid #B4B4B4;
	 * 	border-right: 1px solid #B4B4B4;
	 * 	font-weight: normal;
	 * 	padding: 4px;
	 * 	vertical-align: top;
	 * }
	 * </style>
	 * <table cellspacing="0" style="border:1px #aaaaaa solid; " class="musi_datebox_tbl">
	 * <tr style="background-color:#ddccff;"><td>メンバ</td><td>設定値</td><td>詳細</td><td>デフォルト</td></tr>
	 * <tr><td>bcolor</td><td>musi.Color or String</td><td>背景色</td><td>musi.Color(255,255,255)</td>	</tr>
	 * <tr><td>lcolor</td><td>musi.Color or String</td><td>枠色</td><td>musi.Color(150,150,150)</td>	</tr>
	 * <tr><td>fcolor</td><td>musi.Color or String</td><td>文字色</td><td>musi.Color(0,0,0)</td>	</tr>
	 * <tr><td>scolor</td><td>musi.Color or String</td><td>サブ文字色</td><td>musi.Color(150,150,150)</td>	</tr>
	 * <tr><td>ofcolor</td><td>musi.Color or String</td><td>選択文字色</td><td>musi.Color(100,100,200)</td>	</tr>
	 * <tr><td>obcolor</td><td>musi.Color or String</td><td>選択背景色</td><td>musi.Color(230,230,255)</td>	</tr>
	 * <tr><td>olcolor</td><td>musi.Color or String</td><td>選択枠色</td><td>musi.Color(200,200,255)</td>	</tr>
	 * <tr><td>sfcolor</td><td>musi.Color or String</td><td>決定文字色</td><td>musi.Color(230,230,255)</td>	</tr>
	 * <tr><td>sbcolor</td><td>musi.Color or String</td><td>決定背景色</td><td>musi.Color(50,50,100)</td>	</tr>
	 * <tr><td>slcolor</td><td>musi.Color or String</td><td>決定枠色</td><td>musi.Color(100,100,200)</td>	</tr>
	 * <tr><td>wday</td><td>Array</td><td>曜日文字列 長さ7の配列</td><td>["Sun","Mon","Tue","Wed","Thu","Fri","Sat"]</td></tr>
	 * <tr><td>ym</td><td>String</td><td>年月の表示設定 Yを年にMを月に置換する</td><td>"Y/M"</td></tr>
	 * </table>
	 * @param {Element or musi.Parasite or Event} in_parent 親オブジェクトまたはイベントオブジェクト
	 * @param {Function} in_hdl 設定イベントハンドラ function(in_data{Date})の関数として呼び出す キャンセルされた場合、nullが入ってくる
	 * @param {Date} in_date 初期設定日付 nullの場合今日の日付
	 * @param {Object} in_option 設定上記リストを設定したオブジェクトを渡す。未設定項目がある場合、デフォルト値が入る。
	 * @return {Function} 終了関数 呼ぶと設定ボックスが消える
	 * @example
	 * var element = musi.getParasiteById("pupa");
	 * element.addEvent("click",function(evt){
	 * 	musi.createDateBox(evt, function(date){alert(date);},null, {
	 * 		"wday":["日","月","火","水","木","金","土"],
	 * 		"ym":"Y年M月"
	 * 	});
	 * });
	 */
	musi.createDateBox = function(in_parent, in_hdl, in_date, in_option){
		var opt = new Object();
		// オプション設定
		if(in_option != null){
			var keys=["bcolor","lcolor","fcolor","scolor","ofcolor","obcolor","olcolor","sfcolor","sbcolor","slcolor"];
			for(var i=0,len=keys.length;i<len;++i){
				var key = keys[i];
				if(in_option[key] instanceof musi.Color){
					opt[key] = in_option[key];
				}else if(musi.isString(in_option[key])){
					opt[key] = musi.Color.parse(in_option[key]);
				}
			}
			if(musi.isArray(in_option["wday"]) && in_option["wday"].length == 7){
				opt["wday"] = in_option["wday"];
			}
			if(musi.isString(in_option["ym"])){
				opt["ym"] = in_option["ym"];
			}
		}
		// デフォルト設定反映
		for(var i in DefaultOption){
			if(opt[i] == null){
				opt[i] = DefaultOption[i];
			}
		}
		if(!(in_date instanceof Date)){
			in_date = new Date();
		}
		(new function(){
			var par = in_parent;
			if(!(par instanceof Event)){
				if(par instanceof musi.Parasite){
					par = par._;
				}
				if(par == window || par == document || par == null){
					in_parent = document.getElementsByTagName("body")[0];
				}
				in_parent = musi.convertParasite(in_parent);
			}
		});

		var y = in_date.getFullYear();
		var m = in_date.getMonth()+1;
		var d = in_date.getDate();
		var hh = in_date.getHours();
		var mm = in_date.getMinutes();
		var ss = in_date.getSeconds();
		var dy = y;
		var dm = m;
		var base = new musi.Parasite("div");
		base.addEvent("mousedown",function(evt){
			evt.cancel();
			evt.stop();
		});
		var css = base.style;
		try{
			css.border = "1px "+opt["lcolor"].rgba()+" solid";
			css.backgroundColor = opt["bcolor"].rgba();
			css.color = opt["fcolor"].rgba();
			css.boxShadow = "2px 2px 3px rgba(0,0,0,0.5)";
		}catch(err){
			css.border = "1px "+opt["lcolor"].rgb()+" solid";
			css.backgroundColor = opt["bcolor"].rgb();
			css.color = opt["fcolor"].rgb();
		}
		css.fontSize = "13px";
		css.padding = "5px";
		css.margin = "0px";
		css.position = "fixed";
		css.cursor = "default";
		var z = musi.env.maxZindex();
		css.zIndex = z+2+"";
		if(in_parent instanceof Event){
			css.top = in_parent.clientY-5+"px";
			css.left = in_parent.clientX-5+"px";
		}else{
			css.top = in_parent.dpos().y+"px";
			css.left = in_parent.dpos().x+"px";
		}
		musi.getRootParasite().appendChild(base);

		var updateday = null;
		var csize = "28px";
		(new function(){
			var mode = "1m";
			
			var ymbox = new musi.Table(1,3);
			css = ymbox.style;
			css.width="100%";
			var ym = ymbox.at(0,1);

			var c = ymbox.at(0,0);
			css = c.style;
			css.borderSpacing = "0px";
			try{
				css.border = "1px "+opt["bcolor"].rgba()+" solid";
				css.backgroundColor = opt["bcolor"].rgba();
				css.color = opt["fcolor"].rgba();
			}catch(err){
				css.border = "1px "+opt["bcolor"].rgb()+" solid";
				css.backgroundColor = opt["bcolor"].rgb();
				css.color = opt["fcolor"].rgb();
			}
			css.width = csize;
			css.textAlign = "left";
			css.cursor = "pointer";
			c.html("<");
			c.inner = true;
			c.addEvent("mouseover", mouseover_hdl,opt);
			c.addEvent("mouseout", mouseout_hdl,opt);
			c.addEvent("click", function(){
				dm -= 1;
				switch(mode){
					case "1m":
						dm -= 1;
						break;
					case "1y":
						dy -= 1;
						break;
					case "10y":
						dy -= 10;
						break;
				}
				
				var dt = new Date();
				dt.setFullYear(dy);
				dt.setMonth(dm);
				dy = dt.getFullYear();
				dm = dt.getMonth()+1;
				ym.html(opt["ym"].replace(/Y/g,dy+"").replace(/M/g,dm+""));
				updateday();
			});

			c = ym;
			css = c.style;
			try{
				css.border = "1px "+opt["bcolor"].rgba()+" solid";
				css.backgroundColor = opt["bcolor"].rgba();
				css.color = opt["fcolor"].rgba();
			}catch(err){
				css.border = "1px "+opt["bcolor"].rgb()+" solid";
				css.backgroundColor = opt["bcolor"].rgb();
				css.color = opt["fcolor"].rgb();
			}
			css.cursor = "pointer";
			c.inner = true;
			c.addEvent("mouseover", mouseover_hdl,opt);
			c.addEvent("mouseout", mouseout_hdl,opt);
			c.addEvent("click", function(){
				switch(mode){
					case "1m":
						mode = "1y";
						ymbox.at(0,0).html("<<");
						ymbox.at(0,2).html(">>");
						break;
					case "1y":
						mode = "10y";
						ymbox.at(0,0).html("<<<");
						ymbox.at(0,2).html(">>>");
						break;
					case "10y":
						mode = "1m";
						ymbox.at(0,0).html("<");
						ymbox.at(0,2).html(">");
						break;
				}
			});

			css.textAlign = "center";
			c.html(opt["ym"].replace(/Y/g,y+"").replace(/M/g,m+""));

			c = ymbox.at(0,2);
			css = c.style;
			try{
				css.border = "1px "+opt["bcolor"].rgba()+" solid";
				css.backgroundColor = opt["bcolor"].rgba();
				css.color = opt["fcolor"].rgba();
			}catch(err){
				css.border = "1px "+opt["bcolor"].rgb()+" solid";
				css.backgroundColor = opt["bcolor"].rgb();
				css.color = opt["fcolor"].rgb();
			}
			css.width = csize;
			css.textAlign = "right";
			css.cursor = "pointer";
			c.html(">");
			c.inner = true;
			c.addEvent("mouseover", mouseover_hdl,opt);
			c.addEvent("mouseout", mouseout_hdl,opt);
			c.addEvent("click", function(){
				dm -= 1;
				switch(mode){
					case "1m":
						dm += 1;
						break;
					case "1y":
						dy += 1;
						break;
					case "10y":
						dy += 10;
						break;
				}
				var dt = new Date();
				dt.setFullYear(dy);
				dt.setMonth(dm);
				dy = dt.getFullYear();
				dm = dt.getMonth()+1;
				ym.html(opt["ym"].replace(/Y/g,dy+"").replace(/M/g,dm+""));
				updateday();
			});
			base.appendChild(ymbox);
		});
		var release = null;
		(new function(){
			var sato = null;
			sato = new musi.Parasite("div"); // 他の操作をさせないための保護BOX

			sato.addEvent("mousedown",function(evt){
				evt.cancel();
				evt.stop();
			});
			css = sato.style;
			css.position = "fixed";

			sato.alpha(0.001);
			css.backgroundColor = "black";
			css.left = css.right = "0px";
			css.width = musi.env.width()+"px";
			css.height = musi.env.height()+"px";
			css.top = "0px";
			css.left = "0px";
			css.zIndex = z+1+"";
			css.margin = "0px";
			css.padding = "0px";
			musi.getRootParasite().appendChild(sato);
			sato.addEvent("click", function(evt){
				evt.stop();
				release();
			});
			/** @ignore */
			release = function(apply){
				if(apply){
					var dt = new Date();
					dt.setFullYear(y);
					dt.setMonth(m-1);
					dt.setDate(d);
					dt.setHours(hh);
					dt.setMinutes(mm);
					dt.setSeconds(ss);
					dt.setMilliseconds(0);
					in_hdl(dt);
				}else{
					in_hdl(null);
				}

				base.release();
				sato.release();
			};
		});
		(new function(){
			// 日付系
			var tbl = new musi.Table(7,7);
			css = tbl.style;
			css.borderSpacing = "0px";
			for(var i=0;i<7;++i){
				var c = tbl.at(0,i);
				css = c.style;
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["fcolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["fcolor"].rgb();
				}
				css.width = csize;
				css.textAlign = "right";
				c.html(opt["wday"][i]);
			}
			base.appendChild(tbl);

			for(var r=1,ren=tbl.row;r<ren;++r)for(var col=0,cen=tbl.column;col<cen;++col){
				var c = tbl.at(r,col);
				css = c.style;
				css.textAlign = "right";
				css.width = csize;
				css.cursor = "pointer";
				c.addEvent("click",function(){
					y = this.y;
					m = this.m;
					d = this.d;
					updateday();
				});
				c.addEvent("mouseover", mouseover_hdl,opt);
				c.addEvent("mouseout", mouseout_hdl,opt);
			}

			// 時間系
			var time = new musi.Table(1,5);
			css = time.style;
			css.width = "100%";
			css.borderSpacing = "0px";
			{
				var c = time.at(0,0);
				css = c.style;
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["fcolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["fcolor"].rgb();
				}
				css.textAlign = "center";
				var select = new musi.Parasite("select");
				css = select.style;
				css.textAlign = "center";
				css.width = "100%";
				for(var i=0;i<24;++i){
					var option = new musi.Parasite("option");
					option.value(i);
					option.text(i);
					if(i == hh){
						option.attr("selected","");
					}
					select.appendChild(option);
				}
				select.addEvent("click",function(evt){
					this.func("focus");
				});
				select.addEvent("change",function(evt){
					hh = this.value();
				});
				c.appendChild(select);

				time.at(0,1).text("：");

				c = time.at(0,2);
				css = c.style;
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["fcolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["fcolor"].rgb();
				}
				css.textAlign = "center";
				select = new musi.Parasite("select");
				css = select.style;
				css.textAlign = "center";
				css.width = "100%";
				for(var i=0;i<60;++i){
					var option = new musi.Parasite("option");
					option.value(i);
					option.text(i);
					if(i == mm){
						option.attr("selected","");
					}
					select.appendChild(option);
				}
				select.addEvent("click",function(evt){
					this.func("focus");
				});
				select.addEvent("change",function(evt){
					mm = this.value();
				});
				c.appendChild(select);

				time.at(0,3).text("：");

				c = time.at(0,4);
				css = c.style;
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["fcolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["fcolor"].rgb();
				}
				css.textAlign = "center";
				select = new musi.Parasite("select");
				css = select.style;
				css.textAlign = "center";
				css.width = "100%";
				for(var i=0;i<60;++i){
					var option = new musi.Parasite("option");
					option.value(i);
					option.text(i);
					if(i == ss){
						option.attr("selected","");
					}
					select.appendChild(option);
				}
				select.addEvent("click",function(evt){
					this.func("focus");
				});
				select.addEvent("change",function(evt){
					ss = this.value();
				});
				c.appendChild(select);
			}
			base.appendChild(time);

			// コントロールボックス
			var ctrl = new musi.Table(1,3);
			css = ctrl.style;
			css.width = "100%";
			css.borderSpacing = "0px";
			{
				var c = ctrl.at(0,0);
				css = c.style;
				css.width = "33%";
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["scolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["scolor"].rgb();
				}
				css.textAlign = "center";
				c.text("CANCEL");
				c.addEvent("click",function(evt){
					release();
				});
				c.addEvent("mouseover", mouseover_hdl,opt);
				c.addEvent("mouseout", mouseout_hdl,opt);

				ctrl.at(0,1).style.width="33%";

				c = ctrl.at(0,2);
				css = c.style;
				css.width = "33%";
				try{
					css.border = "1px "+opt["bcolor"].rgba()+" solid";
					css.backgroundColor = opt["bcolor"].rgba();
					css.color = opt["scolor"].rgba();
				}catch(err){
					css.border = "1px "+opt["bcolor"].rgb()+" solid";
					css.backgroundColor = opt["bcolor"].rgb();
					css.color = opt["scolor"].rgb();
				}
				css.textAlign = "center";
				css.width = "100%";
				c.text("OK");
				c.addEvent("click",function(evt){
					release(true);
				});
				c.addEvent("mouseover", mouseover_hdl,opt);
				c.addEvent("mouseout", mouseout_hdl,opt);
			}
			base.appendChild(ctrl);

			updateday = function(){
				var dt = new Date();
				dt.setFullYear(dy);
				dt.setMonth(dm-1);
				dt.setDate(1);
				var day=1-dt.getDay();
				for(var r=1,ren=tbl.row;r<ren;++r)for(var col=0,cen=tbl.column;col<cen;++col){
					dt.setFullYear(1970);
					dt.setMonth(0);
					dt.setDate(1);
					dt.setFullYear(dy);
					dt.setMonth(dm-1);
					dt.setDate(day);

					var cy = dt.getFullYear();
					var cm = dt.getMonth()+1;
					var cd = dt.getDate();

					var c = tbl.at(r,col);
					c.select = false;
					css = c.style;
					if(y == cy && m == cm && d == cd){
						c.select = true;
						try{
							css.border = "1px "+opt["slcolor"].rgba()+" solid";
							css.backgroundColor = opt["sbcolor"].rgba();
							css.color = opt["sfcolor"].rgba();
						}catch(err){
							css.border = "1px "+opt["slcolor"].rgb()+" solid";
							css.backgroundColor = opt["sbcolor"].rgb();
							css.color = opt["sfcolor"].rgb();
						}
					}else if(dt.getDate() == day){
						c.inner = true;
						try{
							css.border = "1px "+opt["bcolor"].rgba()+" solid";
							css.backgroundColor = opt["bcolor"].rgba();
							css.color = opt["fcolor"].rgba();
						}catch(err){
							css.border = "1px "+opt["bcolor"].rgb()+" solid";
							css.backgroundColor = opt["bcolor"].rgb();
							css.color = opt["fcolor"].rgb();
						}
					}else{
						c.inner = false;
						try{
							css.border = "1px "+opt["bcolor"].rgba()+" solid";
							css.backgroundColor = opt["bcolor"].rgba();
							css.color = opt["scolor"].rgba();
						}catch(err){
							css.border = "1px "+opt["bcolor"].rgb()+" solid";
							css.backgroundColor = opt["bcolor"].rgb();
							css.color = opt["scolor"].rgb();
						}
					}
					c.html(cd);
					c.y = cy;
					c.m = cm;
					c.d = cd;
					++day;
				}
			};
		});
		updateday();
		return release;
	};
});

(new function(){
	var _MBoxList = [];
	var lock = null;
	var parent_box = null;
	musi.addLimitEvent(window, "load", function(){
		lock = new musi.Parasite("div");
		lock._.id = "musi_screen_lock";
		_appendLock(lock);
	},1);
	var _appendLock = function(lock){
		var css = lock.style;
		if(parent_box == null){
			css.position = "fixed";
			css.left = "0px";
			css.top = "0px";
			css.overflow = "hidden";
			css.display = "none";
			css.backgroundColor="rgba(0,0,0,0)";
			musi.getRootParasite().appendChild(lock);
			_inner_resizer = _full_resizer;
		}else{
			css.position = "absolute";
			css.left = "0px";
			css.top = "0px";
			css.width = "100%";
			css.height = "100%";
			css.overflow = "hidden";
			css.display = "none";
			lock.css("transition","background-color 0.5s linear 0");
			css.backgroundColor="rgba(0,0,0,0)";
			parent_box.appendChild(lock);
			_inner_resizer = function(){};
		}
	};
	var _inner_resizer = function(){};
	var _resizer = function(){
		_inner_resizer();
	};
	var _full_resizer = function(){
		if(lock == null) return;
		var css = lock.style;
		css.width=musi.env.width()+"px";
		css.height=musi.env.height()+"px";
		for(var i=0,len=_MBoxList.length;i<len;++i){
			css = _MBoxList[i].style;
			css.width=musi.env.width()+"px";
			css.height=musi.env.height()+"px";
		}
	};
	var event_index = null;
	var _screenlock=function(){
		if(lock == null) return;
		lock.style.display = "block";
		//if(_MBoxList.length > 0) return;
		lock.style.zIndex = musi.env.maxZindex()+1;
		lock.style.backgroundColor="rgba(0,0,0,0.4)";
	};
	var _screenunlock=function(){
		if(lock == null) return;
		if(_MBoxList.length > 0){
			var elem = _MBoxList[_MBoxList.length-1];
			lock.style.zIndex = Number(elem.curCss("zIndex"))-1;
			return;
		}

		lock.style.display = "none";
		lock.style.zIndex = 0;
		lock.style.backgroundColor="rgba(0,0,0,0)";
	};
	/**
	 * メッセージボックスシステムを初期化する<br>
	 * @param {Element or musi.Parasite} parent 親要素 指定しない場合、全画面
	 */
	musi.initMBox = function(parent){
		if(parent == null || parent == window){
			parent_box = null;
			return;
		}
		parent = musi.convertParasite(parent);
		if(parent == musi.getRootParasite()){
			parent_box = null;
			return;
		}
		parent_box = parent;
		if(lock == null)return;
		lock.remove();
		parent_box.appendChild(lock);
		_appendLock(lock);
	};
	/**
	 * メッセージボックスシステムを有効にする
	 * @param {Boolean} enable
	 */
	musi.enableMBox = function(enable){
		if(enable){
			if(event_index != null) return;
			event_index = musi.addEvent(window,"resize", _resizer);
		}else if(event_index != null){
			musi.delEvent(event_index);
			event_index=null;
		}
	};
	/**
	 * スクリーンの最上位にメッセージボックスを追加する
	 * @param {String or Element or musi.Parasite} box 表示するメッセージボックス
	 * @return {Object} メッセージボックス削除時に使用するキー
	 */
	musi.pushMBox = function(box){
		_screenlock();
		var locker = new musi.Parasite("div");
		var css = locker.style;
		css.position = "absolute";
		css.left = "0px";
		css.top = "0px";
		if(parent_box != null){
			css.width = "100%";
			css.height = "100%";
		}
		css.overflow = "hidden";
		css.zIndex = musi.env.maxZindex()+1;
		if(musi.isString(box))
			locker.html(box);
		else
			locker.appendChild(box);
		_MBoxList.push(locker);
		if(parent_box != null){
			parent_box.appendChild(locker);
		}else{
			musi.getRootParasite().appendChild(locker);
		}
		_resizer();
		return locker;
	};
	/**
	 * メッセージボックスを削除する
	 * @param {Object} key push時に取得したキー 指定しない場合、最上位のボックスが削除される
	 */
	musi.popMBox = function(key){
		var locker = null;
		if(key == null){
			locker = _MBoxList.pop();
		}else{
			for(var i=0,len=_MBoxList.length;i<len;++i){
				if(_MBoxList[i] == key){
					locker = _MBoxList.splice(i,1)[0];
					break;
				}
			}
		}
		if(locker == null) return;
		locker.release();
		_screenunlock();
	};
});

(new function(){

	/**
	 * @namespace ログシステム
	 */
	musi.Log = {};

	var _log_level = 0;
	/**
	 * ログレベルを設定する<br>
	 * ログウィンドウコマンド<br>
	 * 最初の文字を # にすることでコマンドを打つことができる<br>
	 * uYY: ウィンドウを上にYYピクセル移動する YYを指定しない場合、10ピクセル<br>
	 * dYY: ウィンドウを下にYYピクセル移動する YYを指定しない場合、10ピクセル<br>
	 * lXX: ウィンドウを左にXXピクセル移動する XXを指定しない場合、10ピクセル<br>
	 * rXX: ウィンドウを右にXXピクセル移動する XXを指定しない場合、10ピクセル<br>
	 * xXX: ウィンドウのX座標をXXに設定する<br>
	 * yYY: ウィンドウのY座標をYYに設定する<br>
	 * wXX: ウィンドウの幅をXXに設定する<br>
	 * hYY: ウィンドウの高さをYYに設定する<br>
	 * c: 表示をすべて消す<br>
	 * e: ウィンドウを閉じ ログレベルを1にする<br>
	 * ?: ヘルプを出す<br>
	 * @param {Number} lv 0:ログなし 1:コンソールに出す 2:ウィンドウに出す
	 * @example
	 * #u100r100w100 上に100px右に100px移動 幅を100pxに設定
	 * #dddll 下に30px左に20px移動
	 */
	musi.Log.setLevel = function(lv){
		if(!musi.isNumber(lv)){
			lv = 0;
		}
		lv = Math.floor(Number(lv));
		if(lv == _log_level) return;

		switch(lv){
			case 1:
				_log_level=1;
				_set_log_window(false);
				break;
			case 2:
				_set_log_window(true);
				_log_level=2;
				break;
			default:
				_set_log_window(false);
			_log_level=0;
			break;
		}
	};
	var _command = function(key,num){
		if(box == null || ctrl == null) return;
		switch(key){
			case "u":
				if(!musi.isNumber(num)) num = 10;
				num = Number(num);
				box.y -= num;
				ctrl.y -= num;
				move.y -= num;
				box.style.top = box.y+"px";
				ctrl.style.top = ctrl.y+"px";
				move.style.top = move.y+"px";
				break;
			case "d":
				if(!musi.isNumber(num)) num = 10;
				num = Number(num);
				box.y += num;
				ctrl.y += num;
				move.y += num;
				box.style.top = box.y+"px";
				ctrl.style.top = ctrl.y+"px";
				move.style.top = move.y+"px";
				break;
			case "l":
				if(!musi.isNumber(num)) num = 10;
				num = Number(num);
				box.x -= num;
				ctrl.x -= num;
				move.x -= num;
				box.style.left = box.x+"px";
				ctrl.style.left = ctrl.x+"px";
				move.style.left = move.x+"px";
				break;
			case "r":
				if(!musi.isNumber(num)) num = 10;
				num = Number(num);
				box.x += num;
				ctrl.x += num;
				move.x += num;
				box.style.left = box.x+"px";
				ctrl.style.left = ctrl.x+"px";
				move.style.left = move.x+"px";
				break;
			case "x":
				if(musi.isNumber(num)){
					num = Number(num);
					box.x = num;
					ctrl.x = num+20;
					move.x = num;
					box.style.left = box.x+"px";
					ctrl.style.left = ctrl.x+"px";
					move.style.left = move.x+"px";
				}
				break;
			case "y":
				if(musi.isNumber(num)){
					num = Number(num);
					box.y = num+15;
					ctrl.y = num;
					move.y = num;
					box.style.top = box.y+"px";
					ctrl.style.top = ctrl.y+"px";
					move.style.top = move.y+"px";
				}
				break;
			case "w":
				if(musi.isNumber(num)){
					num = Number(num);
					box.w = num;
					ctrl.w = num-20;
					box.style.width = box.w+"px";
					ctrl.style.width = ctrl.w+"px";
				}
				break;
			case "h":
				if(musi.isNumber(num)){
					num = Number(num);
					box.h = num;
					box.style.height = box.h+"px";
				}
				break;
			case "c":
				box.html("");
				break;
			case "e":
				musi.Log.setLevel(1);
				break;
			case "?":
				musi.Log.out("\n * 最初の文字を # にすることでコマンドを打つことができる\n * uYY: ウィンドウを上にYYピクセル移動する YYを指定しない場合、10ピクセル\n * dYY: ウィンドウを下にYYピクセル移動する YYを指定しない場合、10ピクセル\n * lXX: ウィンドウを左にXXピクセル移動する XXを指定しない場合、10ピクセル\n * rXX: ウィンドウを右にXXピクセル移動する XXを指定しない場合、10ピクセル\n * xXX: ウィンドウのX座標をXXに設定する\n * yYY: ウィンドウのY座標をYYに設定する\n * wXX: ウィンドウの幅をXXに設定する\n * hYY: ウィンドウの高さをYYに設定する\n * c: 表示をすべて消す\n * e: ウィンドウを閉じ ログレベルを1にする\n * ?: ヘルプを出す\n");
				break;
		}
	};

	var box = null;
	var ctrl = null;
	var move = null;
	var _set_log_window = function(is_enable){
		if(box != null && !is_enable){
			box.release();
			ctrl.release();
			move.release();
			box = null;
		}else if(box == null && is_enable){
			var css=null;
			box = new musi.Parasite("div");
			box._.id = "musi_upper_log";
			css = box.style;
			css.paddingTop = "5px";
			css.resize = "auto";
			css.width = "300px";
			css.height = "300px";
			css.position = "absolute";
			css.left = "0px";
			css.top = "15px";
			css.border = "1px rgb(0,255,214) solid";
			css.backgroundColor = "rgba(0,0,0,0.6)";
			css.color = "white";
			css.overflow = "auto";
			css.webkitOverflowScrolling = "touch";
			box.w = 300;
			box.h = 300;
			box.x = 0;
			box.y = 15;

			musi.getRootParasite().appendChild(box);
			ctrl = new musi.Parasite("input");
			ctrl._.type = "text";
			css = ctrl.style;
			css.width = "280px";
			css.height = "12px";
			css.position = "absolute";
			css.left = "20px";
			css.top = "0px";
			css.border = "1px gray solid";
			css.color = "black";
			ctrl.w = 300;
			ctrl.x = 20;
			ctrl.y = 0;
			ctrl.addEvent("keypress",function(evt){
				if(evt.keyCode == musi.PRESSKEY.Enter){
					var v = ctrl.value();
					if((v+"")[0] == "#"){
						v = (v+"").replace(/\s/g,"");
						var _pre = null;
						var _num = null;
						for(var i=1,len=v.length;i<len;++i){
							if(v[i].match(/[0-9]/) != null){
								if(_num == null) _num=0;
								_num *= 10;
								_num += Number(v[i]);
							}else{
								_command(_pre,_num);
								_pre = v[i];
								_num = null;
							}
						}
						_command(_pre,_num);
					}else{
						try{
							var ret = eval(v);
							musi.Log.out(ret);
						}catch(e){
							musi.Log.out(e,true);
						}
					}
					ctrl.value("");
					evt.cancel();
				}
			});
			musi.getRootParasite().appendChild(ctrl);
			move = new musi.Parasite("div");
			css = move.style;
			css.width = "15px";
			css.height = "15px";
			css.position = "absolute";
			css.left = "0px";
			css.top = "0px";
			css.border = "1px green solid";
			css.color = "black";
			css.backgroundColor = "white";
			css.textAlign = "center";
			css.lineHeight = "15px";
			css.userSelect = css.webkitUserSelect = css.msUserSelect = css.mozUserSelect = css.oUserSelect = "none";
			css.cursor = "move";
			move.text("╋");
			move.x = 0;
			move.y = 0;
			move.on = false;
			move.addEvent("mousedown", function(evt){
				move.on = true;
				evt.cancel();
			});
			move.ev1 = musi.addEvent(window,"mousemove",function(evt){
				if(move.on){
					_command("x",evt.pageX);
					_command("y",evt.pageY);
					evt.cancel();
				}
			});
			move.ev2 = musi.addEvent(window,"mouseup",function(evt){
				move.on = false;
			});
			move.addEvent("release",function(){
				musi.delEvent(move.ev1);
				musi.delEvent(move.ev2);
			});

			musi.getRootParasite().appendChild(move);
		}
		if(box != null){
			box.style.zIndex = musi.env.maxZindex()+"";
		}
	};
	var __openobject = function(obj,num){
		var marker = "";
		switch(num%3){
			case 0:
				marker = "list-style-type:disc;";
				break;
			case 1:
				marker = "list-style-type:circle;";
				break;
			default:
				marker = "list-style-type:square;";
			break;
		}
		var ret = "";
		if(musi.isArray(obj)){
			ret += "[<ul style='margin-left:-2.5em;list-style-position:inside;"+marker+"'>";
			for(var i=0,len=obj.length;i<len;++i){
				var elem = obj[i];
				var cl = __openobject(elem,num+1);
				ret += "<li>"+i+":";
				for(var j=0,jen=cl.length;j<jen;++j){
					ret += cl[j];
				}
				ret += "</li>";
			}
			ret += "</ul>]";
		}else if(musi.isString(obj)){
			ret += '"'+obj.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")+'"';
		}else if(musi.isObject(obj)){
			ret+="object{<ul style='margin-left:-2.5em;list-style-position:inside;"+marker+"'>";
			for(var i in obj){
				var elem = obj[i];
				var cl = __openobject(elem,num+1);
				ret += "<li>"+i+":";
				for(var j=0,jen=cl.length;j<jen;++j){
					ret += cl[j];
				}
				ret += "</li>";
			}
			ret+="</ul>}";
		}else {
			ret +=(obj+"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
		}
		return ret;
	};
	var _has_console = console != null && musi.isFunction(console.log) && musi.isFunction(console.error);
	/**
	 * ログを出力する
	 * @param {String} mes 出力するログ
	 * @param {Boolean} err エラーフラグ
	 */
	musi.Log.out = function(mes,err){
		switch(_log_level){
			case 2:
				if(box == null)	return;
				var line = new musi.Parasite("div");
				line.html("<pre>"+__openobject(mes,0).replace(/\r\n/g,"\n").replace(/\n/g,"<br>").replace(/\t/g,"  ")+"</pre>");
				if(err)
					line.style.color = "#ff9999";
				line.style.borderBottom = "1px solid gray";
				box.appendChild(line);
				box._.scrollTop += 10000;
			case 1:
				if(_has_console){
					if(err){
						console.error(mes);
					}else{
						console.log(mes);
					}
				}
			default:
				break;
		}
	};
});
(new function(){
	/**
	 * @namespace musi.adjustBoxのtypeを列挙型
	 */
	musi.ABT = {};
	/**
	 * そのまま拡縮
	 * @constant
	 * @type Number
	 */
	musi.ABT.SCALE = 0;
	/**
	 * アスペクト比を維持し最大まで拡縮
	 * @constant
	 * @type Number
	 */
	musi.ABT.LETTER = 1;
	/**
	 * アスペクト比を維持し最大まで拡縮し、足りない分を伸ばす
	 * @constant
	 * @type Number
	 */
	musi.ABT.SCLFIT = 2;
	/**
	 * 指定の要素を親の要素と同じ大きさに拡縮する
	 * @param {Element or musi.Parasite} target 拡縮する子要素
	 * @param {musi.Vec2} size targetの元のサイズ
	 * @param {musi.Vec2} asize targetの拡縮後サイズ
	 * @param {Number} type {@link musi.ABT}の値
	 * @param {Boolean} rotation trueの場合、親が縦長や横長になった時、sizeもそれに合わせて縦長、横長にする。また、tate,yokoクラスを付け替える
	 */
	musi.adjustBox = function(target,size,asize,type,rotation){
		target = musi.convertParasite(target);
		var w = asize.x;
		var h = asize.y;
		var bw = size.x;
		var bh = size.y;
		if(rotation && (w > h && bh > bw || w < h && bh < bw)){
			bw = size.y;
			bh = size.x;
		}
		if(rotation){
			var is_yoko = w > h;
			var list = target.parasites(".tate,.yoko");
			for(var i=0,len=list.length;i<len;++i){
				var e = list[i];
				if(is_yoko){
					e.delClass("tate");
					e.addClass("yoko");
				}else{
					e.delClass("yoko");
					e.addClass("tate");
				}
			}
		}
		var sw = w/bw;
		var sh = h/bh;
		var css = target.style;
		switch(type){
			case musi.ABT.LETTER:{
				var sub = new musi.Vec2(0,0);
				if(sw > sh){
					sw = sh;
					sub.set((w-sw*bw)/2,0);
				}else{
					sh = sw;
					sub.set(0,(h-sh*bh)/2);
				}
				css.top = sub.y+"px";
				css.left = sub.x+"px";

				target.css("transformOrigin","0 0");
				css.width = bw+"px";
				css.height = bh+"px";
				target.css("transform","scale("+sw+","+sh+")");
				break;
			}
			case musi.ABT.SCLFIT:{
				var s = 1;
				var iw=bw;
				var ih=bh;
				if(sw > sh){
					s = sh;
					iw = bh*w/h;
				}else{
					ih=bw*h/w;
					s = sw;
				}
				target.css("transformOrigin","0 0");
				css.left = "0px";
				css.top = "0px";
				css.width = iw+"px";
				css.height = ih+"px";
				target.css("transform","scale("+s+","+s+")");
				break;
			}
			case musi.ABT.SCALE:
			default:{
				target.css("transformOrigin","0 0");
				css.left = "0px";
				css.top = "0px";
				css.width = bw+"px";
				css.height = bh+"px";
				target.css("transform","scale("+sw+","+sh+")");
				break;
			}
		}
	};
});





/**
 * @class AppCache時に使用するハンドラクラス
 */
musi.AppcacheHandler = function (){};
/**
 * キャッシュ完了時<br>
 * オーバーライドして使用
 */
musi.AppcacheHandler.prototype.onCached = function(){};
/**
 * エラー時<br>
 * オーバーライドして使用
 */
musi.AppcacheHandler.prototype.onError = function(){};
/**
 * キャッシング時<br>
 * オーバーライドして使用
 * @param {Number} loaded ロード済みファイル数
 * @param {Number} total 全ファイル数 nullの場合ファイル数を取得できていない
 */
musi.AppcacheHandler.prototype.onCaching = function(loaded, total){};
/**
 * キャッシュをリロードするか判定<br>
 * オーバーライドして使用
 * @return {Boolean} trueならば、キャッシュを更新するを行う
 */
musi.AppcacheHandler.prototype.isRefresh = function(){return true;};

/**
 * @param {musi.AppcacheHandler} hdl APPキャッシュを行うためのハンドラ
 */
musi.appCache = function(hdl){
	if(hdl == null) musi.Log.out("musi.appCache invalid value.",true);
	var ac = window.applicationCache;

	var _cache_ok = function(){
		hdl.onCached();
	};
	var _cache_err = function(){
		hdl.onError();
	};

	ac.addEventListener('progress', function(evt){
		hdl.onCaching(evt.loaded,evt.total);
	}, false);
	ac.addEventListener('cached', _cache_ok, false);
	ac.addEventListener('error', _cache_err, false);
	ac.addEventListener('noupdate', _cache_ok, false);
	ac.addEventListener('obsolete', _cache_ok, false);
	ac.addEventListener('updateready', function(){
		if(hdl.isRefresh()){
			ac.swapCache();
			location.reload();
		}
	}, false);
	musi.addLimitEvent(window,"load",function(){
		try{
			ac.update();
		}catch(e){}
	},1);
};
