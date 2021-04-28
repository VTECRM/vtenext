/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@106857 crmv@118977 */

function vtenextwebservicesproto(){
	var $ = jQuery;
	var skip_tablefields_columns = ['seq','parent_id','assigned_user_id','createdtime','modifiedtime'];
	
	function md5(str){
		return hex_md5(str);
	}

	function mergeObjects(obj1, obj2){
		var res = {};
		for(var k in obj1){
			res[k] = obj1[k];
		}
		for(var k in obj2){
			res[k] = obj2[k];
		}
		return res;
	}

	var cacheGet = {};
	function doGet(params, callback){
		if (this.cacheRequests) {
			var cache_key = btoa($.param(params));
			if(!cacheGet[cache_key]) cacheGet[cache_key] = $.get(this.serviceUrl, params);
			cacheGet[cache_key].done(callback);
		} else {
			$.get(this.serviceUrl, params, function(result){
				callback(result);
			});
		}
	}

	var cachePost = {};
	function doPost(params, callback){
		if (this.cacheRequests) {
			var cache_key = btoa($.param(params));
			if(!cachePost[cache_key]) cachePost[cache_key] = $.post(this.serviceUrl, params);
			cachePost[cache_key].done(callback);
		} else {
			$.post(this.serviceUrl, params, function(result){
				callback(result);
			});
		}
	}

	function get(operation, parameters, callback){
		response = this.doGet(mergeObjects(parameters,
			{'operation':operation, 'sessionName':this.sessionId}), function(response){
			if(response['success']==true){
				callback(true,response['result']);
			}else{
				callback(false,response['error']);
			}
		});
	}

	function post(operation, parameters, callback){
		response = this.doPost(mergeObjects(parameters,
			{'operation':operation, 'sessionName':this.sessionId}), function(response){
			if(response['success']==true){
				callback(true,response['result']);
			}else{
				callback(false,response['error']);
			}
		});
	}


	function login(callback){
		var self = this;
		response = this.doGet({operation:'getchallenge', username:this.username}, function(response){
			if(response['success']==true){
				var token = response['result']['token'];
				var encodedKey = md5(token+self.accessKey);
				self.doPost({operation:'login', username: self.username, accessKey: encodedKey}, function (response){
					if(response['success']==true){
						self.sessionId = response['result']['sessionName'];
						self.userId = response['result']['userId'];
						callback(true);
					}else{
						callback(false,response['error']);
					}
				});
			}else{
				callback(false,response['error']);
			}
		});
	}


	function logout(callback){
		this.post('logout', {}, callback);
	}

	function listTypes(callback){
		this.get('listtypes', {}, function (status, result){
			if(status){
				callback(true, result['types']);
			}else{
				callback(false, result);
			}
		});
	}

	var describeObjectModLightDone = {}; //crmv@186085
	function describeObject(name, callback){
		var me = this;
		//crmv@120039
		var operation = 'describe';
		if (me.show_describe_hidden_fields) operation = 'describe_all';
		
		me.get(operation, {'elementType':name}, function(statusOk, result) {
		//crmv@120039e
			if (statusOk && result && result.fields) {
				// ok
				//crmv@153321_5
				var table_fields_count = 0;
				for (var i=0; i<result.fields.length; ++i) {
					if (result.fields[i].type.name == 'table') table_fields_count++;
				}
				// if there are not table fields call the callback function immediately otherwise call it after all the ajax calls for modlights
				if (table_fields_count == 0) {
					callback(statusOk, result);
				} else {
					var fields = [];
					var j = 0;
					for (var i=0; i<result.fields.length; ++i) {
						fields.push(result.fields[i]);
						if (result.fields[i].type.name == 'table') {
							j++;
							var fieldlabel = result.fields[i].label;
							var fieldname = result.fields[i].name;
							var v = 'ModLight'+fieldname.substr(2);
							//crmv@186085
							if (typeof(describeObjectModLightDone[v]) == 'undefined') {
								describeObjectModLightDone[v] = 1;
								describeObjectModLight(fieldname, fieldlabel, v, fields, function(){
									table_fields_count--;
								});
							} else {
								table_fields_count--;
							}
							//crmv@186085e
						}
					}
					function checkLastCallback() {
						setTimeout(function(){
							if (table_fields_count == 0) {
								result.fields = fields;
								callback(statusOk, result);
							} else {
								checkLastCallback();
							}
						},100);
					}
					checkLastCallback();
				}
				//crmv@153321_5e
			} else {
				// error
				callback(statusOk, result);
			}
		});
		
		function describeObjectModLight(fieldname, fieldlabel, module, fields, callback) {
			me.describeObject(module, function(statusML, resultML) {
				if (statusML && resultML && resultML.fields) {
					// ok
					resultML.fields = resultML.fields.filter(function(v,k){
						return (skip_tablefields_columns.indexOf(v['name']) == -1);
					});
					jQuery.each(resultML.fields,function(tk,column) {
						column.label = fieldlabel+': '+column.label;
						column.name = fieldname+'::'+column.name;
						fields.push(column);
					});
				}
				if (typeof callback == 'function') callback();
			});	
		}
	}
	
	function create(object, objectType, callback){
		if(object['assigned_user_id']==null){
			object['assigned_user_id'] = this.userId;
		}
		objectJson = JSON.encode(object);
		this.post('create', {'elementType':objectType,
			'element':objectJson}, callback);
	}

	function retrieve(id, callback){
		this.get('retrieve', {'id':id}, callback);
	}

	function update(object, callback){
		objectJson = JSON.encode(object);
		this.post('update', {'element':objectJson}, callback);
	}


	function deleteObject(id, callback){
		this.post('delete', {'id':id}, callback);
	}

	function query(query, callback){
		this.get('query', {'query':query}, callback);
	}

	function extendSession(callback){
		var self = this;
		this.doPost({operation: 'extendsession'}, function(response){
			var status = response['success'];
			var result = response['result'];
			if(status==true){
				self.sessionId = result['sessionName'];
				self.userId = result['userId'];
				callback(true, result);
			}else{
				callback(false, result);
			}

		});
	}
	
	//crmv@96450 crmv@115268
	function describeDynaForm(processmakerid, metaid, options, callback){
		// TODO put callbak in another function
		this.get('dynaform_describe', {'processmakerid':processmakerid,'metaid':metaid,'options':{}}, function(statusOk, result) {	// TODO pass options
			if (statusOk && result && result.fields) {
				// ok
				var fields = [];
				for (var i=0; i<result.fields.length; ++i) {
					fields.push(result.fields[i]);
					if (result.fields[i].type.name == 'table' && result.fields[i].columns) {
						// table field in dynaform
						jQuery.each(result.fields[i].columns,function(tk,column) {
							//crmv@186085
							if (typeof(options['cycle']) != 'undefined' && options['cycle']) {
								// do not prepend parent field
								fields.push(column);
							} else if (column.name.indexOf('::') == -1) {
								column.label = result.fields[i].label+': '+column.label;
								column.name = result.fields[i].name+'::'+column.name;
								fields.push(column);
							}
							//crmv@186085e
						});
					}
				}
				result.fields = fields;
				callback(statusOk, result);
			} else {
				// error
				callback(statusOk, result);
			}
		});
	}
	//crmv@96450e crmv@115268e
	
	// crmv@104180
	function describeTableField(processmakerid, metaid, fieldname, options, callback){
		if (fieldname.indexOf("ml") == -1) {
			this.describeDynaForm(processmakerid, metaid,  options, function(statusOk, result) {
				if (statusOk && result && result.fields) {
					// ok
					for (var i=0; i<result.fields.length; ++i) {
						if (result.fields[i].name == fieldname && result.fields[i].columns) {
							result.name = 'TableField';
							result.fields = result.fields[i].columns;
							break;
						}
					}
					callback(statusOk, result);
				} else {
					// error
					callback(statusOk, result);
				}
			});
		} else {
			var v = 'ModLight'+fieldname.substr(2);
			this.describeObject(v, function(statusOk, result) {
				if (statusOk && result && result.fields) {
					// ok
					result.fields = result.fields.filter(function(v,k){
						return (skip_tablefields_columns.indexOf(v['name']) == -1);
					});
					result.name = 'TableField';
					callback(statusOk, result);
				} else {
					// error
					callback(statusOk, result);
				}
			});
		}
	}
	// crmv@104180e

	return {
		doPost:doPost, doGet:doGet,
		get:get, post:post,
		login:login, logout:logout,
		listTypes:listTypes, describeObject:describeObject,
		create:create, retrieve:retrieve, update:update, deleteObject:deleteObject,
		query:query, extendSession: extendSession,
		describeDynaForm:describeDynaForm,	//crmv@96450
		describeTableField:describeTableField	//crmv@104180
	}
}

function VtenextWebservices(serviceUrl, username, accessKey, cacheRequests, show_describe_hidden_fields){	//crmv@120039
	this.serviceUrl = serviceUrl;
	this.username = username;
	this.accessKey = accessKey;
	if (typeof(cacheRequests) == 'undefined') this.cacheRequests = false; else this.cacheRequests = cacheRequests;
	if (typeof(show_describe_hidden_fields) == 'undefined') this.show_describe_hidden_fields = false; else this.show_describe_hidden_fields = show_describe_hidden_fields;	//crmv@120039
}
VtenextWebservices.prototype = vtenextwebservicesproto();