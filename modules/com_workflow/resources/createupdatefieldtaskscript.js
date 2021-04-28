/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@18199
function createupdatefieldtaskscript($,fieldName,fieldValue){
	var vtinst = new VtenextWebservices("webservice.php");
	var fieldValidator;
	var desc = null;

	function id(v){
		return v;
	}

	function map(fn, list){
		var out = [];
		$.each(list, function(i, v){
			out[out.length]=fn(v);
		});
		return out;
	}

	function field(name){
		return function(object){
			if(typeof(object) != 'undefined') {
				return object[name];
			}
		};
	}

	function zip(){
		var out = [];

		var lengths = map(field('length'), arguments);
		var min = reduceR(function(a,b){return a<b?a:b;},lengths,lengths[0]);
		for(var i=0; i<min; i++){
			out[i]=map(field(i), arguments);
		}
		return out;
	}

	function dict(list){
		var out = {};
		$.each(list, function(i, v){
			out[v[0]] = v[1];
		});
		return out;
	}

	function filter(pred, list){
		var out = [];
		$.each(list, function(i, v){
			if(pred(v)){
				out[out.length]=v;
			}
		});
		return out;
	}
	
	function diff(reflist, list) {
		var out = [];
		$.each(list, function(i, v) {
			if(contains(reflist, v)) {
				out.push(v);
			}
		});
		return out;
	}


	function reduceR(fn, list, start){
		var acc = start;
		$.each(list, function(i, v){
			acc = fn(acc, v);
		});
		return acc;
	}

	function contains(list, value){
		var ans = false;
		$.each(list, function(i, v){
			if(v==value){
				ans = true;
				return false;
			}
		});
		return ans;
	}

	function concat(lista,listb){
		return lista.concat(listb);
	}

	function errorDialog(message){
		alert(message);
	}

	function handleError(fn){
		return function(status, result){
			if(status){
				fn(result);
			}else{
				errorDialog('Failure:'+result);
			}
		};
	}

	function implode(sep, arr){
		var out = "";
		$.each(arr, function(i, v){
			out+=v;
			if(i<arr.length-1){
				out+=sep;
			}
		});
		return out;
	}
	
	function getFile(url) {	//crmv@18199 : add User field
	  if (window.XMLHttpRequest) {              
	    AJAX=new XMLHttpRequest();              
	  } else {                                  
	    AJAX=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	  if (AJAX) {
	     AJAX.open("GET", url, false);                             
	     AJAX.send(null);
	     return AJAX.responseText;                                         
	  } else {
	     return false;
	  }                                             
	}

	function defaultValue(fieldType){

		function forOwner(opType){	//crmv@18199 : add User field
			var value = $("#fieldValue");
			result = getFile('index.php?module=com_workflow&action=com_workflowAjax&file=getownerfieldjson');//crmv@207901
			result = eval('('+result+')');
			var options = implode('',
				map(function (e){return '<option value="'+e.value+'">'+e.label+'</option>';},
					result['picklistValues'])
			);
			value.replaceWith('<select id="fieldValue" name="fieldValue" class="detailedViewTextBox">'+options+'</select>');
		}
		function forPicklist(opType){
			var value = $("#fieldValue");
			var options = implode('',
				map(function (e){return '<option value="'+e.value+'">'+e.label+'</option>';},
					opType['picklistValues'])
			);
			value.replaceWith('<select id="fieldValue" name="fieldValue" class="detailedViewTextBox">'+
												options+'</select>');
		}
		function forInteger(opType){
			var value = $("#fieldValue");
			value.replaceWith('<input type="text" id="fieldValue" name="fieldValue" '+
															 'value="0" class="detailedViewTextBox">');
		}
		var functions = {
			string:function(opType){
				var value = $("#fieldValue");
				value.replaceWith('<input type="text" id="fieldValue" name="fieldValue" '+
																 'value="" class="detailedViewTextBox">');
			},
			'boolean': function(opType){
				var value = $("#fieldValue");
				value.replaceWith(
					'<select id="fieldValue" name="fieldValue" value="true" class="detailedViewTextBox"> \
						<option value="1">True</option>\
						<option value="0">False</option>\
					</select>');
			},
			integer: forInteger,
			picklist:forPicklist,
			multipicklist:forPicklist,
			picklistmultilanguage:forPicklist, // crmv@118315
			owner: forOwner	//crmv@18199 : add User field
		};
		var ret = functions[fieldType];
		if(ret==null){
			ret = functions['string'];
		}
		return ret;
	}

	function fillOptions(el,options){
		el.empty();
		$.each(options, function(k, v){
			el.append('<option value="'+k+'">'+v+'</option>');
		});
	}

	function resetFields(opType){
		defaultValue(opType.name)(opType);
	}

	//Convert user type into reference for consistency in describe objects
	//This is done inplace
	function referencify(desc){
	  var fields = desc['fields'];
	  for(var i=0; i<fields.length; i++){
		var field = fields[i];
		var type = field['type'];
		//crmv@18199 : add User field
		/*
		if(type['name']=='owner'){
		  type['name']='reference';
		  type['refersTo']=['Users'];
		}
		*/
	  }
	  return desc;
	}

	function getDescribeObjects(accessibleModules, moduleName, callback){
		vtinst.describeObject(moduleName, handleError(function(result){
			var parent = referencify(result);
			var fields = parent['fields'];
			var referenceFields = filter(function(e){return e['type']['name']=='reference';}, fields);
			var referenceFieldModules =
				map(function(e){ return e['type']['refersTo'];},
					referenceFields
				);
			function union(a, b){
				var newfields = filter(function(e){return !contains(a, e);}, b);
				return a.concat(newfields);
			}
			var relatedModules = reduceR(union, referenceFieldModules, [parent['name']]);
			
			// Remove modules that is no longer accessible
			relatedModules = diff(accessibleModules, relatedModules);
			
			function executer(parameters){
				var failures = filter(function(e){return e[0]==false;}, parameters);
				if(failures.length!=0){
					var firstFailure = failures[0];
					callback(false, firstFailure[1]);
				}else{
				  var moduleDescriptions = map(function(e){return e[1];}, parameters);
					var modules = dict(map(function(e){return [e['name'], referencify(e)];}, moduleDescriptions));
					callback(true, modules);
				}
			}
			var p = parallelExecuter(executer, relatedModules.length);
			$.each(relatedModules, function(i, v){
				p(function(callback){vtinst.describeObject(v, callback);});
			});
		}));
	}

	$(document).ready(function(){
		fieldValidator = new VTFieldValidator($('#edit_workflow_form'));
		fieldValidator.mandatoryFields = ["description"];
		vtinst.extendSession(handleError(function(result){
			vtinst.listTypes(handleError(function(accessibleModules) {
				accessibleModules.splice(accessibleModules.indexOf('Users'), 1);	//tolgo il modulo Users dalla lista dei moduli disponibili
				getDescribeObjects(accessibleModules, moduleName, handleError(function(modules){
					var parent = modules[moduleName];
					function filteredFields(fields){
						return filter(
							//crmv@18199 : add User field
				  			//function(e){return !contains(['autogenerated', 'reference', 'owner', 'multipicklist', 'password'], e.type.name);}, fields
				  			function(e){return !contains(['autogenerated', 'reference', 'multipicklist', 'password'], e.type.name);}, fields
				 		);
					};
					var parentFields = map(function(e){return[e['name'],e['label']];}, filteredFields(parent['fields']));
					var referenceFieldTypes = filter(function(e){return (e['type']['name']=='reference')}, parent['fields']);
					var moduleFieldTypes = {};
					$.each(modules, function(k, v){
						moduleFieldTypes[k] = dict(map(function(e){return [e['name'], e['type']];},
							filteredFields(v['fields'])));
					});
	
					function getFieldType(fullFieldName){
						var group = fullFieldName.match(/(\w+) : \((\w+)\) (\w+)/);
						if(group==null){
							var fieldModule = moduleName;
							var fieldName = fullFieldName;
						}else{
							var fieldModule = group[2];
							var fieldName = group[3];
						}
						return moduleFieldTypes[fieldModule][fieldName];
					}
	
					function fieldReferenceNames(referenceField){
						var name = referenceField['name'];
						var label = referenceField['label'];
						function forModule(moduleName){
							// If module is not accessible return no field information
							if(!contains(accessibleModules, moduleName)) return [];
				
							return map(function(field){
								return [name+' : '+'('+moduleName+') '+field['name'], label+' : '+'('+moduleName+') '+field['label']];},
								filteredFields(modules[moduleName]['fields'])
							);
						}
						return reduceR(concat, map(forModule,referenceField['type']['refersTo']),[]);
					}
	
					var referenceFields = reduceR(concat, map(fieldReferenceNames, referenceFieldTypes), []);
					var fieldLabels = dict(parentFields.concat(referenceFields));
					
					function selectField(){

						var fe = $("#fieldName");

						fillOptions(fe, fieldLabels);
	
						var fullFieldName = fe.val();
	
						resetFields(getFieldType(fullFieldName));

						fe.bind("change", function(){
							var select = $(this);
							var fullFieldName = $(this).val();
							resetFields(getFieldType(fullFieldName));
						});
					}

					selectField();
					if (fieldName != '') {
						$("#fieldName").val(fieldName);
						resetFields(getFieldType(fieldName));
					}
					if (fieldValue != '')
						$("#fieldValue").val(fieldValue);
					$('#fieldName_busyicon').hide();
					$('#fieldName').show();
					$('#fieldValue_busyicon').hide();
					$('#fieldValue').show();

				}));
			}));
		}));
	});
}
//crmv@18199e