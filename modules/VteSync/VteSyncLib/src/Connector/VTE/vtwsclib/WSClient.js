var VTE_WSClient = function(url) {//crmv@208028
	this._servicebase = 'webservice.php';
	// TODO: Format the url before appending servicebase
	url = url + '/';

	this._serviceurl = url + this._servicebase;

	// Webservice user credentials
	this._serviceuser= false;
	this._servicekey = false;

	// Webservice login validity
	this._servertime = false;
	this._expiretime = false;
	this._servicetoken=false;

	// Webservice login credentials
	this._sessionid  = false;
	this._userid     = false;

	// Last operation error information
	this._lasterror  = false;

	/**
	 * JSONify input data.
	 */
	this.toJSON = function(input) {
		return JSON.parse(input);
    };

	/**
	 * Get actual record id from the response id.
	 */
	this.getRecordId = function(id) {
		var ids = id.split('x');
		return ids[1];
	};

	/**
	 * Convert to JSON String.
	 */
	this.toJSONString = function(input) {
		return JSON.stringify(input);
	}

	/**
	 * Check if result has any error.
	 */
	this.hasError = function(resultdata) {
		if (resultdata != null && resultdata['success'] == false) {
			this._lasterror = resultdata['error'];
			return true;
		}
		this._lasterror = false;
		return false;
	}

	/**
	 * Get last operation error information
	 */
	this.lastError = function() {
		return this._lasterror;
	}

	/**
	 * Perform the callback now.
	 */
	this.__performCallback = function(callback, result) {
		if(callback) {
			var callbackFunction = callback;
			var callbackArguments = false;
			if(typeof(callback) == 'object') {
				callbackFunction = callback['function'];
				callbackArguments = callback['arguments'];
			}
			if(typeof(callbackFunction) == 'function') {
				callbackFunction(result, callbackArguments);
			}
		}
	}

	/**
	 * Perform the challenge
	 * @access private
	 */
	this.__doChallenge = function(username) {
		var reqtype = 'GET';
		var getdata = {
			'operation' : 'getchallenge',
			'username'  : username
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: getdata,
			// We have to do this in sync manner
			async: false,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				if(usethis.hasError(resobj) == false) {
					var result = resobj['result'];
					usethis._servicetoken = result.token;
					usethis._servertime = result.serverTime;
					usethis._expiretime = result.expireTime;
				}
			}
		});
	}

	/**
	 * Check and perform login if requried.
	 */
	this.__checkLogin = function() {
		return true;
	}

	/**
	 * Do Login Operation
	 */
	this.doLogin = function(username, accesskey, callback) {
		this.__doChallenge(username);
		if(this._servicetoken == false) {
			// TODO: Failed to get the service token
			return false;
		}

		this._serviceuser = username;
		this._servicekey  = accesskey;

		var reqtype = 'POST';
		var postdata = {
			'operation' : 'login',
			'username'  : username,
			'accessKey' : hex_md5(this._servicetoken + accesskey)
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: postdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var resflag = false;
				if(usethis.hasError(resobj) == false) {
					var result = resobj['result'];
					usethis._sessionid  = result.sessionName;
					usethis._userid = result.userId;
					resflag = true;
				}
				usethis.__performCallback(callback, resflag);
			}
		});
	};

	/**
	 * Do Query Operation.
	 */
	this.doQuery = function(query, callback) {
		this.__checkLogin();

		// TODO: Append ; if not found
		if(query.indexOf(';') == -1) query += ';';

		var reqtype = 'GET';
		var getdata = {
			'operation'    : 'query',
			'sessionName'  : this._sessionid,
			'query'        : query
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: getdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var result = false;
				if(usethis.hasError(resobj) == false) {
					result = resobj['result'];
				}
				usethis.__performCallback(callback, result);
			}
		});
	};

	/**
	 * Get Result Column Names.
	 */
	this.getResultColumns = function(result) {
		var columns = [];
		if(result != null && result.length != 0) {
			var firstrecord = result[0];
			for(key in firstrecord) {
				columns.push(key);
			}
		}
		return columns;
	};

	/**
	 * List types (modules) available.
	 */
	this.doListTypes = function(callback) {
		this.__checkLogin();

		var reqtype = 'GET';
		var getdata = {
			'operation'    : 'listtypes',
			'sessionName'  : this._sessionid
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: getdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var returnvalue = false;
				if(usethis.hasError(resobj) == false) {
					var result = resobj['result'];
					var modulenames = result['types'];

					returnvalue = { };
					for(var mindex = 0; mindex < modulenames.length; ++mindex) {
						var modulename = modulenames[mindex];
						returnvalue[modulename] = {
							'name'     : modulename
						}
					}
				}
				usethis.__performCallback(callback, returnvalue);
			}
		});
	};

	/**
	 * Do Describe Operation
	 */
	this.doDescribe = function(module, callback) {
		this.__checkLogin();

		var reqtype = 'GET';
		var getdata = {
			'operation'    : 'describe',
			'sessionName'  : this._sessionid,
			'elementType'  : module
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: getdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var result = false;
				if(!usethis.hasError(resobj)) result = resobj['result'];
				usethis.__performCallback(callback, result);
			}
		});
	};

	/**
	 * Retrieve details of record
	 */
	this.doRetrieve = function(record, callback) {
		this.__checkLogin();

		var reqtype = 'GET';
		var getdata = {
			'operation'    : 'retrieve',
			'sessionName'  : this._sessionid,
			'id'           : record
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: getdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var result = false;
				if(!usethis.hasError(resobj)) result = resobj['result'];
				usethis.__performCallback(callback, result);
			}
		});
	};

	/**
	 * Do Create Operation
	 */
	this.doCreate = function(module, valuemap, callback) {
		this.__checkLogin();

		// Assign record to logged in user if not specified
		if(valuemap['assigned_user_id'] == null) {
			valuemap['assigned_user_id'] = this._userid;
		}

		var reqtype = 'POST';
		var postdata = {
			'operation'    : 'create',
			'sessionName'  : this._sessionid,
			'elementType'  : module,
			'element'      : this.toJSONString(valuemap)
		};
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: postdata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete : function(res, status) {
				var usethis = this._wsclient;
				var resobj = usethis.toJSON(res.responseText);
				var result = false;
				if(!usethis.hasError(resobj)) result = resobj['result'];
				usethis.__performCallback(callback, result);
			}
		});
	};

	/**
	 * Invoke custom operation
	 */
	this.doInvoke = function(callback, method, params, type) {
		this.__checkLogin();

		if(typeof(params) == 'undefined') params = {};

		var reqtype = 'POST';
		if(typeof(type) != 'undefined') reqtype = type.toUpperCase();

		var sendata = {
			'operation' : method,
			'sessionName' : this._sessionid,
		};
		for(key in params) {
			if(typeof(sendata[key]) == 'undefined') {
				sendata[key] = params[key];
			}
		}
		jQuery.ajax({
			url : this._serviceurl,
			type: reqtype,
			data: sendata,
			// Pass reference to the client to use it inside callback function.
			_wsclient : this,
			complete  : function(res, status) {
				var usethis = this._wsclient;
				var resobj  = usethis.toJSON(res.responseText);
				var result  = false;
				if(!usethis.hasError(resobj)) result = resobj['result'];
				usethis.__performCallback(callback, result);
			}
		});
	};
};

/*******************************************************************************
 * JSON Functions are defined below to make this script to work independently. *
 *******************************************************************************/
/*
    http://www.JSON.org/json2.js    2008-09-01
    Public Domain.
    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
    See http://www.JSON.org/js.html

	Original File:  http://www.json.org/json2.js
	Minified Using: http://fmarcia.info/jsmin/test.html (Level: agressive)
*/
if(!this.JSON){JSON={};}(function(){function f(n){return n<10?'0'+n:n;}if(typeof Date.prototype.toJSON!=='function'){Date.prototype.toJSON=function(key){return this.getUTCFullYear()+'-'+f(this.getUTCMonth()+1)+'-'+f(this.getUTCDate())+'T'+f(this.getUTCHours())+':'+f(this.getUTCMinutes())+':'+f(this.getUTCSeconds())+'Z';};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf();};}var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapeable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},rep;function quote(string){escapeable.lastIndex=0;return escapeable.test(string)?'"'+string.replace(escapeable,function(a){var c=meta[a];if(typeof c==='string'){return c;}return'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'
+string+'"';}function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==='object'&&typeof value.toJSON==='function'){value=value.toJSON(key);}if(typeof rep==='function'){value=rep.call(holder,key,value);}switch(typeof value){case'string':return quote(value);case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}gap+=indent;partial=[];if(typeof value.length==='number'&&!value.propertyIsEnumerable('length')){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||'null';}v=partial.length===0?'[]':gap?'[\n'+gap+partial.join(',\n'+gap)+'\n'+mind+']':'['+partial.join(',')+']';gap=mind;return v;}if(rep&&typeof rep==='object'){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==='string'){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':'
)+v);}}}}v=partial.length===0?'{}':gap?'{\n'+gap+partial.join(',\n'+gap)+'\n'+mind+'}':'{'+partial.join(',')+'}';gap=mind;return v;}}if(typeof JSON.stringify!=='function'){JSON.stringify=function(value,replacer,space){var i;gap='';indent='';if(typeof space==='number'){for(i=0;i<space;i+=1){indent+=' ';}}else if(typeof space==='string'){indent=space;}rep=replacer;if(replacer&&typeof replacer!=='function'&&(typeof replacer!=='object'||typeof replacer.length!=='number')){throw new Error('JSON.stringify');}return str('',{'':value});};}if(typeof JSON.parse!=='function'){JSON.parse=function(text,reviver){var j;function walk(holder,key){var k,v,value=holder[key];if(value&&typeof value==='object'){for(k in value){if(Object.hasOwnProperty.call(value,k)){v=walk(value,k);if(v!==undefined){value[k]=v;}else{delete value[k];}}}}return reviver.call(holder,key,value);}cx.lastIndex=0;if(cx.test(text)){text=text.replace(cx,function(a){return'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);});}if(/^[\],:{}\s]*$/.test(text.
replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,''))){j=eval('('+text+')');return typeof reviver==='function'?walk({'':j},''):j;}throw new SyntaxError('JSON.parse');};}})();
