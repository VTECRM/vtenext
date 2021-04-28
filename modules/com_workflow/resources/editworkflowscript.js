/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function editworkflowscript($, conditions){
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

	function jsonget(operation, params, callback){
		var obj = {
				module:'com_workflow',//crmv@207901
				action:'com_workflowAjax',//crmv@207901
				file:operation, ajax:'true'};
		$.each(params,function(key, value){
			obj[key] = value;
		});
		$.get('index.php', obj,
			function(result){
				callback(result);
		});
	}




	function center(el){
		el.css({position: 'absolute'});
		el.width("400px");
		el.height("125px");
		placeAtCenter(el.get(0));
	}


	function PageLoadingPopup(){
		function show(){
			$('#workflow_loading').css('display', 'block');
			$('#save_submit').prop('disabled',true);	//crmv@40964
			//center($('#workflow_loading'));
		}
		function close(){
			$('#workflow_loading').css('display', 'none');
			$('#save_submit').prop('disabled',false);	//crmv@40964
		}
		return {
			show:show, close:close
		};
	}
	var pageLoadingPopup = PageLoadingPopup();

	function NewTemplatePopup(){
		function close(){
			$('#new_template_popup').css('display', 'none');
		}

		function show(module){
			$('#new_template_popup').css('display', 'block');
			$('#new_template_popup').css('z-index',findZMax()+1);	//crmv@26986
			center($('#new_template_popup'));
		}
		
		$('#new_template_popup_save').click(function(){
			var messageBoxPopup = MessageBoxPopup();
		
			if(trim(this.form.title.value) == '') {
				messageBoxPopup.show();
				$('#'+ 'empty_fields_message').show();
				return false;
			}
		});

		$('#new_template_popup_close').click(close);
		$('#new_template_popup_cancel').click(close);
		return {
			close:close,show:show
		};
	}
	var newTemplatePopup = NewTemplatePopup();

	function NewTaskPopup(){
		function close(){
			$('#new_task_popup').css('display', 'none');
		}

		function show(module){
			$('#new_task_popup').css('display', 'block');
			center($('#new_task_popup'));
		}

		$('#new_task_popup_close').click(close);
		$('#new_task_popup_cancel').click(close);
		return {
			close:close,show:show
		};
	}

	var operations = function(){
		//crmv@36510
		var op = {
			string:["is", "is not", "contains", "does not contain", "starts with", "ends with", "has changed"],
			number:["equal to", "less than", "greater than", "does not equal", "less than or equal to", "greater than or equal to", "has changed"],
			date:["equal to", "less than", "greater than", "does not equal", "less than or equal to", "greater than or equal to", "has changed"],
			value:['is','is not', "has changed"]
		};
		var mapping = [
			['string', ['string', 'text', 'url', 'email', 'phone']],
			['number', ['integer', 'double','currency']],
			['date', ['datetime','date']],
			['value', ['reference', 'picklist', 'multipicklist', 'time', 'boolean','picklistmultilanguage','file','signature']] //crmv@95817 crmv@99788 crmv@156774
		];
		//crmv@36510 e
	  	var out = {};
		$.each(mapping, function(i, v){
			var opName = v[0];
			var types = v[1];
			$.each(types, function(i, v){
				out[v] = op[opName];
			});
		});
		return out;
	}();

	function defaultValue(fieldType){

		function forPicklist(opType, condno){
			var value = $("#save_condition_"+condno+"_value");
			var options = implode('',
				map(function (e){return '<option value="'+e.value+'">'+e.label+'</option>';},
					opType['picklistValues'])
			);
			value.replaceWith('<select id="save_condition_'+condno+'_value" class="detailedViewTextBox value">'+
												options+'</select>');
		}

		function forInteger(opType, condno){
			var value = $(format("#save_condition_%s_value", condno));
			value.replaceWith(format('<input type="text" id="save_condition_%s_value" '+
															 'value="0" class="detailedViewTextBox value">', condno));
		}
		//crmv@36510
		var functions = {
			string:function(opType, condno){
				var value = $(format("#save_condition_%s_value", condno));
				value.replaceWith(format('<input type="text" id="save_condition_%s_value" '+
																 'value="" class="detailedViewTextBox value">', condno));
			},
			'boolean': function(opType, condno){
				var value = $("#save_condition_"+condno+"_value");
				value.replaceWith(
					'<select id="save_condition_'+condno+'_value" value="true" class="detailedViewTextBox value"> \
						<option value="true:boolean">'+alert_arr.YES+'</option>\
						<option value="false:boolean">'+alert_arr.NO+'</option>\
					</select>');
			},
			integer: forInteger,
			picklist:forPicklist,
			multipicklist:forPicklist,
			picklistmultilanguage:forPicklist, //crmv@95817
			date: forInteger
		};
		//crmv@36510 e
		var ret = functions[fieldType];
		if(ret==null){
			ret = functions['string'];
		}
		return ret;
	}

	var format = fn.format;


	function fillOptions(el,options){
		el.empty();
		$.each(options, function(k, v){
			el.append('<option value="'+k+'">'+v+'</option>');
		});
	}

	function resetFields(opType, condno){
		if (!opType) return;
		var ops = $("#save_condition_"+condno+"_operation");
		var selectedOperations = operations[opType.name];
		var l = {};
		var labels = {
			'is':'EQUALS',
			'equal to':'EQUALS',
			'is not':'NOT_EQUALS_TO',
			'does not equal':'NOT_EQUALS_TO',
			'has changed':'HAS_CHANGED',	//crmv@56962
			'contains':'CONTAINS',
			'does not contain':'DOES_NOT_CONTAINS',
			'starts with':'STARTS_WITH',
			'ends with':'ENDS_WITH',	//crmv@56962
			'less than':'LESS_THAN',
			'greater than':'GREATER_THAN',
			'less than or equal to':'LESS_OR_EQUALS',
			'greater than or equal to':'GREATER_OR_EQUALS',
		};
		for(var i=0; i<selectedOperations.length; i++){
			value = selectedOperations[i];
			transl = alert_arr[labels[value]];
			if (transl == undefined) transl = value;
			l[value] = transl;
		}
		fillOptions(ops, l);
		defaultValue(opType.name)(opType, condno);
	}

	function removeCondition(condno){
	  $(format("#save_condition_%s", condno)).remove();
	}

	//Convert user type into reference for consistency in describe objects
	//This is done inplace
	function referencify(desc){
	  var fields = desc['fields'];
	  for(var i=0; i<fields.length; i++){
		var field = fields[i];
		var type = field['type'];
		if(type['name']=='owner'){
		  type['name']='reference';
		  type['refersTo']=['Users'];
		}
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
		pageLoadingPopup.show();
		vtinst.extendSession(handleError(function(result){
			vtinst.listTypes(handleError(function(accessibleModules) {
				getDescribeObjects(accessibleModules, moduleName, handleError(function(modules){
					var parent = modules[moduleName];
					function filteredFields(fields){
						return filter(
				  			function(e){return !contains(['autogenerated', 'reference', 'owner', 'multipicklist', 'password', 'table'], e.type.name);}, fields // crmv@202235
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
						if (!fullFieldName) return null;
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
	
					function addCondition(condno){
						$("#save_conditions").append(
							'<div id="save_condition_'+condno+'" style=\'margin-bottom: 5px\'> \
								<div class="dvtCellInfo" style="float:left; padding-right:10px;"> \
									<select id="save_condition_'+condno+'_fieldname" class="detailedViewTextBox fieldname"></select> \
								</div> \
								<div class="dvtCellInfo" style="float:left; padding-right:10px;"> \
									<select id="save_condition_'+condno+'_operation" class="detailedViewTextBox operation"></select> \
								</div> \
								<div class="dvtCellInfo" style="float:left; padding-right:10px;"> \
									<input type="text" id="save_condition_'+condno+'_value" class="detailedViewTextBox value"> \
								</div> \
								<span id="save_condition_'+condno+'_remove" class="link remove-link"><a href="javascript:;"><i class="vteicon">delete</i></a></span> \
							</div>'
						);
						var fe = $("#save_condition_"+condno+"_fieldname");
						var i = 1;
						fillOptions(fe, fieldLabels);
	
						var fullFieldName = fe.val();
	
						resetFields(getFieldType(fullFieldName), condno);
	
						var re = $("#save_condition_"+condno+"_remove");
						re.bind("click", function(){
							removeCondition(condno);
						});
	
						fe.bind("change", function(){
							var select = $(this);
							var condNo = select.attr("id").match(/save_condition_(\d+)_fieldname/)[1];
							var fullFieldName = $(this).val();
							resetFields(getFieldType(fullFieldName), condNo);
						});
					}
	
					var newTaskPopup = NewTaskPopup();
					$("#new_task").click(function(){
						newTaskPopup.show();
					});
	
					var newTemplatePopup = NewTemplatePopup();
					$("#new_template").click(function(){
						newTemplatePopup.show();
					});
	
					var condno=0;
					if(conditions){
						$.each(conditions, function(i, condition){
							var fieldname = condition["fieldname"];
							addCondition(condno);
							$(format("#save_condition_%s_fieldname", condno)).val(fieldname);
							resetFields(getFieldType(fieldname), condno);
							$(format("#save_condition_%s_operation", condno)).val(condition["operation"]);
							$('#dump').html(condition["value"]);
							var text = $('#dump').text();
							$(format("#save_condition_%s_value", condno)).val(text);
							condno+=1;
						});
					}
	
					$("#save_conditions_add").bind("click", function(){
						addCondition(condno++);
					});
	
					$("#save_submit").bind("click", function(){
						var conditions = [];
						$("#save_conditions").children().each(function(i){
							var fieldname = $(this).find(".fieldname").val();
							var operation = $(this).find(".operation").val();
							var value = $(this).find(".value").val();
							var condition = {fieldname:fieldname, operation:operation, value:value};
							conditions[i]=condition;
						});
						if(conditions.length==0){
						  var out = "";
						}else{
						  var out = JSON.stringify(conditions);
						}
						$("#save_conditions_json").val(out);
					});
					pageLoadingPopup.close();
					$('#save_conditions_add').show();
				}));
			}));
		}));
	});

}